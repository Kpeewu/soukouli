<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Matiere;
use App\Models\Professeur;
use App\Models\Promotion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoursSeeder extends Seeder
{
    /**
     * Coefficients par matiere et cycle
     */
    private array $coefficients = [
        'MATERNELLE' => [
            'Lecture' => 2,
            'Ecriture' => 2,
            'Calcul' => 2,
            'Arts Plastiques' => 1,
            'Musique' => 1,
            'Education Physique et Sportive' => 1
        ],
        'PRIMAIRE' => [
            'Francais' => 4,
            'Mathematiques' => 4,
            'Eveil Scientifique' => 2,
            'Histoire-Geographie' => 2,
            'Education Civique et Morale' => 1,
            'Education Physique et Sportive' => 1,
            'Arts Plastiques' => 1,
            'Musique' => 1
        ],
        'COLLEGE' => [
            'Francais' => 4,
            'Mathematiques' => 4,
            'Anglais' => 3,
            'Physique-Chimie' => 3,
            'Sciences de la Vie et de la Terre' => 3,
            'Histoire-Geographie' => 3,
            'Education Civique et Morale' => 2,
            'Education Physique et Sportive' => 2
        ],
        'LYCEE' => [
            'Francais' => 4,
            'Mathematiques' => 5,
            'Anglais' => 3,
            'Physique-Chimie' => 4,
            'Sciences de la Vie et de la Terre' => 3,
            'Histoire-Geographie' => 3,
            'Philosophie' => 4,
            'Education Physique et Sportive' => 2
        ]
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anneeScolaire = AnneeScolaire::where('courant', true)->first();

        if (!$anneeScolaire) {
            $this->command->error('Aucune annee scolaire courante trouvee!');
            return;
        }

        // Recuperer toutes les classes avec leurs promotions et cycles
        $classes = Classe::with('promotion.cycle', 'promotion.matieres')->get();

        foreach ($classes as $classe) {
            if (!$classe->promotion || !$classe->promotion->cycle) {
                continue;
            }

            $cycleCode = $classe->promotion->cycle->code;
            $matieres = $classe->promotion->matieres;

            // Recuperer les professeurs du cycle
            $professeurs = Professeur::where('cycle_id', $classe->promotion->cycle_id)->get();

            if ($professeurs->isEmpty()) {
                $this->command->warn("Aucun professeur pour le cycle: {$classe->promotion->cycle->nom}");
                continue;
            }

            // Creer un cours par matiere
            $profIndex = 0;
            foreach ($matieres as $matiere) {
                // Assigner un professeur (rotation)
                $professeur = $professeurs[$profIndex % $professeurs->count()];
                $profIndex++;

                // Recuperer le coefficient
                $coefficient = isset($this->coefficients[$cycleCode][$matiere->intitule])
                    ? $this->coefficients[$cycleCode][$matiere->intitule]
                    : 2;

                // Creer le cours
                Cours::firstOrCreate(
                    [
                        'classe_id' => $classe->id,
                        'matiere_id' => $matiere->id
                    ],
                    [
                        'nom' => $matiere->intitule,
                        'coefficient' => $coefficient,
                        'professeur_id' => $professeur->id
                    ]
                );
            }

            $this->command->info("Cours crees pour la classe: {$classe->nom}");
        }
    }
}
