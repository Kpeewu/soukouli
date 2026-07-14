<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cycle;
use App\Models\ExamenOfficiel;

class ExamenOfficielSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $examens = [
            [
                'nom' => 'CEPD',
                'code' => 'CEPD',
                'cycle_code' => Cycle::PRIMAIRE,
                'niveau_requis' => 'CM2',
                'description' => 'Certificat d\'Études du Premier Degré - Examen de fin de cycle primaire'
            ],
            [
                'nom' => 'BEPC',
                'code' => 'BEPC',
                'cycle_code' => Cycle::COLLEGE,
                'niveau_requis' => '3ème',
                'description' => 'Brevet d\'Études du Premier Cycle - Examen de fin de collège'
            ],
            [
                'nom' => 'BAC1',
                'code' => 'BAC1',
                'cycle_code' => Cycle::LYCEE,
                'niveau_requis' => '1ere',
                'description' => 'Baccalauréat Première Partie - Examen de passage en Terminale'
            ],
            [
                'nom' => 'BAC2',
                'code' => 'BAC2',
                'cycle_code' => Cycle::LYCEE,
                'niveau_requis' => 'Tle',
                'description' => 'Baccalauréat Deuxième Partie - Examen de fin de cycle secondaire'
            ],
        ];

        foreach ($examens as $examenData) {
            $cycle = Cycle::where('code', $examenData['cycle_code'])->first();

            if ($cycle) {
                ExamenOfficiel::updateOrCreate(
                    ['code' => $examenData['code']],
                    [
                        'nom' => $examenData['nom'],
                        'code' => $examenData['code'],
                        'cycle_id' => $cycle->id,
                        'niveau_requis' => $examenData['niveau_requis'],
                        'description' => $examenData['description']
                    ]
                );
            }
        }
    }
}
