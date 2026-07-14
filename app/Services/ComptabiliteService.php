<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\ConfigurationFrais;
use App\Models\Cycle;
use App\Models\Eleve;
use App\Models\Paiement;
use App\Models\Recu;
use App\Models\TranchePaiement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ComptabiliteService
{
    /**
     * Retourne les frais applicables a un eleve avec leur statut de paiement
     */
    public function getFraisEleve(Eleve $eleve): array
    {
        return $eleve->getFraisAvecStatut();
    }

    /**
     * Enregistre un paiement et genere automatiquement le recu
     */
    public function enregistrerPaiement(array $data, User $comptable): Paiement
    {
        return DB::transaction(function () use ($data, $comptable) {
            // Creer le paiement
            $paiement = Paiement::create([
                'eleve_id' => $data['eleve_id'],
                'configuration_frais_id' => $data['configuration_frais_id'],
                'tranche_paiement_id' => $data['tranche_paiement_id'] ?? null,
                'montant' => $data['montant'],
                'mode_paiement' => $data['mode_paiement'] ?? 'especes',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'date_paiement' => $data['date_paiement'] ?? now(),
                'annee_scolaire_id' => $data['annee_scolaire_id'],
                'comptable_id' => $comptable->id,
                'motif' => $data['motif'] ?? 'Paiement frais de scolarite',
                'methode' => $data['mode_paiement'] ?? 'especes',
            ]);

            // Generer le recu
            $recu = Recu::create([
                'numero' => Recu::genererNumero(),
                'paiement_id' => $paiement->id,
                'comptable_id' => $comptable->id,
                'date_emission' => now(),
            ]);

            return $paiement->load('recu', 'eleve', 'configurationFrais.typeFrais');
        });
    }

    /**
     * Retourne la liste des eleves en retard de paiement
     */
    public function getElevesEnRetard(?Cycle $cycle = null, ?AnneeScolaire $anneeScolaire = null): Collection
    {
        $anneeScolaire = $anneeScolaire ?? AnneeScolaire::getAnneeScolaireActive();

        if (!$anneeScolaire) {
            return collect();
        }

        // Recuperer les tranches en retard
        $tranchesEnRetard = TranchePaiement::with('configurationFrais.cycle')
            ->whereHas('configurationFrais', function ($q) use ($anneeScolaire, $cycle) {
                $q->where('annee_scolaire_id', $anneeScolaire->id)
                    ->where('actif', true);

                if ($cycle) {
                    $q->where('cycle_id', $cycle->id);
                }
            })
            ->where('date_limite', '<', now())
            ->get();

        if ($tranchesEnRetard->isEmpty()) {
            return collect();
        }

        // Recuperer les eleves concernes
        $elevesEnRetard = collect();

        $eleves = Eleve::with(['classes.promotion.cycle', 'paiements'])
            ->whereHas('classes.promotion', function ($q) use ($anneeScolaire, $cycle) {
                $q->where('annee_scolaire_id', $anneeScolaire->id);

                if ($cycle) {
                    $q->where('cycle_id', $cycle->id);
                }
            })
            ->get();

        foreach ($eleves as $eleve) {
            $solde = $eleve->getSoldeRestant();
            if ($solde > 0) {
                $elevesEnRetard->push([
                    'eleve' => $eleve,
                    'solde' => $solde,
                    'statut' => $eleve->getStatutPaiement(),
                    'classe' => $eleve->getClasseActuelle(),
                ]);
            }
        }

        return $elevesEnRetard->sortByDesc('solde');
    }

    /**
     * Genere un rapport financier
     */
    public function getRapportFinancier(AnneeScolaire $anneeScolaire, ?Cycle $cycle = null): array
    {
        $query = ConfigurationFrais::with(['typeFrais', 'cycle', 'paiements' => function ($q) {
                $q->where('annule', false);
            }])
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->where('actif', true);

        if ($cycle) {
            $query->where('cycle_id', $cycle->id);
        }

        $configurations = $query->get();

        // Statistiques globales
        $totalAttendu = 0;
        $totalRecu = 0;

        // Statistiques par type de frais
        $parTypeFrais = [];

        // Statistiques par cycle
        $parCycle = [];

        foreach ($configurations as $config) {
            // Compter le nombre d'eleves concernes
            $nombreEleves = $this->getNombreElevesParConfig($config, $anneeScolaire);
            $montantAttendu = $config->montant * $nombreEleves;
            $montantRecu = $config->paiements->sum('montant');

            $totalAttendu += $montantAttendu;
            $totalRecu += $montantRecu;

            // Par type de frais
            $typeFraisCode = $config->typeFrais->code;
            if (!isset($parTypeFrais[$typeFraisCode])) {
                $parTypeFrais[$typeFraisCode] = [
                    'type_frais' => $config->typeFrais,
                    'montant_attendu' => 0,
                    'montant_recu' => 0,
                ];
            }
            $parTypeFrais[$typeFraisCode]['montant_attendu'] += $montantAttendu;
            $parTypeFrais[$typeFraisCode]['montant_recu'] += $montantRecu;

            // Par cycle
            $cycleCode = $config->cycle->code;
            if (!isset($parCycle[$cycleCode])) {
                $parCycle[$cycleCode] = [
                    'cycle' => $config->cycle,
                    'montant_attendu' => 0,
                    'montant_recu' => 0,
                ];
            }
            $parCycle[$cycleCode]['montant_attendu'] += $montantAttendu;
            $parCycle[$cycleCode]['montant_recu'] += $montantRecu;
        }

        return [
            'annee_scolaire' => $anneeScolaire,
            'cycle' => $cycle,
            'total_attendu' => $totalAttendu,
            'total_recu' => $totalRecu,
            'solde_restant' => $totalAttendu - $totalRecu,
            'taux_recouvrement' => $totalAttendu > 0 ? round(($totalRecu / $totalAttendu) * 100, 2) : 0,
            'par_type_frais' => array_values($parTypeFrais),
            'par_cycle' => array_values($parCycle),
        ];
    }

    /**
     * Retourne le nombre d'eleves concernes par une configuration de frais
     */
    private function getNombreElevesParConfig(ConfigurationFrais $config, AnneeScolaire $anneeScolaire): int
    {
        return Eleve::whereHas('classes.promotion', function ($q) use ($config, $anneeScolaire) {
            $q->where('annee_scolaire_id', $anneeScolaire->id)
                ->where('cycle_id', $config->cycle_id);

            if ($config->niveau) {
                $q->where('nom', $config->niveau);
            }
        })->count();
    }

    /**
     * Retourne les statistiques du dashboard comptable (optimise)
     */
    public function getDashboardStats(?Cycle $cycle = null): array
    {
        $anneeScolaire = AnneeScolaire::getAnneeScolaireActive();

        if (!$anneeScolaire) {
            return [
                'total_eleves' => 0,
                'total_frais_attendu' => 0,
                'total_paiements' => 0,
                'taux_recouvrement' => 0,
                'paiements_aujourd_hui' => 0,
                'paiements_semaine' => 0,
                'eleves_soldes' => 0,
                'eleves_partiels' => 0,
                'eleves_impayes' => 0,
            ];
        }

        // Charger les configurations de frais
        $configsQuery = ConfigurationFrais::where('annee_scolaire_id', $anneeScolaire->id)
            ->where('actif', true);
        if ($cycle) {
            $configsQuery->where('cycle_id', $cycle->id);
        }
        $configurations = $configsQuery->get();
        $configIds = $configurations->pluck('id')->toArray();

        // Calculer totaux
        $totalPaiements = Paiement::whereIn('configuration_frais_id', $configIds)->valide()->sum('montant');

        // Compter les eleves
        $elevesQuery = Eleve::with(['classes' => function ($q) use ($anneeScolaire) {
                $q->whereHas('promotion', function ($p) use ($anneeScolaire) {
                    $p->where('annee_scolaire_id', $anneeScolaire->id);
                })->with('promotion');
            }])
            ->whereHas('classes.promotion', function ($q) use ($anneeScolaire, $cycle) {
                $q->where('annee_scolaire_id', $anneeScolaire->id);
                if ($cycle) {
                    $q->where('cycle_id', $cycle->id);
                }
            });

        $totalEleves = $elevesQuery->count();

        // Charger les paiements par eleve en une seule requete
        $paiementsParEleve = Paiement::whereIn('configuration_frais_id', $configIds)
            ->valide()
            ->select('eleve_id', DB::raw('SUM(montant) as total_paye'))
            ->groupBy('eleve_id')
            ->pluck('total_paye', 'eleve_id');

        // Calculer le statut des eleves de maniere optimisee
        $elevesSoldes = 0;
        $elevesPartiels = 0;
        $elevesImpayes = 0;
        $totalFraisAttendu = 0;

        $eleves = $elevesQuery->get();

        foreach ($eleves as $eleve) {
            $classe = $eleve->classes->first();
            if (!$classe || !$classe->promotion) {
                $elevesImpayes++;
                continue;
            }

            $cycleId = $classe->promotion->cycle_id;
            $niveau = $classe->promotion->nom;

            // Calculer le total des frais pour cet eleve
            $totalFrais = $configurations->filter(function ($config) use ($cycleId, $niveau) {
                return $config->cycle_id == $cycleId
                    && (is_null($config->niveau) || $config->niveau === $niveau);
            })->sum('montant');

            $totalFraisAttendu += $totalFrais;

            $totalPaye = (float) ($paiementsParEleve[$eleve->id] ?? 0);
            $solde = $totalFrais - $totalPaye;

            if ($totalFrais <= 0 || $solde <= 0) {
                $elevesSoldes++;
            } elseif ($totalPaye > 0) {
                $elevesPartiels++;
            } else {
                $elevesImpayes++;
            }
        }

        // Paiements recents
        $paiementsAujourdhui = Paiement::whereIn('configuration_frais_id', $configIds)
            ->valide()
            ->whereDate('created_at', today())
            ->sum('montant');

        $paiementsSemaine = Paiement::whereIn('configuration_frais_id', $configIds)
            ->valide()
            ->where('created_at', '>=', now()->startOfWeek())
            ->sum('montant');

        $tauxRecouvrement = $totalFraisAttendu > 0 ? round(($totalPaiements / $totalFraisAttendu) * 100, 2) : 0;

        return [
            'total_eleves' => $totalEleves,
            'total_frais_attendu' => $totalFraisAttendu,
            'total_paiements' => $totalPaiements,
            'taux_recouvrement' => $tauxRecouvrement,
            'paiements_aujourd_hui' => $paiementsAujourdhui,
            'paiements_semaine' => $paiementsSemaine,
            'eleves_soldes' => $elevesSoldes,
            'eleves_partiels' => $elevesPartiels,
            'eleves_impayes' => $elevesImpayes,
        ];
    }

    /**
     * Retourne les derniers paiements
     */
    public function getDerniersPaiements(int $limit = 10, ?Cycle $cycle = null): Collection
    {
        $anneeScolaire = AnneeScolaire::getAnneeScolaireActive();

        if (!$anneeScolaire) {
            return collect();
        }

        $query = Paiement::with(['eleve', 'configurationFrais.typeFrais', 'comptable', 'recu'])
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($cycle) {
            $query->whereHas('configurationFrais', function ($q) use ($cycle) {
                $q->where('cycle_id', $cycle->id);
            });
        }

        return $query->get();
    }
}
