<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('=== Demarrage du seeding de la base de donnees ===');

        // 1. Donnees de base
        $this->command->info('1. Creation de l\'annee scolaire...');
        $this->call(AnneeScolaireSeeder::class);

         // 3. Roles et permissions (apres les cycles)
        $this->command->info('5. Creation des roles et permissions...');
        $this->call(RolesAndPermissionsSeeder::class);

        $this->command->info('2. Creation de l\'utilisateur admin...');
        $this->call(UserSeeder::class);

        // 2. Cycles et examens officiels (systeme togolais)
        $this->command->info('3. Creation des cycles...');
        $this->call(CycleSeeder::class);

        $this->command->info('4. Creation des examens officiels...');
        $this->call(ExamenOfficielSeeder::class);

       

        // 4. Matieres
        $this->command->info('6. Creation des matieres...');
        $this->call(MatiereSeeder::class);

        // 5. Professeurs (par cycle)
        $this->command->info('7. Creation des professeurs...');
        $this->call(ProfesseurSeeder::class);

        // 6. Promotions avec trimestres et liaison aux matieres
        $this->command->info('8. Creation des promotions et trimestres...');
        $this->call(PromotionSeeder::class);

        // 7. Classes (2 par promotion: A et B)
        $this->command->info('9. Creation des classes...');
        $this->call(ClasseSeeder::class);

        // 8. Cours (liaison classe-matiere-professeur)
        $this->command->info('10. Creation des cours...');
        $this->call(CoursSeeder::class);

        // 9. Eleves (20-25 par classe)
        $this->command->info('11. Creation des eleves (20-25 par classe)...');
        $this->call(EleveSeeder::class);

        // 10. Evaluations (interrogations, devoirs, compositions)
        $this->command->info('12. Creation des evaluations...');
        $this->call(EvaluationSeeder::class);

        // 11. Notes pour chaque eleve
        $this->command->info('13. Creation des notes...');
        $this->call(NoteSeeder::class);

         // 14. Paramètres de l'application
        $this->command->info('14. Initialisation des paramètres...');
        $this->call(SettingsSeeder::class);

        $this->command->info('=== Seeding termine avec succes! ===');

        $this->command->info('');
        $this->command->info('Resume:');
        $this->command->info('- 4 cycles (Maternelle, Primaire, College, Lycee)');
        $this->command->info('- 15 promotions (niveaux)');
        $this->command->info('- 30 classes (2 par promotion)');
        $this->command->info('- ~25 professeurs');
        $this->command->info('- ~750 eleves (20-25 par classe)');
        $this->command->info('- Evaluations (2 interros + 1 devoir + 1 compo par trimestre/cours)');
        $this->command->info('- Notes pour chaque eleve/evaluation');
    }
}
