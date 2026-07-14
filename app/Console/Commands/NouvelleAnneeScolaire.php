<?php

namespace App\Console\Commands;

use App\Services\AnneeScolaireGenerationService;
use Illuminate\Console\Command;
use RuntimeException;

class NouvelleAnneeScolaire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anneeScolaire:change';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cette commande permet de rajouter une nouvelle annee scolaire et de passer a celle-ci';

    public function __construct(private AnneeScolaireGenerationService $service)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $stats = $this->service->genererAnneeSuivante();
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Aucune année scolaire courante')) {
                $this->error($e->getMessage());
            } else {
                $this->warn($e->getMessage());
            }
            return;
        }

        $this->info('Creation de la nouvelle annee scolaire...');
        $this->line("  -> {$stats['nb_configurations_frais']} configurations de frais copiees");
        $this->line("  -> {$stats['nb_tranches']} tranches de paiement copiees");
        if ($stats['nb_sessions_examen'] > 0) {
            $this->line("  -> {$stats['nb_sessions_examen']} sessions d'examen copiees");
        }
        $this->line("  -> {$stats['nb_associations_matieres']} associations matiere-promotion copiees");
        $this->line("  -> {$stats['nb_cours']} cours crees (professeurs repris de l'annee precedente)");

        $this->info('Nouvelle annee scolaire ' . $stats['nouvelle_annee']->annee . ' creee avec succes.');
        $this->info('- Promotions et classes creees pour tous les cycles');
        $this->info('- Configurations de frais copiees');
        $this->info('- Sessions d\'examen copiees');
        $this->info('- Matieres et cours copies');
    }
}
