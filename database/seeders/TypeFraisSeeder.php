<?php

namespace Database\Seeders;

use App\Models\TypeFrais;
use Illuminate\Database\Seeder;

class TypeFraisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typesFrais = [
            [
                'nom' => 'Frais de scolarité',
                'code' => 'SCOLARITE',
                'description' => 'Frais annuels de scolarité',
                'obligatoire' => true,
                'actif' => true,
            ],
            [
                'nom' => 'Frais d\'inscription',
                'code' => 'INSCRIPTION',
                'description' => 'Frais d\'inscription annuels',
                'obligatoire' => true,
                'actif' => true,
            ],
            [
                'nom' => 'Frais d\'examen',
                'code' => 'EXAMEN',
                'description' => 'Frais pour les examens officiels (CEPD, BEPC, BAC)',
                'obligatoire' => false,
                'actif' => true,
            ],
            [
                'nom' => 'Frais de transport',
                'code' => 'TRANSPORT',
                'description' => 'Frais de transport scolaire',
                'obligatoire' => false,
                'actif' => true,
            ],
            [
                'nom' => 'Frais de cantine',
                'code' => 'CANTINE',
                'description' => 'Frais de cantine scolaire',
                'obligatoire' => false,
                'actif' => true,
            ],
            [
                'nom' => 'Frais de documentation',
                'code' => 'DOCUMENTATION',
                'description' => 'Frais pour les manuels et fournitures scolaires',
                'obligatoire' => false,
                'actif' => true,
            ],
            [
                'nom' => 'Frais d\'assurance',
                'code' => 'ASSURANCE',
                'description' => 'Assurance scolaire annuelle',
                'obligatoire' => false,
                'actif' => true,
            ],
        ];

        foreach ($typesFrais as $typeFrais) {
            TypeFrais::firstOrCreate(
                ['code' => $typeFrais['code']],
                $typeFrais
            );
        }
    }
}
