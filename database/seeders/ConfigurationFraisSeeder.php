<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\ConfigurationFrais;
use App\Models\Cycle;
use App\Models\TranchePaiement;
use App\Models\TypeFrais;
use Database\Seeders\Support\CalendrierScolaire;
use Illuminate\Database\Seeder;

/**
 * Tarifs et echeanciers de l'annee courante.
 *
 * Les montants sont ceux d'une ecole privee togolaise de taille moyenne,
 * exprimes en FCFA.
 */
class ConfigurationFraisSeeder extends Seeder
{
    /**
     * Montants par cycle. Cle = code du cycle, puis code du type de frais.
     * Ces frais s'appliquent a tous les niveaux du cycle (niveau = null).
     */
    private array $montants = [
        'MATERNELLE' => [
            'SCOLARITE' => 90000,
            'INSCRIPTION' => 15000,
            'DOCUMENTATION' => 10000,
            'ASSURANCE' => 3000,
            'CANTINE' => 60000,
            'TRANSPORT' => 45000,
        ],
        'PRIMAIRE' => [
            'SCOLARITE' => 120000,
            'INSCRIPTION' => 15000,
            'DOCUMENTATION' => 12000,
            'ASSURANCE' => 3000,
            'CANTINE' => 60000,
            'TRANSPORT' => 45000,
        ],
        'COLLEGE' => [
            'SCOLARITE' => 175000,
            'INSCRIPTION' => 20000,
            'DOCUMENTATION' => 18000,
            'ASSURANCE' => 3000,
            'TRANSPORT' => 50000,
        ],
        'LYCEE' => [
            'SCOLARITE' => 225000,
            'INSCRIPTION' => 25000,
            'DOCUMENTATION' => 22000,
            'ASSURANCE' => 3000,
            'TRANSPORT' => 50000,
        ],
    ];

    /**
     * Frais d'examen, rattaches a un niveau precis (niveau != null).
     * Exerce la seconde branche de Eleve::getTotalFrais().
     * La cle doit correspondre exactement au nom de la promotion.
     */
    private array $fraisExamen = [
        'PRIMAIRE' => ['CM2' => 5000],
        'COLLEGE' => ['3ème' => 12000],
        'LYCEE' => ['1ere' => 15000, 'Tle' => 18000],
    ];

    /**
     * Echeancier de la scolarite : 3 tranches 40/30/30.
     * 'limite' est un "MM-JJ" resolu sur l'annee scolaire.
     */
    private array $tranches = [
        1 => ['nom' => '1ere tranche', 'part' => 0.40, 'limite' => '10-15'],
        2 => ['nom' => '2eme tranche', 'part' => 0.30, 'limite' => '01-15'],
        3 => ['nom' => '3eme tranche', 'part' => 0.30, 'limite' => '04-15'],
    ];

    public function run(): void
    {
        $annee = AnneeScolaire::where('courant', true)->first();

        if (!$annee) {
            $this->command->error('Aucune annee scolaire courante trouvee!');

            return;
        }

        $types = TypeFrais::pluck('id', 'code');

        if ($types->isEmpty()) {
            $this->command->warn('Aucun type de frais. Executez d\'abord TypeFraisSeeder.');

            return;
        }

        $configs = 0;
        $tranches = 0;

        foreach (Cycle::all() as $cycle) {
            // Frais applicables a tout le cycle
            foreach ($this->montants[$cycle->code] ?? [] as $code => $montant) {
                if (!isset($types[$code])) {
                    continue;
                }

                $config = $this->configurer($types[$code], $cycle->id, null, $annee->id, $montant);
                $configs++;

                // Seule la scolarite est echelonnee : c'est le seul frais dont
                // le montant justifie un echeancier, et cela suffit a alimenter
                // l'ecran des impayes et des retards.
                if ($code === 'SCOLARITE') {
                    $tranches += $this->echelonner($config, $annee);
                }
            }

            // Frais d'examen, rattaches a un niveau
            foreach ($this->fraisExamen[$cycle->code] ?? [] as $niveau => $montant) {
                if (!isset($types['EXAMEN'])) {
                    continue;
                }

                $this->configurer($types['EXAMEN'], $cycle->id, $niveau, $annee->id, $montant);
                $configs++;
            }
        }

        $this->command->info("Configurations de frais creees: {$configs} (dont {$tranches} tranches)");
    }

    /**
     * Cree ou retrouve un tarif.
     *
     * L'index unique config_frais_unique ne contraint PAS les lignes dont
     * `niveau` est NULL (sémantique PostgreSQL : deux NULL ne sont jamais
     * egaux). D'ou la recherche explicite avec whereNull plutot qu'un
     * firstOrCreate, qui laisserait passer des doublons a chaque re-execution.
     */
    private function configurer(int $typeId, int $cycleId, ?string $niveau, int $anneeId, float $montant): ConfigurationFrais
    {
        $config = ConfigurationFrais::query()
            ->where('type_frais_id', $typeId)
            ->where('cycle_id', $cycleId)
            ->where('annee_scolaire_id', $anneeId)
            ->when(
                is_null($niveau),
                fn ($q) => $q->whereNull('niveau'),
                fn ($q) => $q->where('niveau', $niveau)
            )
            ->first();

        return $config ?? ConfigurationFrais::create([
            'type_frais_id' => $typeId,
            'cycle_id' => $cycleId,
            'niveau' => $niveau,
            'annee_scolaire_id' => $anneeId,
            'montant' => $montant,
            'actif' => true,
        ]);
    }

    /**
     * Decoupe un tarif en tranches.
     */
    private function echelonner(ConfigurationFrais $config, AnneeScolaire $annee): int
    {
        if ($config->tranches()->exists()) {
            return 0;
        }

        $total = (float) $config->montant;
        $anneeDebut = CalendrierScolaire::anneeDebut($annee);
        $cumul = 0.0;
        $creees = 0;

        foreach ($this->tranches as $numero => $tranche) {
            // La derniere tranche absorbe le reste : calculer chaque tranche
            // comme un pourcentage laisserait un residu d'arrondi, et le taux
            // de recouvrement plafonnerait sous 100% dans le rapport financier.
            $montant = $numero === array_key_last($this->tranches)
                ? round($total - $cumul, 2)
                : round($total * $tranche['part'], 2);

            $cumul += $montant;

            TranchePaiement::create([
                'configuration_frais_id' => $config->id,
                'nom' => $tranche['nom'],
                'numero' => $numero,
                'montant' => $montant,
                'date_limite' => $this->dateLimite($tranche['limite'], $anneeDebut),
            ]);

            $creees++;
        }

        return $creees;
    }

    /**
     * Un "MM-JJ" anterieur a aout tombe sur l'annee civile suivante.
     */
    private function dateLimite(string $mmjj, int $anneeDebut): string
    {
        $mois = (int) substr($mmjj, 0, 2);

        return ($mois >= 8 ? $anneeDebut : $anneeDebut + 1) . '-' . $mmjj;
    }
}
