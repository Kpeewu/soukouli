<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Cycle;
use App\Models\ExamenOfficiel;
use App\Models\Matiere;
use App\Models\Promotion;
use App\Models\Trimestre;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
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

        // Recuperer tous les cycles
        $cycles = Cycle::orderBy('ordre')->get();

        // Matieres par cycle
        $matieresParCycle = [
            'MATERNELLE' => ['Lecture', 'Ecriture', 'Calcul', 'Arts Plastiques', 'Musique', 'Education Physique et Sportive'],
            'PRIMAIRE' => ['Francais', 'Mathematiques', 'Eveil Scientifique', 'Histoire-Geographie', 'Education Civique et Morale', 'Education Physique et Sportive', 'Arts Plastiques', 'Musique'],
            'COLLEGE' => ['Francais', 'Mathematiques', 'Anglais', 'Physique-Chimie', 'Sciences de la Vie et de la Terre', 'Histoire-Geographie', 'Education Civique et Morale', 'Education Physique et Sportive'],
            'LYCEE' => ['Francais', 'Mathematiques', 'Anglais', 'Physique-Chimie', 'Sciences de la Vie et de la Terre', 'Histoire-Geographie', 'Philosophie', 'Education Physique et Sportive'],
        ];

        foreach ($cycles as $cycle) {
            $promotionNames = $cycle->getDefaultPromotions();
            $promotionsAvecExamen = $cycle->getPromotionsAvecExamen();

            foreach ($promotionNames as $promotionName) {
                // Determiner si cette promotion a un examen officiel
                $aExamen = in_array($promotionName, $promotionsAvecExamen);
                $examenOfficiel = null;

                if ($aExamen) {
                    $examenOfficiel = ExamenOfficiel::where('cycle_id', $cycle->id)
                        ->where('niveau_requis', $promotionName)
                        ->first();
                }

                // Creer la promotion
                $promotion = Promotion::firstOrCreate(
                    [
                        'nom' => $promotionName,
                        'annee_scolaire_id' => $anneeScolaire->id,
                        'cycle_id' => $cycle->id,
                    ],
                    [
                        'type_periode' => 'trimestre',
                        'a_examen_officiel' => $aExamen,
                        'examen_officiel_id' => $examenOfficiel?->id
                    ]
                );

                // Creer les trimestres si pas encore crees
                if ($promotion->trimestres()->count() === 0) {
                    $nombrePeriodes = $promotion->type_periode === 'semestre' ? 2 : 3;
                    $typePeriode = $promotion->type_periode === 'semestre' ? 'Semestre' : 'Trimestre';

                    for ($j = 1; $j <= $nombrePeriodes; $j++) {
                        Trimestre::create([
                            'intitule' => $typePeriode . ' ' . $j . ' ' . $promotionName . ' ' . $anneeScolaire->annee,
                            'promotion_id' => $promotion->id
                        ]);
                    }
                }

                // Attacher les matieres a la promotion
                if (isset($matieresParCycle[$cycle->code])) {
                    $matieres = Matiere::whereIn('intitule', $matieresParCycle[$cycle->code])->get();
                    $promotion->matieres()->syncWithoutDetaching($matieres->pluck('id'));
                }
            }

            $this->command->info("Promotions creees pour le cycle: {$cycle->nom}");
        }
    }
}
