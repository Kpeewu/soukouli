<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Cours;
use App\Models\Matiere;
use App\Models\Promotion;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatiereController extends Controller
{
    use FiltersByCycle;

    /**
     * Seuls les directeurs de cycle (pas le directeur general) creent/suppriment des matieres.
     */
    private function authorizeCycleDirecteur(): void
    {
        $user = Auth::user();
        if (!$user->isDirecteur() || $user->hasRole('directeur_general')) {
            abort(403, "Seuls les directeurs de cycle peuvent gérer les matières.");
        }
    }

    public function index()
    {
        $annee = AnneeScolaire::getAnneeScolaireActive();
        $cycleIds = $this->getAccessibleCycleIds();

        $promotions = Promotion::whereIn('cycle_id', $cycleIds)
            ->where('annee_scolaire_id', $annee->id)
            ->with('matieres.promotions')
            ->get();

        $matieres = [];

        foreach ($promotions as $promotion) {
            foreach ($promotion->matieres as $matiere) {
                // Utiliser l'identifiant de la matière comme clé
                $matiereId = $matiere->id;

                // Vérifier si la matière n'a pas déjà été ajoutée
                if (!isset($matieres[$matiereId])) {
                    // Ajouter la matière au tableau
                    $matieres[$matiereId] = $matiere;
                }
            }
        }

        // Le tableau $matieres ne contiendra que des matières uniques
        $matieres = array_values($matieres);


        $tab_matieres = [];
        foreach ($matieres as $matiere) {
            $temp_matiere = [];
            $tab_promotions = [];
            foreach ($matiere->promotions as $promotion) {
                // Ne pas exposer les promotions d'un cycle non accessible
                if (!in_array($promotion->cycle_id, $cycleIds)) {
                    continue;
                }
                if (!isset($tab_promotions[$promotion->nom])) {
                    $tab_promotions[$promotion->nom] = $promotion;
                }
            }
            $temp_matiere['promotions'] = $tab_promotions;
            $temp_matiere['matiere'] = $matiere;
            array_push($tab_matieres, $temp_matiere);
        }

        $user = Auth::user();

        $data = [
            'matieres' => $tab_matieres,
            'annee' => $annee->annee,
            'canManage' => $user->isDirecteur() && !$user->hasRole('directeur_general'),
        ];

        return view('matiere.index', $data);
    }

    public function create()
    {
        $this->authorizeCycleDirecteur();

        $annee = AnneeScolaire::getAnneeScolaireActive();

        // Filtrer les promotions par annee scolaire courante et par cycle accessible
        $promotions = Promotion::with('cycle')
            ->where('annee_scolaire_id', $annee->id)
            ->whereIn('cycle_id', $this->getAccessibleCycleIds())
            ->orderBy('cycle_id')
            ->orderBy('ordre')
            ->get();

        $data = [
            'annee' => $annee->annee,
            'promotions' => $promotions
        ];

        return view('matiere.create', $data);
    }

    public function store(Request $request)
    {
        $this->authorizeCycleDirecteur();

        $request->validate([
            'intitule' => 'required|string|max:255',
            'promotions' => 'required|array',
            'promotions.*' => 'exists:promotions,id',
        ]);

        $promotions = Promotion::whereIn('id', $request->promotions)->get();
        foreach ($promotions as $promotion) {
            $this->authorizeAccessPromotion($promotion);
        }

        $matiere = Matiere::create([
            'intitule' => $request->intitule
        ]);

        foreach ($promotions as $promotion) {
            $promotion->matieres()->attach($matiere);
            $classes = $promotion->classes;
            foreach ($classes as $classe) {
                Cours::create([
                    'nom' => $matiere->intitule,
                    'coefficient' => 1,
                    'classe_id' => $classe->id,
                    'matiere_id' => $matiere->id
                ]);
            }
        }

        return redirect()->to(route('matiere.index'))->with('notification', ['type' => 'success', 'message' => 'Matière crée avec succès']);
    }


    public function edit(Matiere $matiere)
    {
    }

    public function update(Matiere $matiere)
    {
    }


    public function destroy(Matiere $matiere)
    {
        $this->authorizeCycleDirecteur();

        $cycleIds = $this->getAccessibleCycleIds();
        $matiere->load('promotions.classes');

        $promotionsDansMonCycle = $matiere->promotions->filter(
            fn ($promotion) => in_array($promotion->cycle_id, $cycleIds)
        );

        if ($promotionsDansMonCycle->isEmpty()) {
            abort(403, "Vous n'avez pas accès à cette matière.");
        }

        foreach ($promotionsDansMonCycle as $promotion) {
            // Supprime uniquement les cours de cette matiere pour les classes de CE cycle
            Cours::where('matiere_id', $matiere->id)
                ->whereIn('classe_id', $promotion->classes->pluck('id'))
                ->delete();

            $promotion->matieres()->detach($matiere->id);
        }

        // Ne supprimer la Matiere elle-meme que si plus aucun cycle ne l'utilise
        $message = 'Matière retirée de votre cycle';
        if ($matiere->promotions()->count() === 0) {
            $matiere->delete();
            $message = 'Matière supprimée';
        }

        $url = url()->previous();
        return redirect()->to($url)->with('notification', ['type' => 'success', 'message' => $message]);
    }
}
