<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Cycle;
use App\Models\Professeur;
use App\Models\User;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProfesseurController extends Controller
{
    use FiltersByCycle;

    /**
     * Seuls les directeurs de cycle (pas le directeur general) et les secretaires (de cycle ou
     * secretaire general) peuvent recruter un enseignant.
     */
    private function authorizeRecruteProfesseur(): void
    {
        $user = Auth::user();
        $isDirecteurCycle = $user->isDirecteur() && !$user->hasRole('directeur_general');

        if (!$isDirecteurCycle && !$user->isSecretaire()) {
            abort(403, 'Seuls les directeurs de cycle et les secrétaires peuvent recruter un enseignant.');
        }
    }

    /**
     * Le directeur general ne peut que consulter la liste des enseignants, pas les gerer.
     */
    private function authorizeManageProfesseur(Professeur $professeur): void
    {
        if (Auth::user()->hasRole('directeur_general')) {
            abort(403, 'Le directeur général ne peut que consulter la liste des enseignants.');
        }

        if (!$this->canAccessProfesseur($professeur)) {
            abort(403, 'Vous n\'avez pas accès à ce professeur.');
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $cycle = $this->resolveRequestedCycle($request);
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        $query = Professeur::query();

        if ($cycle) {
            // forCycle() inclut aussi les professeurs qui enseignent un cours ou sont
            // titulaires d'une classe dans ce cycle, meme si leur cycle de rattachement
            // (cycle_id) est different (un professeur peut intervenir dans plusieurs cycles).
            $query->forCycle($cycle->id);
        }

        $professeurs = $query->get();

        // Filtre par cycle disponible pour le directeur general et le secretaire general,
        // qui ont acces a tous les cycles et ont besoin de restreindre l'affichage.
        $showCycleFilter = $user->hasRole('directeur_general') || $user->hasRole('secretaire_general');
        $cycles = $showCycleFilter ? Cycle::orderBy('ordre')->get() : collect();

        // Filtrer les promotions par annee scolaire courante pour l'affichage des classes
        $promotionsQuery = \App\Models\Promotion::with('classes')
            ->where('annee_scolaire_id', $anneeCourante->id)
            ->orderBy('cycle_id')
            ->orderBy('ordre');

        // Filtrer par cycle si necessaire
        if ($cycle) {
            $promotionsQuery->where('cycle_id', $cycle->id);
        }

        $promotions = $promotionsQuery->get();

        $isDirecteurCycle = $user->isDirecteur() && !$user->hasRole('directeur_general');

        $data = [
            'professeurs' => $professeurs,
            'cycles' => $cycles,
            'showCycleFilter' => $showCycleFilter,
            'selectedCycleId' => $cycle?->id,
            'promotions' => $promotions,
            'canRecruit' => $isDirecteurCycle || $user->isSecretaire(),
            'canManageProfesseur' => !$user->hasRole('directeur_general'),
        ];

        return view('professeur.index', $data);
    }

    public function create()
    {
        $this->authorizeRecruteProfesseur();

        // Récupérer les cycles accessibles pour le formulaire
        $cycles = $this->getAccessibleCycles();

        return view('professeur.create', [
            'cycles' => $cycles,
            'classesGroupees' => $this->getClassesDisponiblesGroupees(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeRecruteProfesseur();

        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'sexe' => 'required|in:M,F',
            'contact' => 'nullable|string|max:30',
            'cycle_id' => 'nullable|exists:cycles,id',
            'classe_ids' => 'nullable|array',
            'classe_ids.*' => 'exists:classes,id',
        ]);

        // Generer un username unique pour le compte utilisateur
        $baseUsername = Str::lower(substr($request->prenom, 0, 1) . $request->nom);
        $baseUsername = Str::ascii($baseUsername); // Supprimer les accents
        $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername); // Garder seulement lettres et chiffres
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        // Creer le compte utilisateur avec mot de passe par defaut "professeur"
        $user = User::create([
            'username' => $username,
            'password' => 'professeur', // Le cast 'hashed' s'occupe du hashage
        ]);
        $user->assignRole('professeur');

        // Assigner automatiquement le cycle si c'est un directeur
        $userCycle = $this->getUserCycle();
        $cycleId = $userCycle ? $userCycle->id : $request->cycle_id;

        $professeur = Professeur::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'contact' => $request->contact,
            'sexe' => $request->sexe,
            'user_id' => $user->id,
            'cycle_id' => $cycleId,
        ]);

        if ($request->filled('classe_ids')) {
            Classe::whereIn('id', $request->classe_ids)->update(['professeur_id' => $professeur->id]);
        }

        return redirect()->to(route('professeur.index'))->with('notification', ['type' =>  'success', 'message' =>  "Enseignant recruté avec compte utilisateur (username: $username)"]);
    }

    public function edit(Professeur $professeur)
    {
        $this->authorizeManageProfesseur($professeur);

        $cycles = $this->getAccessibleCycles();

        $data = [
            'professeur' => $professeur,
            'cycles' => $cycles,
            'classesGroupees' => $this->getClassesDisponiblesGroupees($professeur),
        ];

        return view('professeur.edit', $data);
    }

    public function update(Request $request, Professeur $professeur)
    {
        $this->authorizeManageProfesseur($professeur);

        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'sexe' => 'required|in:M,F',
            'contact' => 'nullable|string|max:30',
            'classe_ids' => 'nullable|array',
            'classe_ids.*' => 'exists:classes,id',
        ]);

        $professeur->update($request->only(['nom', 'prenom', 'sexe', 'contact']));

        $classeIds = $request->input('classe_ids', []);

        // Détache les classes tutorées qui ne sont plus sélectionnées
        foreach ($professeur->classes as $classe) {
            if (!in_array($classe->id, $classeIds)) {
                $classe->update(['professeur_id' => null]);
            }
        }

        if (!empty($classeIds)) {
            Classe::whereIn('id', $classeIds)->update(['professeur_id' => $professeur->id]);
        }

        return redirect()->to(route('professeur.index'))->with('notification', ['type' =>  'success', 'message' =>  "Enseignant modifié"]);
    }

    /**
     * Classes sans titulaire (ou déjà tutorées par $professeur), groupées par promotion.
     * Le libellé du groupe inclut le cycle quand l'utilisateur a accès à plusieurs cycles.
     */
    private function getClassesDisponiblesGroupees(?Professeur $professeur = null): array
    {
        $promotions = $this->getFilteredPromotions();
        $multiCycles = $this->getAccessibleCycles()->count() > 1;

        $groupes = [];

        foreach ($promotions as $promotion) {
            $classes = $promotion->classes->filter(function ($classe) use ($professeur) {
                return $classe->professeur_id === null || ($professeur && $classe->professeur_id === $professeur->id);
            })->values();

            if ($classes->isEmpty()) {
                continue;
            }

            $groupes[] = [
                'label' => $multiCycles && $promotion->cycle ? "{$promotion->cycle->nom} — {$promotion->nom}" : $promotion->nom,
                'classes' => $classes,
            ];
        }

        return $groupes;
    }


    public function destroy(Professeur $professeur)
    {
        $this->authorizeManageProfesseur($professeur);

        $url = url()->previous();

        $cours = $professeur->cours;

        foreach ($cours as $cour) {
            $cour->update([
                'professeur_id' => null,
            ]);
            $cour->save();
        }

        $professeur->delete();

        return redirect()->to($url)->with('notification', ['type' =>  'success', 'message' =>  "L'enseignant à été supprimé"]);
    }
}
