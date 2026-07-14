<?php

namespace App\Console\Commands;

use App\Services\PassageElevesService;
use Illuminate\Console\Command;
use RuntimeException;

class PassageEleves extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eleves:passage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Declenche le passage des eleves en annee superieure';

    public function __construct(private PassageElevesService $service)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $stats = $this->service->executerPassageComplet();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return;
        }

        $this->info('Passage des eleves pour l\'annee ' . $stats['nouvelle_annee']->annee);

        foreach ($stats['erreurs'] as $erreur) {
            $this->error("Erreur pour l'eleve {$erreur}");
        }

        $this->newLine();
        $this->info('=== Statistiques du passage ===');
        $this->line("Eleves passes en classe superieure: {$stats['nb_passes']}");
        $this->line("Eleves redoublants: {$stats['nb_redoublants']}");
        $this->line("Eleves diplomes (fin de scolarite): {$stats['nb_diplomes']}");
        if ($stats['nb_erreurs'] > 0) {
            $this->error("Erreurs rencontrees: {$stats['nb_erreurs']}");
        }
    }
}
