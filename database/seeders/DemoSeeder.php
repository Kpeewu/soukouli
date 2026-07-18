<?php

namespace Database\Seeders;

use App\Models\Eleve;
use App\Models\Note;
use App\Models\Paiement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Jeu de donnees complet pour une instance de demonstration.
 *
 * Distinct de DatabaseSeeder (seeder de developpement, volume complet, appele
 * par `php artisan db:seed`) et de ProductionSeeder (donnees de reference
 * seules, appele a l'installation chez un client).
 *
 * Contrairement a DatabaseSeeder, celui-ci peuple aussi la comptabilite,
 * l'assiduite et les examens officiels : sans eux, les roles comptable et
 * surveillant n'auraient rien a montrer.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Seule barriere contre un `db:seed --class=DemoSeeder` lance par
        // erreur sur une base client.
        if (!config('demo.enabled')) {
            throw new RuntimeException(
                'DemoSeeder refuse de s\'executer : DEMO_MODE n\'est pas actif.'
            );
        }

        // Des dizaines de milliers d'inserts : sans cela le query log seul
        // consomme plusieurs centaines de Mo.
        DB::connection()->disableQueryLog();

        $this->command->info('=== Chargement des donnees de demonstration ===');

        // --- Socle : annee, roles, comptes, referentiel togolais ---------
        $this->command->info('1/6  Annee scolaire, roles et comptes...');
        $this->call(AnneeScolaireSeeder::class);
        // Avant UserSeeder : celui-ci fait assignRole().
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UserSeeder::class);

        $this->command->info('2/6  Cycles, examens officiels et matieres...');
        $this->call(CycleSeeder::class);
        $this->call(ExamenOfficielSeeder::class);
        $this->call(MatiereSeeder::class);

        // --- Structure pedagogique, en volume reduit ----------------------
        $this->command->info('3/6  Enseignants, promotions, classes et cours...');
        $this->call(ProfesseurSeeder::class);
        $this->call(PromotionSeeder::class);
        // Les parametres de callWith() sont resolus par NOM par le conteneur,
        // pas par position : des cles numeriques seraient ignorees et les
        // valeurs par defaut des seeders s'appliqueraient silencieusement.
        $this->callWith(ClasseSeeder::class, [
            'nombreClasses' => config('demo.classes_par_promotion'),
        ]);
        $this->call(CoursSeeder::class);

        $this->command->info('4/6  Eleves, evaluations et notes...');
        $this->callWith(EleveSeeder::class, [
            'elevesMin' => config('demo.eleves_min'),
            'elevesMax' => config('demo.eleves_max'),
        ]);
        $this->call(EvaluationSeeder::class);
        $this->call(NoteSeeder::class);

        // Remplit les fiches d'assiduite creees par EleveSeeder.
        $this->command->info('5/6  Absences, retards et comportement...');
        $this->call(AssiduiteSeeder::class);

        // --- Comptabilite et examens --------------------------------------
        $this->command->info('6/6  Frais, paiements, recus et sessions d\'examen...');
        $this->call(TypeFraisSeeder::class);
        $this->call(ConfigurationFraisSeeder::class);
        // Depend des comptes comptables crees par UserSeeder.
        $this->call(PaiementSeeder::class);
        $this->call(ExamenSeeder::class);

        $this->call(SettingsSeeder::class);

        // Volontairement pas d'AdminUserSeeder : il creerait un second compte
        // administrateur au mot de passe aleatoire, qui polluerait la liste
        // des comptes de demonstration affichee dans la banniere.

        $this->afficherResume();
    }

    private function afficherResume(): void
    {
        $this->command->newLine();
        $this->command->info('=== Donnees de demonstration chargees ===');

        $lignes = [
            ['Eleves', Eleve::count()],
            ['Notes', Note::count()],
            ['Paiements', Paiement::count()],
        ];

        foreach ($lignes as [$libelle, $total]) {
            $this->command->line(sprintf('  %-12s %s', $libelle, number_format($total, 0, ',', ' ')));
        }

        $this->command->newLine();
        $this->command->line('Comptes de test :');

        foreach (config('demo.credentials') as $compte) {
            $this->command->line(sprintf(
                '  %-16s %s / %s',
                $compte['role'],
                $compte['login'],
                $compte['password']
            ));
        }
    }
}
