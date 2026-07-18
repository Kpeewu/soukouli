<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\ConfigurationFrais;
use App\Models\Eleve;
use App\Models\User;
use App\Services\ComptabiliteService;
use Carbon\Carbon;
use Database\Seeders\Support\ProfilEleve;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Encaissements de l'annee courante.
 *
 * Repartit les eleves en quatre cohortes pour que le tableau de bord
 * comptable, le rapport financier et l'ecran des impayes affichent tous
 * des chiffres parlants plutot qu'un taux de recouvrement de 0% ou 100%.
 */
class PaiementSeeder extends Seeder
{
    /** Bornes hautes des cohortes (0-99) et part des frais reglee. */
    private const COHORTE_SOLDE = 34;          // 35% : tout est paye
    private const COHORTE_PARTIEL_HAUT = 64;   // 30% : scolarite presque soldee
    private const COHORTE_PARTIEL_BAS = 84;    // 20% : 1ere tranche seulement
                                               // 15% restants : aucun paiement

    private array $modes = [
        ['mode' => 'especes', 'poids' => 60],
        ['mode' => 'mobile_money', 'poids' => 30],
        ['mode' => 'virement', 'poids' => 10],
    ];

    public function run(ComptabiliteService $comptabilite): void
    {
        $annee = AnneeScolaire::where('courant', true)->first();

        if (!$annee) {
            $this->command->error('Aucune annee scolaire courante trouvee!');

            return;
        }

        $comptables = $this->comptablesParCycle();

        if ($comptables->isEmpty()) {
            $this->command->warn('Aucun comptable trouve. Executez d\'abord UserSeeder.');

            return;
        }

        // Tous les tarifs de l'annee, indexes par cycle : evite d'appeler
        // Eleve::getTotalFrais() dans la boucle (3-4 requetes lourdes par
        // appel). Ces methodes restent l'outil de verification post-seed.
        $configsParCycle = ConfigurationFrais::with('tranches', 'typeFrais')
            ->where('annee_scolaire_id', $annee->id)
            ->where('actif', true)
            ->get()
            ->groupBy('cycle_id');

        if ($configsParCycle->isEmpty()) {
            $this->command->warn('Aucun tarif. Executez d\'abord ConfigurationFraisSeeder.');

            return;
        }

        $eleves = Eleve::with('classes.promotion.cycle')->get();
        $paiements = 0;
        $annules = 0;

        DB::connection()->disableQueryLog();

        foreach ($eleves as $eleve) {
            $classe = $eleve->getClasseActuelle();
            $promotion = $classe?->promotion;
            $cycle = $promotion?->cycle;

            if (!$promotion || !$cycle) {
                continue;
            }

            $comptable = $comptables->get($cycle->code) ?? $comptables->get('general');

            if (!$comptable) {
                continue;
            }

            // Memes deux branches que Eleve::getTotalFrais() : les frais du
            // cycle (niveau null) plus ceux du niveau de l'eleve.
            $configs = ($configsParCycle[$cycle->id] ?? collect())
                ->filter(fn ($c) => is_null($c->niveau) || $c->niveau === $promotion->nom);

            $cohorte = ProfilEleve::cohorte($eleve->id, 'paiement');

            foreach ($configs as $config) {
                $paiements += $this->reglerFrais(
                    $comptabilite,
                    $eleve,
                    $config,
                    $cohorte,
                    $annee,
                    $comptable,
                    $annules
                );
            }
        }

        $this->command->info("Paiements crees: {$paiements} (dont {$annules} annules)");
    }

    /**
     * Regle un tarif pour un eleve, selon sa cohorte.
     *
     * @return int Nombre de paiements crees.
     */
    private function reglerFrais(
        ComptabiliteService $comptabilite,
        Eleve $eleve,
        ConfigurationFrais $config,
        int $cohorte,
        AnneeScolaire $annee,
        User $comptable,
        int &$annules
    ): int {
        // Cohorte des impayes : rien du tout.
        if ($cohorte > self::COHORTE_PARTIEL_BAS) {
            return 0;
        }

        $libelle = $config->typeFrais?->nom ?? 'Frais de scolarite';
        $tranches = $config->tranches->sortBy('numero')->values();

        // Tarif echelonne (la scolarite) : on paie un prefixe des tranches.
        if ($tranches->isNotEmpty()) {
            $aPayer = match (true) {
                $cohorte <= self::COHORTE_SOLDE => $tranches->count(),
                $cohorte <= self::COHORTE_PARTIEL_HAUT => max(1, $tranches->count() - 1),
                default => 1,
            };

            $crees = 0;

            foreach ($tranches->take($aPayer) as $tranche) {
                $this->encaisser($comptabilite, [
                    'eleve_id' => $eleve->id,
                    'configuration_frais_id' => $config->id,
                    'tranche_paiement_id' => $tranche->id,
                    'montant' => $tranche->montant,
                    'motif' => $libelle . ' - ' . $tranche->nom,
                    'annee_scolaire_id' => $annee->id,
                    // Regle peu apres l'echeance, jamais dans le futur.
                    'date_paiement' => $this->dateReglement($tranche->date_limite),
                ], $comptable, $annules);

                $crees++;
            }

            return $crees;
        }

        // Tarif non echelonne : regle en une fois, et seulement par les
        // cohortes les plus solvables (sinon tout le monde solderait les
        // frais annexes et le rapport par type de frais serait plat).
        if ($cohorte > self::COHORTE_PARTIEL_HAUT) {
            return 0;
        }

        $obligatoire = (bool) ($config->typeFrais?->obligatoire);

        if (!$obligatoire && $cohorte > self::COHORTE_SOLDE) {
            return 0;
        }

        $this->encaisser($comptabilite, [
            'eleve_id' => $eleve->id,
            'configuration_frais_id' => $config->id,
            'montant' => $config->montant,
            'motif' => $libelle,
            'annee_scolaire_id' => $annee->id,
            'date_paiement' => $this->dateReglement(null),
        ], $comptable, $annules);

        return 1;
    }

    /**
     * Passe par le service applicatif : il cree le paiement ET le recu dans
     * une transaction. C'est le chemin de code que la demo doit exercer.
     */
    private function encaisser(
        ComptabiliteService $comptabilite,
        array $data,
        User $comptable,
        int &$annules
    ): void {
        $mode = $this->modeAleatoire();

        $paiement = $comptabilite->enregistrerPaiement($data + [
            'mode_paiement' => $mode,
            'reference' => $this->reference($mode),
        ], $comptable);

        // ~2% d'encaissements annules, pour que l'ecran d'annulation et le
        // scope Paiement::valide() aient de la matiere.
        if (rand(1, 100) <= 2) {
            $paiement->update(['annule' => true]);
            $paiement->recu?->update([
                'annule' => true,
                'motif_annulation' => 'Erreur de saisie du montant',
            ]);
            $annules++;
        }
    }

    /**
     * Date d'encaissement : quelques jours apres l'echeance, plafonnee a
     * aujourd'hui.
     *
     * On antidate uniquement `date_paiement`, JAMAIS `created_at` :
     * Recu::genererNumero() reconstruit la sequence avec
     * whereYear('created_at', date('Y')), et un recu date de l'annee civile
     * precedente ferait repartir le compteur a 1, violant l'unique sur
     * recus.numero.
     *
     * Effet de bord assume : getDashboardStats() compte les encaissements du
     * jour sur created_at, donc les tuiles du tableau de bord seront
     * remplies. C'est souhaitable pour une demo.
     */
    private function dateReglement(?Carbon $echeance): Carbon
    {
        $date = $echeance
            ? $echeance->copy()->subDays(rand(0, 20))
            : Carbon::today()->subDays(rand(30, 250));

        return $date->isFuture() ? Carbon::today() : $date;
    }

    private function modeAleatoire(): string
    {
        $tirage = rand(1, 100);
        $cumul = 0;

        foreach ($this->modes as $mode) {
            $cumul += $mode['poids'];

            if ($tirage <= $cumul) {
                return $mode['mode'];
            }
        }

        return 'especes';
    }

    /**
     * Les especes n'ont pas de reference (l'UI ne l'exige que pour les
     * autres modes).
     */
    private function reference(string $mode): ?string
    {
        return match ($mode) {
            'mobile_money' => (rand(0, 1) ? 'TMONEY-' : 'FLOOZ-') . rand(1000000, 9999999),
            'virement' => 'VIR-' . rand(100000, 999999),
            default => null,
        };
    }

    /**
     * Un comptable par cycle, indexe par code de cycle, plus 'general'.
     */
    private function comptablesParCycle(): \Illuminate\Support\Collection
    {
        $comptables = collect();

        foreach (['MATERNELLE', 'PRIMAIRE', 'COLLEGE', 'LYCEE'] as $code) {
            $user = User::where('username', 'comptable.' . strtolower($code))->first();

            if ($user) {
                $comptables->put($code, $user);
            }
        }

        $general = User::where('username', 'comptable.general')->first();

        if ($general) {
            $comptables->put('general', $general);
        }

        return $comptables;
    }
}
