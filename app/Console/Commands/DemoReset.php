<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GuardsDemoMode;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class DemoReset extends Command
{
    use GuardsDemoMode;

    protected $signature = 'demo:reset {--force : Ne pas demander de confirmation}';

    protected $description = 'Efface la base et recharge les donnees de demonstration (necessite DEMO_MODE=true)';

    public function handle(): int
    {
        if (!$this->guardDemoMode('EFFACER integralement la base et recharger la demo ?')) {
            return self::FAILURE;
        }

        // migrate:fresh plutot qu'un truncate selectif : ~25 tables liees par
        // cles etrangeres plus les tables spatie, dont l'ordre de troncature
        // se degrade silencieusement des qu'une migration s'ajoute. En prime,
        // les sequences Postgres repartent a 1, donc identifiants, matricules
        // et numeros de recu sont reproductibles d'un reset a l'autre.
        $this->call('migrate:fresh', ['--force' => true]);

        $this->call('db:seed', [
            '--class' => DemoSeeder::class,
            '--force' => true,
        ]);

        // Le modele Setting met ses valeurs en cache 24h : sans purge, la
        // demo rechargee afficherait les parametres de l'instance precedente.
        $this->call('cache:clear');

        $this->newLine();
        $this->info('Demonstration reinitialisee.');

        return self::SUCCESS;
    }
}
