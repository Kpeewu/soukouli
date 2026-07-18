<?php

namespace Database\Seeders;

use App\Models\Cours;
use App\Models\Evaluation;
use Carbon\Carbon;
use Database\Seeders\Support\CalendrierScolaire;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EvaluationSeeder extends Seeder
{
    /**
     * Types d'évaluations avec leurs caractéristiques
     */
    private array $typesEvaluation = [
        'interrogation' => [
            'note_maximale' => 10,
            'nombre_par_trimestre' => 2,
            'intitule_pattern' => 'Interrogation %d'
        ],
        'devoir' => [
            'note_maximale' => 20,
            'nombre_par_trimestre' => 1,
            'intitule_pattern' => 'Devoir surveillé'
        ],
        'composition' => [
            'note_maximale' => 20,
            'nombre_par_trimestre' => 1,
            'intitule_pattern' => 'Composition'
        ]
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer tous les cours avec leurs classes et promotions
        $cours = Cours::with('classe.promotion.trimestres', 'classe.promotion.anneeScolaire', 'matiere')->get();

        if ($cours->isEmpty()) {
            $this->command->warn('Aucun cours trouvé. Exécutez d\'abord CoursSeeder.');
            return;
        }

        $evaluationsCreees = 0;

        foreach ($cours as $cour) {
            // Vérifier que la promotion a des trimestres
            if (!$cour->classe || !$cour->classe->promotion || !$cour->classe->promotion->trimestres) {
                continue;
            }

            $annee = $cour->classe->promotion->anneeScolaire;

            if (!$annee) {
                continue;
            }

            // Le rang de la periode se deduit de l'ordre de creation
            // (PromotionSeeder les cree sequentiellement), pas d'un parsing
            // de l'intitule qui serait fragile.
            $trimestres = $cour->classe->promotion->trimestres->sortBy('id')->values();
            $matiereNom = $cour->matiere ? $cour->matiere->intitule : 'Matière';

            foreach ($trimestres as $index => $trimestre) {
                [$debutPeriode, $finPeriode] = CalendrierScolaire::fenetre(
                    $annee,
                    $index + 1,
                    $trimestres->count()
                );

                // Créer les évaluations pour chaque type
                foreach ($this->typesEvaluation as $type => $config) {
                    $nombre = $config['nombre_par_trimestre'];

                    for ($i = 1; $i <= $nombre; $i++) {
                        // Générer l'intitulé
                        $intitule = $nombre > 1
                            ? sprintf($config['intitule_pattern'], $i) . " de {$matiereNom}"
                            : $config['intitule_pattern'] . " de {$matiereNom}";

                        // Générer une date dans la période du trimestre
                        $date = $this->generateDate($debutPeriode, $finPeriode, $type, $i);

                        Evaluation::firstOrCreate(
                            [
                                'cours_id' => $cour->id,
                                'type' => $type,
                                'intitule' => $intitule,
                                'date' => $date
                            ],
                            [
                                'note_maximale' => $config['note_maximale']
                            ]
                        );

                        $evaluationsCreees++;
                    }
                }
            }

            $this->command->info("Évaluations créées pour le cours: {$cour->nom}");
        }

        $this->command->info("Total évaluations créées: {$evaluationsCreees}");
    }

    /**
     * Génère une date appropriée dans la période selon le type d'évaluation.
     *
     * Les fenêtres étant disjointes d'une période à l'autre, la date suffit
     * ensuite à rattacher l'évaluation à son trimestre (cf. NoteSeeder).
     */
    private function generateDate(Carbon $debut, Carbon $fin, string $type, int $numero): string
    {
        $duree = $debut->diffInSeconds($fin);

        // Positionner les évaluations de manière logique dans le trimestre
        $ratio = match ($type) {
            // Interrogations au début et au milieu du trimestre
            'interrogation' => $numero === 1 ? 0.2 : 0.5,
            // Devoir au milieu du trimestre
            'devoir' => 0.6,
            // Composition à la fin du trimestre
            'composition' => 0.9,
            default => 0.5,
        };

        $date = $debut->copy()->addSeconds((int) round($duree * $ratio));

        return CalendrierScolaire::jourOuvre($date)->format('Y-m-d');
    }
}
