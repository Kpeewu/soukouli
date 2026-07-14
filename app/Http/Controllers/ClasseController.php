<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Professeur;
use App\Models\Promotion;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\ViewException;
use Ismaelw\LaraTeX\LaraTeX;

class ClasseController extends Controller
{
    use FiltersByCycle;

    /**
     * Seul le directeur du cycle de la promotion (pas le directeur general) peut ajouter un groupe.
     */
    private function authorizeCreateClasse(Promotion $promotion): void
    {
        $user = Auth::user();
        if (!$user->isDirecteur() || $user->hasRole('directeur_general')) {
            abort(403, "Seul le directeur du cycle peut ajouter un groupe.");
        }
        $this->authorizeAccessPromotion($promotion);
    }

    // liste des élèves de la classe
    public function index(Classe $classe)
    {
        $user = Auth::user();

        // Un professeur ne voit que les classes ou il enseigne effectivement un cours,
        // pas l'ensemble des classes accessibles au niveau de son cycle.
        if ($user->isProfesseur()) {
            $enseigneDansClasse = $user->professeur
                && $user->professeur->cours()->where('classe_id', $classe->id)->exists();

            if (!$enseigneDansClasse) {
                abort(403, "Vous n'enseignez pas dans cette classe.");
            }
        }

        $eleves = $classe->eleves;

        $data = [
            'classe' => $classe,
            'eleves' => $eleves->sortBy('nom'),
            // Un professeur ne peut que consulter et imprimer la liste des élèves.
            'isProfesseur' => $user->isProfesseur(),
            'canValidatePassage' => $user->isDirecteur() && !$user->hasRole('directeur_general') && $this->canAccessClasse($classe),
            'canDeleteEleve' => $user->getSecretaireCycle() && $this->canAccessClasse($classe),
            'canEditEleve' => $user->getSecretaireCycle() && $this->canAccessClasse($classe),
            'canGenerateBulletin' => $user->isSecretaire() && $this->canAccessClasse($classe),
            'canGenerateCartes' => $user->isSecretaire() && $this->canAccessClasse($classe),
        ];

        return view('classe.index', $data);
    }


    public function listeClasses(Promotion $promotion)
    {
        $user = Auth::user();

        $data = [
            'promotion' => $promotion,
            'canManage' => $user->isDirecteur() && !$user->hasRole('directeur_general') && $this->canAccessPromotion($promotion),
        ];

        return view('classe.liste-classes', $data);
    }


    public function create(Promotion $promotion)
    {
        $this->authorizeCreateClasse($promotion);

        $data = [
            'professeurs' => Professeur::all(),
            'promotion' => $promotion
        ];

        return view('classe.create', $data);
    }

    public function store(Request $request)
    {
        $url = url()->previous();
        $promotion = Promotion::find($request->promotion_id);

        $this->authorizeCreateClasse($promotion);

        $nom = $promotion->nom . ' ' . $request->nom;

        if (Classe::where('promotion_id', $promotion->id)->where('nom', $nom)->exists()) {
            return redirect()->to($url)->with('notification', ['type' => 'error', 'message' => 'Le groupe ' . $request->nom . ' existe déjà pour ' . $promotion->nom . '.']);
        }

        $classe = Classe::create([
            'nom' => $nom,
            'promotion_id' => $promotion->id,
            'professeur_id' => $request->professeur_id
        ]);

        // on récupère les matières de la promotion
        $matieres = $promotion->matieres;

        foreach ($matieres as $matiere) {
            Cours::create([
                'nom' => $matiere->intitule . ' ' . $classe->nom,
                'classe_id' => $classe->id,
                'matiere_id' => $matiere->id
            ]);
        }

        return redirect()->to($url)->with('notification', ['type' =>  'success', 'message' => 'Nouvelle de classe de ' . $promotion->nom . ' crée']);
    }

    public function destroy(Classe $classe)
    {
        $url = url()->previous();
        $classe->delete();
        return redirect()->to($url)->with('notification', ['type' =>  'danger', 'message' => 'La classe à été supprimée']);
    }
}
