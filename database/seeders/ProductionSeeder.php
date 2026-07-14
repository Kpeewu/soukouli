<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seed uniquement les donnees systeme/reference necessaires a une nouvelle
     * installation client : roles/permissions, annee scolaire courante, cycles
     * et examens officiels togolais, matieres de base, promotions/trimestres
     * de l'annee courante, parametres par defaut, et un compte administrateur.
     *
     * Volontairement distinct de DatabaseSeeder (donnees de demo/dev : ~750
     * eleves fictifs, professeurs fictifs, notes fictives, et des comptes a
     * mots de passe fixes) qui ne doit jamais tourner sur la base de donnees
     * d'un client.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AnneeScolaireSeeder::class,
            CycleSeeder::class,
            ExamenOfficielSeeder::class,
            MatiereSeeder::class,
            PromotionSeeder::class,
            SettingsSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
