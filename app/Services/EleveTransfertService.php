<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EleveTransfertService
{
    /**
     * Change l'élève de classe au sein de la même promotion (changement de
     * groupe en cours d'année, ex: 6ème A -> 6ème B).
     *
     * Les notes de devoir et de composition déjà saisies sont reportées vers
     * les cours équivalents (même matière) de la nouvelle classe : un devoir
     * étant en général donné le même jour à toutes les classes d'une
     * promotion (l'écran de création d'évaluation crée une évaluation par
     * classe en une seule action), on rattache la note à l'évaluation native
     * déjà existante de la nouvelle classe (même type/date/barème) quand elle
     * existe, pour que la note apparaisse directement dans la liste de
     * notes de la nouvelle classe plutôt que dans une évaluation séparée. Une
     * évaluation n'est créée que si la nouvelle classe n'a vraiment aucune
     * évaluation correspondante.
     *
     * Les notes d'interrogation ne sont pas reportées et restent attachées à
     * l'ancien cours. L'assiduité, les retards et les absences suivent déjà
     * l'élève automatiquement car ils sont rattachés au trimestre (et non à
     * la classe), qui ne change pas puisque la promotion reste la même.
     *
     * @return array{notes_reportees: int, ancienne_classe: string, nouvelle_classe: string}
     */
    public function changerClasse(Eleve $eleve, Classe $ancienneClasse, Classe $nouvelleClasse): array
    {
        if ($ancienneClasse->id === $nouvelleClasse->id) {
            return ['notes_reportees' => 0, 'ancienne_classe' => $ancienneClasse->nom, 'nouvelle_classe' => $nouvelleClasse->nom];
        }

        if ($ancienneClasse->promotion_id !== $nouvelleClasse->promotion_id) {
            throw new InvalidArgumentException('Les deux classes doivent appartenir à la même promotion.');
        }

        return DB::transaction(function () use ($eleve, $ancienneClasse, $nouvelleClasse) {
            $notes = Note::where('eleve_id', $eleve->id)
                ->whereHas('evaluation', function ($query) use ($ancienneClasse) {
                    $query->whereIn('type', ['devoir', 'composition'])
                        ->whereHas('cours', fn ($q) => $q->where('classe_id', $ancienneClasse->id));
                })
                ->with(['evaluation.cours.matiere', 'evaluation.source.cours'])
                ->get();

            $notesReportees = 0;

            foreach ($notes as $note) {
                $evaluation = $note->evaluation;

                // On remonte toujours à l'évaluation d'origine véritable, jamais
                // à un clone intermédiaire : un aller-retour A -> B -> A ne doit
                // pas créer une évaluation supplémentaire en plus de l'originale.
                $racine = $evaluation->evaluation_source_id ? $evaluation->source : $evaluation;

                if ($racine->cours->classe_id === $nouvelleClasse->id) {
                    continue;
                }

                $coursCible = Cours::firstOrCreate(
                    ['classe_id' => $nouvelleClasse->id, 'matiere_id' => $racine->cours->matiere_id],
                    ['nom' => $racine->cours->matiere->intitule . ' ' . $nouvelleClasse->nom]
                );

                // Évaluation déjà donnée par la nouvelle classe le même jour, avec le
                // même barème : c'est très probablement le même devoir/composition.
                $evaluationCible = Evaluation::where('cours_id', $coursCible->id)
                    ->where('type', $racine->type)
                    ->where('date', $racine->date)
                    ->where('note_maximale', $racine->note_maximale)
                    ->first();

                if (!$evaluationCible) {
                    $evaluationCible = Evaluation::create([
                        'intitule' => str_replace($ancienneClasse->nom, $nouvelleClasse->nom, $racine->intitule),
                        'type' => $racine->type,
                        'date' => $racine->date,
                        'note_maximale' => $racine->note_maximale,
                        'cours_id' => $coursCible->id,
                        'evaluation_source_id' => $racine->id,
                    ]);
                }

                // On pointe toujours vers la note d'origine véritable (jamais vers une copie
                // intermédiaire) : la moyenne (calculée par matière/trimestre à travers tous
                // les cours) exclut ensuite toute note référencée comme source par une autre,
                // pour ne pas compter deux fois le même devoir/composition.
                $racineNoteId = $note->note_source_id ?? $note->id;

                $noteReportee = Note::firstOrCreate(
                    ['evaluation_id' => $evaluationCible->id, 'eleve_id' => $eleve->id],
                    ['valeur' => $note->valeur, 'trimestre_id' => $note->trimestre_id, 'note_source_id' => $racineNoteId]
                );

                if ($noteReportee->wasRecentlyCreated) {
                    $notesReportees++;
                }
            }

            $eleve->classes()->detach($ancienneClasse->id);
            $eleve->classes()->attach($nouvelleClasse->id);

            return [
                'notes_reportees' => $notesReportees,
                'ancienne_classe' => $ancienneClasse->nom,
                'nouvelle_classe' => $nouvelleClasse->nom,
            ];
        });
    }
}
