<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Note: Le modele User a le cast 'password' => 'hashed', donc le mot de passe est automatiquement hashe.
     */
    public function run(): void
    {
        // Admin principal
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            ['password' => 'admin123']
        );
        $admin->assignRole('admin');
        $this->command->info('Admin cree: admin / admin123');

        // Directeur General
        $directeurGeneral = User::firstOrCreate(
            ['username' => 'directeur.general'],
            ['password' => 'general123']
        );
        $directeurGeneral->assignRole('directeur_general');
        $this->command->info('Directeur General cree: directeur.general / general123');

        // Directeur Maternelle
        $directeurMaternelle = User::firstOrCreate(
            ['username' => 'directeur.maternelle'],
            ['password' => 'maternelle123']
        );
        $directeurMaternelle->assignRole('directeur_maternelle');
        $this->command->info('Directeur Maternelle cree: directeur.maternelle / maternelle123');

        // Directeur Primaire
        $directeurPrimaire = User::firstOrCreate(
            ['username' => 'directeur.primaire'],
            ['password' => 'primaire123']
        );
        $directeurPrimaire->assignRole('directeur_primaire');
        $this->command->info('Directeur Primaire cree: directeur.primaire / primaire123');

        // Directeur College
        $directeurCollege = User::firstOrCreate(
            ['username' => 'directeur.college'],
            ['password' => 'college123']
        );
        $directeurCollege->assignRole('directeur_college');
        $this->command->info('Directeur College cree: directeur.college / college123');

        // Directeur Lycee
        $directeurLycee = User::firstOrCreate(
            ['username' => 'directeur.lycee'],
            ['password' => 'lycee123']
        );
        $directeurLycee->assignRole('directeur_lycee');
        $this->command->info('Directeur Lycee cree: directeur.lycee / lycee123');

        // Comptable General
        $comptableGeneral = User::firstOrCreate(
            ['username' => 'comptable.general'],
            ['password' => 'general123']
        );
        $comptableGeneral->assignRole('comptable_general');
        $this->command->info('Comptable General cree: comptable.general / general123');

        // Comptable Maternelle
        $comptableMaternelle = User::firstOrCreate(
            ['username' => 'comptable.maternelle'],
            ['password' => 'maternelle123']
        );
        $comptableMaternelle->assignRole('comptable_maternelle');
        $this->command->info('Comptable Maternelle cree: comptable.maternelle / maternelle123');

        // Comptable Primaire
        $comptablePrimaire = User::firstOrCreate(
            ['username' => 'comptable.primaire'],
            ['password' => 'primaire123']
        );
        $comptablePrimaire->assignRole('comptable_primaire');
        $this->command->info('Comptable Primaire cree: comptable.primaire / primaire123');

        // Comptable College
        $comptableCollege = User::firstOrCreate(
            ['username' => 'comptable.college'],
            ['password' => 'college123']
        );
        $comptableCollege->assignRole('comptable_college');
        $this->command->info('Comptable College cree: comptable.college / college123');

        // Comptable Lycee
        $comptableLycee = User::firstOrCreate(
            ['username' => 'comptable.lycee'],
            ['password' => 'lycee123']
        );
        $comptableLycee->assignRole('comptable_lycee');
        $this->command->info('Comptable Lycee cree: comptable.lycee / lycee123');

        // Secretaire General
        $secretaireGeneral = User::firstOrCreate(
            ['username' => 'secretaire.general'],
            ['password' => 'secretaire123']
        );
        $secretaireGeneral->assignRole('secretaire_general');
        $this->command->info('Secretaire General cree: secretaire.general / secretaire123');

        // Secretaire Maternelle
        $secretaireMaternelle = User::firstOrCreate(
            ['username' => 'secretaire.maternelle'],
            ['password' => 'maternelle123']
        );
        $secretaireMaternelle->assignRole('secretaire_maternelle');
        $this->command->info('Secretaire Maternelle cree: secretaire.maternelle / maternelle123');

        // Secretaire Primaire
        $secretairePrimaire = User::firstOrCreate(
            ['username' => 'secretaire.primaire'],
            ['password' => 'primaire123']
        );
        $secretairePrimaire->assignRole('secretaire_primaire');
        $this->command->info('Secretaire Primaire cree: secretaire.primaire / primaire123');

        // Secretaire College
        $secretaireCollege = User::firstOrCreate(
            ['username' => 'secretaire.college'],
            ['password' => 'college123']
        );
        $secretaireCollege->assignRole('secretaire_college');
        $this->command->info('Secretaire College cree: secretaire.college / college123');

        // Secretaire Lycee
        $secretaireLycee = User::firstOrCreate(
            ['username' => 'secretaire.lycee'],
            ['password' => 'lycee123']
        );
        $secretaireLycee->assignRole('secretaire_lycee');
        $this->command->info('Secretaire Lycee cree: secretaire.lycee / lycee123');

        // Surveillant General
        $surveillantGeneral = User::firstOrCreate(
            ['username' => 'surveillant.general'],
            ['password' => 'general123']
        );
        $surveillantGeneral->assignRole('surveillant_general');
        $this->command->info('Surveillant General cree: surveillant.general / general123');

        // Surveillant Maternelle
        $surveillantMaternelle = User::firstOrCreate(
            ['username' => 'surveillant.maternelle'],
            ['password' => 'maternelle123']
        );
        $surveillantMaternelle->assignRole('surveillant_maternelle');
        $this->command->info('Surveillant Maternelle cree: surveillant.maternelle / maternelle123');

        // Surveillant Primaire
        $surveillantPrimaire = User::firstOrCreate(
            ['username' => 'surveillant.primaire'],
            ['password' => 'primaire123']
        );
        $surveillantPrimaire->assignRole('surveillant_primaire');
        $this->command->info('Surveillant Primaire cree: surveillant.primaire / primaire123');

        // Surveillant College
        $surveillantCollege = User::firstOrCreate(
            ['username' => 'surveillant.college'],
            ['password' => 'college123']
        );
        $surveillantCollege->assignRole('surveillant_college');
        $this->command->info('Surveillant College cree: surveillant.college / college123');

        // Surveillant Lycee
        $surveillantLycee = User::firstOrCreate(
            ['username' => 'surveillant.lycee'],
            ['password' => 'lycee123']
        );
        $surveillantLycee->assignRole('surveillant_lycee');
        $this->command->info('Surveillant Lycee cree: surveillant.lycee / lycee123');

        // Professeur (sera lie a un professeur dans ProfesseurSeeder)
        $professeur = User::firstOrCreate(
            ['username' => 'professeur'],
            ['password' => 'prof123']
        );
        $professeur->assignRole('professeur');
        $this->command->info('Professeur cree: professeur / prof123');
    }
}
