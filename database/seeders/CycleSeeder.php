<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cycle;

class CycleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cycles = [
            [
                'nom' => 'Maternelle',
                'code' => Cycle::MATERNELLE,
                'description' => 'Cycle maternelle (Maternelle 1, Maternelle 2)',
                'ordre' => 1,
                'supports_semestre' => false,
                'niveaux' => ['Maternelle 1', 'Maternelle 2'],
            ],
            [
                'nom' => 'Primaire',
                'code' => Cycle::PRIMAIRE,
                'description' => 'Cycle primaire (CP1, CP2, CE1, CE2, CM1, CM2) - CEPD en CM2',
                'ordre' => 2,
                'supports_semestre' => false,
                'niveaux' => ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'],
            ],
            [
                'nom' => 'College',
                'code' => Cycle::COLLEGE,
                'description' => 'Cycle college (6eme, 5eme, 4eme, 3eme) - BEPC en 3eme',
                'ordre' => 3,
                'supports_semestre' => true,
                'niveaux' => ['6ème', '5ème', '4ème', '3ème'],
            ],
            [
                'nom' => 'Lycee',
                'code' => Cycle::LYCEE,
                'description' => 'Cycle lycee (2nde, 1ere, Terminale) - BAC1 en 1ere, BAC2 en Terminale',
                'ordre' => 4,
                'supports_semestre' => true,
                'niveaux' => ['2nde', '1ere', 'Tle'],
            ],
        ];

        // Creer d'abord tous les cycles
        foreach ($cycles as $cycleData) {
            Cycle::updateOrCreate(
                ['code' => $cycleData['code']],
                [
                    'nom' => $cycleData['nom'],
                    'description' => $cycleData['description'],
                    'ordre' => $cycleData['ordre'],
                    'supports_semestre' => $cycleData['supports_semestre'],
                    'niveaux' => $cycleData['niveaux'],
                ]
            );
        }

        // Ensuite definir les cycles suivants
        $maternelle = Cycle::where('code', Cycle::MATERNELLE)->first();
        $primaire = Cycle::where('code', Cycle::PRIMAIRE)->first();
        $college = Cycle::where('code', Cycle::COLLEGE)->first();
        $lycee = Cycle::where('code', Cycle::LYCEE)->first();

        if ($maternelle && $primaire) {
            $maternelle->update(['cycle_suivant_id' => $primaire->id]);
        }
        if ($primaire && $college) {
            $primaire->update(['cycle_suivant_id' => $college->id]);
        }
        if ($college && $lycee) {
            $college->update(['cycle_suivant_id' => $lycee->id]);
        }
        // Le lycee n'a pas de cycle suivant (fin de scolarite)
    }
}
