<?php

namespace Database\Seeders;

use App\Models\Cours;
use App\Models\Evaluation;
use App\Models\Trimestre;
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
        $cours = Cours::with('classe.promotion.trimestres', 'matiere')->get();

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

            $trimestres = $cour->classe->promotion->trimestres;
            $matiereNom = $cour->matiere ? $cour->matiere->intitule : 'Matière';

            foreach ($trimestres as $trimestre) {
                // Créer les évaluations pour chaque type
                foreach ($this->typesEvaluation as $type => $config) {
                    $nombre = $config['nombre_par_trimestre'];

                    for ($i = 1; $i <= $nombre; $i++) {
                        // Générer l'intitulé
                        $intitule = $nombre > 1
                            ? sprintf($config['intitule_pattern'], $i) . " de {$matiereNom}"
                            : $config['intitule_pattern'] . " de {$matiereNom}";

                        // Générer une date dans la période du trimestre
                        $date = $this->generateDateInTrimestre($trimestre, $type, $i);

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
     * Génère une date appropriée dans le trimestre selon le type d'évaluation
     */
    private function generateDateInTrimestre(Trimestre $trimestre, string $type, int $numero): string
    {
        $debut = $trimestre->date_debut ? strtotime($trimestre->date_debut) : strtotime('2024-09-01');
        $fin = $trimestre->date_fin ? strtotime($trimestre->date_fin) : strtotime('2024-12-15');

        $duree = $fin - $debut;

        // Positionner les évaluations de manière logique dans le trimestre
        switch ($type) {
            case 'interrogation':
                // Interrogations au début et au milieu du trimestre
                if ($numero === 1) {
                    $offset = $duree * 0.2; // 20% du trimestre
                } else {
                    $offset = $duree * 0.5; // 50% du trimestre
                }
                break;

            case 'devoir':
                // Devoir au milieu du trimestre
                $offset = $duree * 0.6; // 60% du trimestre
                break;

            case 'composition':
                // Composition à la fin du trimestre
                $offset = $duree * 0.9; // 90% du trimestre
                break;

            default:
                $offset = $duree * 0.5;
        }

        $timestamp = $debut + $offset;

        // Éviter les weekends
        $dayOfWeek = date('N', $timestamp);
        if ($dayOfWeek == 6) $timestamp -= 86400; // Samedi -> Vendredi
        if ($dayOfWeek == 7) $timestamp -= 2 * 86400; // Dimanche -> Vendredi

        return date('Y-m-d', $timestamp);
    }
}
