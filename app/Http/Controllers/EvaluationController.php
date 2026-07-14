<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Promotion;
use App\Models\Trimestre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    /**
     * Filtre les cours selon le rôle de l'utilisateur
     * Les professeurs ne voient que leurs propres cours, les secretaires de cycle
     * ne voient que les cours de leur cycle
     */
    private function filterCoursForUser($cours)
    {
        $user = Auth::user();

        // Admin et directeurs voient tous les cours
        if ($user->isAdmin() || $user->isDirecteur()) {
            return $cours;
        }

        // Les professeurs ne voient que leurs cours
        if ($user->isProfesseur() && $user->professeur) {
            $professeurId = $user->professeur->id;
            return $cours->filter(function ($cour) use ($professeurId) {
                return $cour->professeur_id === $professeurId;
            });
        }

        // Les secretaires de cycle ne voient que les cours de leur cycle
        if ($secretaireCycle = $user->getSecretaireCycle()) {
            return $cours->filter(function ($cour) use ($secretaireCycle) {
                return $cour->classe->promotion->cycle_id === $secretaireCycle->id;
            });
        }

        return collect();
    }

    /**
     * Vérifie si l'utilisateur peut accéder au cours
     */
    private function canAccessCours(Cours $cours): bool
    {
        $user = Auth::user();

        if ($user->isAdmin() || $user->isDirecteur()) {
            return true;
        }

        if ($user->isProfesseur() && $user->professeur) {
            return $cours->professeur_id === $user->professeur->id;
        }

        if ($secretaireCycle = $user->getSecretaireCycle()) {
            return $cours->classe->promotion->cycle_id === $secretaireCycle->id;
        }

        return false;
    }

    /**
     * Seuls les professeurs et les secretaires de cycle (pas le secretaire general, ni les
     * directeurs) peuvent créer des interrogations.
     */
    private function authorizeCreateEvaluation(): void
    {
        $user = Auth::user();

        if (!$user->isProfesseur() && !$user->getSecretaireCycle()) {
            abort(403, 'Seuls les professeurs et les secrétaires de cycle peuvent créer des interrogations.');
        }
    }

    /**
     * Seuls les secretaires de cycle (pas le secretaire general, ni les professeurs, ni les
     * directeurs) peuvent créer des devoirs et compositions. Les professeurs ne peuvent que
     * consulter et saisir les notes des devoirs deja crees pour leurs cours (voir show/update).
     */
    private function authorizeCreateDevoir(): void
    {
        if (!Auth::user()->getSecretaireCycle()) {
            abort(403, 'Seuls les secrétaires de cycle peuvent créer des devoirs ou compositions.');
        }
    }

    /**
     * Vérifie si l'utilisateur peut modifier ou supprimer l'évaluation. Les directeurs n'ont
     * qu'un droit de consultation : seuls le professeur du cours et le secretaire de son cycle
     * peuvent modifier ou supprimer.
     */
    private function canModifyEvaluation(Evaluation $evaluation): bool
    {
        return Auth::user()->canManageEvaluation($evaluation);
    }

    // liste des cours pour créer une intérrogation
    public function choixCours(Classe $classe)
    {
        $this->authorizeCreateEvaluation();

        $cours = $this->filterCoursForUser($classe->cours);
        $trimestres = $classe->promotion->trimestres;

        $data = [
            'cours' => $cours,
            'trimestres' => $trimestres,
            'classe' => $classe
        ];

        return view('evaluation.interrogation.liste_cours', $data);
    }


    public function choixCoursViewInterrogation(Classe $classe)
    {
        $cours = $this->filterCoursForUser($classe->cours);
        $trimestres = $classe->promotion->trimestres;

        $data = [
            'cours' => $cours,
            'trimestres' => $trimestres,
            'classe' => $classe
        ];

        return view('evaluation.interrogation.view.liste_cours', $data);
    }


    // liste des matières pour créer une évaluation (devoir/composition)
    public function choixMatiere(Promotion $promotion)
    {
        $this->authorizeCreateDevoir();

        $user = Auth::user();
        if ($secretaireCycle = $user->getSecretaireCycle()) {
            if ($promotion->cycle_id !== $secretaireCycle->id) {
                abort(403, "Vous n'avez pas accès à ce cycle.");
            }
        }

        // on récupère toutes les matières du niveau
        $matieres = $promotion->matieres;

        $data = [
            'promotion' => $promotion,
            'matieres' => $matieres
        ];

        return view('evaluation.liste_matieres', $data);
    }


    // les interrogations de la classe dans le cours donné dans le trimestre choisi
    public function indexInterrogation(Classe $classe, Cours $cours, Trimestre $trimestre)
    {
        // Vérifier l'accès au cours pour les professeurs
        if (!$this->canAccessCours($cours)) {
            abort(403, 'Vous n\'avez pas accès à ce cours');
        }

        $evaluations = $cours->evaluations;


        // tableau contenant les interrogations du trimestre dans le cours
        $evaluation_trimestre = [];

        foreach ($evaluations as $evaluation) {
            if (($evaluation->notes[0]->trimestre_id === $trimestre->id) && $evaluation->type === 'interrogation') {
                array_push($evaluation_trimestre, $evaluation);
            }
        }

        $data = [
            'evaluations' => $evaluation_trimestre,
            'cours' => $cours,
            'classe' => $classe,
            'trimestre' => $trimestre,
            'canManage' => Auth::user()->canManageCours($cours),
        ];

        return view('evaluation.interrogation.view.index', $data);
    }


    // liste des matières pour voir les évaluations et les modifier
    public function choixMatiereViewEvaluation(Promotion $promotion)
    {
        $user = Auth::user();

        // on récupère toutes les matières du niveau
        $matieres = $promotion->matieres;

        // Un professeur ne voit que les matières qu'il enseigne dans cette promotion.
        if ($user->isProfesseur() && $user->professeur) {
            $matiereIds = $user->professeur->cours()
                ->whereHas('classe', fn ($q) => $q->where('promotion_id', $promotion->id))
                ->pluck('matiere_id')
                ->unique();

            $matieres = $matieres->whereIn('id', $matiereIds);
        }

        $data = [
            'promotion' => $promotion,
            'matieres' => $matieres
        ];

        return view('evaluation.view.liste_matieres', $data);
    }

    // listes des évaluations dans la matière au cours du trimestre selectionné
    public function index(Promotion $promotion, Matiere $matiere, Trimestre $trimestre)
    {
        $user = Auth::user();

        // tableau contenant les évaluations du cours dans la matière donnée
        $tab_evaluations = [];

        $classes = $promotion->classes;

        // récupération des évaluations dans la matière donnée dans toutes les classes
        foreach ($classes as $classe) {
            foreach ($classe->cours as $cour) {
                if ($cour->matiere->id === $matiere->id) {
                    // Un professeur ne voit que les devoirs des cours qu'il enseigne.
                    if ($user->isProfesseur() && $user->professeur && $cour->professeur_id !== $user->professeur->id) {
                        continue;
                    }

                    foreach ($cour->evaluations as $evaluation) {
                        array_push($tab_evaluations, $evaluation);
                    }
                }
            }
        }


        // tableau contenant les évaluations du trimestre dans le cours
        $evaluation_trimestre = [];

        // tri des evaluations du trimestre concerné en fonction du trimestre de l'une des notes dans l'évaluation donnée
        // les notes reportées suite à un changement de classe d'élève (EleveTransfertService) sont ajoutées
        // directement à l'évaluation native de la nouvelle classe quand elle existe déjà (même cours/type/date/
        // barème), donc elles apparaissent normalement ici sans entrée séparée.
        foreach ($tab_evaluations as $evaluation) {
            if (($evaluation->notes[0]->trimestre_id === $trimestre->id) && ($evaluation->type === 'devoir' || $evaluation->type === 'composition')) {
                array_push($evaluation_trimestre, $evaluation);
            }
        }



        $data = [
            'evaluations' => $evaluation_trimestre,
            'promotion' => $promotion,
            'matiere' => $matiere,
            'trimestre' => $trimestre,
        ];

        return view('evaluation.view.index', $data);
    }


    public function show(Evaluation $evaluation, Trimestre $trimestre, Promotion $promotion)
    {
        // Vérifier l'accès en consultation à l'évaluation (directeurs inclus)
        if (!$evaluation->cours || !$this->canAccessCours($evaluation->cours)) {
            abort(403, 'Vous n\'avez pas accès à cette évaluation');
        }

        $data = [
            'trimestre' => $trimestre,
            'promotion' => $promotion,
            'evaluation' => $evaluation,
            'canManage' => Auth::user()->canManageEvaluation($evaluation),
            'stats' => $evaluation->statistiques(),
        ];



        return view('evaluation.view.show', $data);
    }



    // page de création d'un devoir/composition
    public function create(Promotion $promotion, Matiere $matiere, Trimestre $trimestre)
    {
        $this->authorizeCreateDevoir();

        $user = Auth::user();

        if ($secretaireCycle = $user->getSecretaireCycle()) {
            if ($promotion->cycle_id !== $secretaireCycle->id) {
                abort(403, "Vous n'avez pas accès à ce cycle.");
            }
        }

        $tab_classes = [];
        // recupération ds classes et des cours

        $classes = $promotion->classes;

        foreach ($classes as $classe) {
            foreach ($classe->cours as $cour) {
                if ($cour->matiere->id === $matiere->id) {
                    array_push($tab_classes, ['classe' => $classe, 'cours' => $cour, 'eleves' => $classe->eleves->sortBy('nom')]);
                }
            }
        }


        $data = [
            'promotion' => $promotion,
            'classes' => $tab_classes,
            'matiere' => $matiere,
            'trimestre' => $trimestre,
        ];

        return view('evaluation.create', $data);
    }


    // page de création d'une interrogation
    public function createInterrogation(Classe $classe, Cours $cours,  Trimestre $trimestre)
    {
        $this->authorizeCreateEvaluation();

        // Vérifier l'accès au cours pour les professeurs et secretaires de cycle
        if (!$this->canAccessCours($cours)) {
            abort(403, 'Vous n\'avez pas accès à ce cours');
        }

        $data = [
            'cours' => $cours,
            'classe' => $classe,
            'trimestre' => $trimestre,
        ];

        return view('evaluation.interrogation.create', $data);
    }


    // Création des évaluations
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($request->type === 'devoir' || $request->type === 'composition') {
            $this->authorizeCreateDevoir();

            $trimestre = Trimestre::find($request->trimestre_id);

            // Récupérer les notes pour chaque élève
            $eleves = $request->input('eleves');

            if (!$eleves) {
                $url = url()->previous();
                return redirect()->to($url)->with('notification', ['type' => 'error', 'message' => 'Impossible de créer l\'évaluation aucun élève existant']);
            }

            // Tableau pour stocker les IDs de cours uniques
            $idsCours = [];

            foreach ($request->eleves as $eleve) {
                // Ajouter l'ID de cours au tableau
                $idsCours[] = $eleve['cours_id'];
            }



            // Récupérer les IDs de cours de manière unique
            $idsCoursUniques = array_unique($idsCours);

            $tab_cours = [];
            foreach ($idsCoursUniques as $id) {
                $cours = Cours::find($id);
                // Vérifier l'accès au cours pour les professeurs
                if (!$this->canAccessCours($cours)) {
                    abort(403, 'Vous n\'avez pas accès à ce cours');
                }
                array_push($tab_cours, $cours);
            }



            $tab_evaluations = [];

            // Exemple de traitement des données
            foreach ($tab_cours as $cours) {
                $evaluation = Evaluation::create([
                    'intitule' => $request->intitule . ' ' . $cours->classe->nom . ' ' . substr(
                        $trimestre->intitule,
                        0,
                        11
                    ),
                    'type' => $request->type,
                    'note_maximale' => $request->note_maximale,
                    'date' => $request->date,
                    'cours_id' => $cours->id
                ]);
                array_push($tab_evaluations, $evaluation);
            }

            foreach ($tab_evaluations as $evaluation) {
                foreach ($eleves as $eleve) {
                    if (intval($eleve['cours_id']) === $evaluation->cours_id) {

                        $note = Note::create([
                            'valeur' => floatval($eleve['note']),
                            'evaluation_id' => $evaluation->id,
                            'trimestre_id' => $request->trimestre_id,
                            'eleve_id' => intval($eleve['eleve_id'])
                        ]);
                        $eleve_model = Eleve::find($eleve['eleve_id']);
                        $evaluation->notes()->save($note);
                        $eleve_model->notes()->save($note);
                        $trimestre->notes()->save($note);
                    }
                }
            }

            $trimestre = Trimestre::find($request->trimestre_id);



            return redirect()->route('evaluation.create', ['promotion' => $trimestre->promotion, 'matiere' => $tab_cours[0]->matiere, 'trimestre' => $trimestre])->with('notification', ['type' => 'success', 'message' => 'Evaluation créée avec succès']);
        }


        if ($request->type === 'interrogation') {
            $this->authorizeCreateEvaluation();

            $cours = Cours::find($request->cours_id);

            // Vérifier l'accès au cours pour les professeurs
            if (!$this->canAccessCours($cours)) {
                abort(403, 'Vous n\'avez pas accès à ce cours');
            }

            $trimestre = Trimestre::find($request->trimestre_id);
            $evaluation = Evaluation::create([
                'intitule' => $request->intitule . ' ' . $cours->classe->nom . ' ' . substr(
                    $trimestre->intitule,
                    0,
                    11
                ),
                'type' => $request->type,
                'note_maximale' => $request->note_maximale,
                'date' => $request->date,
                'cours_id' => $request->cours_id
            ]);


            $eleves = $request->input('eleves');

            if (!$eleves) {
                $url = url()->previous();
                return redirect()->to($url)->with('notification', ['type' => 'error', 'message' => 'Impossible de créer l\'évaluation aucun élève existant']);
            }

            foreach ($eleves as $eleve) {

                $note = Note::create([
                    'valeur' => floatval($eleve['note']),
                    'evaluation_id' => $evaluation->id,
                    'trimestre_id' => $request->trimestre_id,
                    'eleve_id' => intval($eleve['eleve_id'])
                ]);
                $eleve_model = Eleve::find($eleve['eleve_id']);
                $evaluation->notes()->save($note);
                $eleve_model->notes()->save($note);
                $trimestre->notes()->save($note);
            }

            return redirect()->route('evaluation.create.interrogation', ['classe' => $cours->classe, 'cours' => $cours, 'trimestre' => $trimestre])->with('notification', ['type' => 'success', 'message' => 'Interrogation créée avec succès']);
        }
    }


    // mise à jour des notes d'une évaluation et de l'évaluation
    public function update(Request $request, Evaluation $evaluation, Trimestre $trimestre)
    {
        // Vérifier l'accès à l'évaluation pour les professeurs
        if (!$this->canModifyEvaluation($evaluation)) {
            abort(403, 'Vous n\'avez pas le droit de modifier cette évaluation');
        }

        // $request->validate([
        //     'note_maximale' => 'bail|min:0|max:20'
        // ]);

        $evaluation->update([
            'intitule' => $request->intitule,
            'date' => $request->date,
            'type' => $request->type,
            'note_maximale' => $request->note_maximale
        ]);

        $evaluation->save();

        $notes = $request->notes;



        foreach ($notes as $note) {

            // on verifie si la note n'est pas supérieure au barême
            if ($note['valeur'] > $evaluation->note_maximale) {
                return redirect()->route('evaluation.show', ['evaluation' => $evaluation, 'trimestre' => $trimestre, 'promotion' => $evaluation->cours->classe->promotion])->with('notification', ['type' => 'warning', 'message' => 'La note ne doit pas dépasser le barême']);
            }

            $old_note = Note::find($note['note_id']);
            $old_note->update([
                'valeur' => $note['valeur']
            ]);
            $old_note->save();
        }


        return redirect()->route('evaluation.show', ['evaluation' => $evaluation, 'trimestre' => $trimestre, 'promotion' => $evaluation->cours->classe->promotion])->with('notification', ['type' => 'success', 'message' => 'Evaluation et notes mises à jour avec succès']);
    }

    public function destroy(Evaluation $evaluation)
    {
        // Un devoir/composition ne peut etre supprime que par le secretaire de cycle ; une
        // interrogation peut l'etre par le professeur du cours ou le secretaire de cycle.
        if (!Auth::user()->canDeleteEvaluation($evaluation)) {
            abort(403, 'Vous n\'avez pas le droit de supprimer cette évaluation');
        }

        $url = url()->previous();
        $evaluation->delete();
        return redirect()->to($url)->with('notification', ['type' => 'danger', 'message' => 'Evaluation supprimée']);
    }
}
