<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GuardsDemoMode;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class DemoSeed extends Command
{
    use GuardsDemoMode;

    protected $signature = 'demo:seed {--force : Ne pas demander de confirmation}';

    protected $description = 'Charge le jeu de donnees de demonstration (necessite DEMO_MODE=true)';

    public function handle(): int
    {
        if (!$this->guardDemoMode('Charger les donnees de demonstration dans la base courante ?')) {
            return self::FAILURE;
        }

        $this->call('db:seed', [
            '--class' => DemoSeeder::class,
            '--force' => true,
        ]);

        // Le modele Setting met ses valeurs en cache 24h.
        $this->call('cache:clear');

        return self::SUCCESS;
    }
}
