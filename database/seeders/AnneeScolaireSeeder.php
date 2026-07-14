<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnneeScolaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = date('Y') - 1;
        $nextYear = $currentYear + 1;

        AnneeScolaire::firstOrCreate(
            ['annee' => "{$currentYear}-{$nextYear}"],
            ['courant' => true]
        );
    }
}
