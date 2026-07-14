<?php

namespace Database\Seeders;

use App\Models\Matiere;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MatiereSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matieres = [
            // Matieres communes
            'Mathematiques',
            'Francais',
            'Anglais',
            'Sciences de la Vie et de la Terre',
            'Physique-Chimie',
            'Histoire-Geographie',
            'Education Civique et Morale',
            'Education Physique et Sportive',
            'Arts Plastiques',
            'Musique',
            // Matieres specifiques
            'Philosophie',
            'Espagnol',
            'Allemand',
            'Informatique',
            'Economie',
            'Lecture',
            'Ecriture',
            'Calcul',
            'Eveil Scientifique',
        ];

        foreach ($matieres as $matiere) {
            Matiere::firstOrCreate(['intitule' => $matiere]);
        }
    }
}
