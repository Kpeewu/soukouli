<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Eleve;
use App\Models\ExamenOfficiel;
use App\Models\InscriptionExamen;
use App\Models\Promotion;
use App\Models\SessionExamen;
use Database\Seeders\Support\CalendrierScolaire;
use Database\Seeders\Support\ProfilEleve;
use Illuminate\Database\Seeder;

/**
 * Sessions d'examens officiels togolais, inscriptions et resultats.
 */
class ExamenSeeder extends Seeder
{
    /**
     * Une session par examen.
     *
     * Les statuts sont FORCES plutot que derives de la date du jour : sinon,
     * selon le moment ou la demo est rechargee, les quatre sessions
     * partageraient le meme statut et l'ecran perdrait tout interet. Ici on
     * montre toujours une session terminee (avec resultats), une en cours et
     * une programmee.
     */
    private array $sessions = [
        'CEPD' => ['debut' => '06-10', 'fin' => '06-12', 'statut' => 'termine'],
        'BEPC' => ['debut' => '06-24', 'fin' => '06-27', 'statut' => 'termine'],
        'BAC1' => ['debut' => '06-17', 'fin' => '06-19', 'statut' => 'en_cours'],
        'BAC2' => ['debut' => '07-01', 'fin' => '07-05', 'statut' => 'programme'],
    ];

    private array $centres = [
        'Centre de Lome-Golfe',
        'Centre de Sokode',
        'Centre de Kara',
        'Centre de Kpalime',
    ];

    public function run(): void
    {
        $annee = AnneeScolaire::where('courant', true)->first();

        if (!$annee) {
            $this->command->error('Aucune annee scolaire courante trouvee!');

            return;
        }

        $examens = ExamenOfficiel::all();

        if ($examens->isEmpty()) {
            $this->command->warn('Aucun examen officiel. Executez d\'abord ExamenOfficielSeeder.');

            return;
        }

        // Les epreuves ont lieu en fin d'annee scolaire, donc sur l'annee
        // civile suivante : "2025-2026" => juin 2026.
        $anneeEpreuves = CalendrierScolaire::anneeDebut($annee) + 1;
        $inscriptions = 0;

        foreach ($examens as $examen) {
            $config = $this->sessions[$examen->code] ?? null;

            if (!$config) {
                continue;
            }

            $session = SessionExamen::firstOrCreate(
                [
                    'examen_officiel_id' => $examen->id,
                    'annee_scolaire_id' => $annee->id,
                ],
                [
                    'date_debut' => "{$anneeEpreuves}-{$config['debut']}",
                    'date_fin' => "{$anneeEpreuves}-{$config['fin']}",
                    'statut' => $config['statut'],
                ]
            );

            $inscriptions += $this->inscrire($session, $examen, $annee, $anneeEpreuves);
        }

        $this->command->info("Sessions d'examen: {$examens->count()}, inscriptions: {$inscriptions}");
    }

    /**
     * Inscrit les eleves du niveau requis par l'examen.
     */
    private function inscrire(SessionExamen $session, ExamenOfficiel $examen, AnneeScolaire $annee, int $anneeEpreuves): int
    {
        // Le nom de la promotion porte le niveau ("CM2", "3ème", "Tle") et
        // doit correspondre exactement a examens_officiels.niveau_requis.
        $promotions = Promotion::where('annee_scolaire_id', $annee->id)
            ->where('nom', $examen->niveau_requis)
            ->pluck('id');

        if ($promotions->isEmpty()) {
            return 0;
        }

        $eleves = Eleve::whereHas('classes', fn ($q) => $q->whereIn('promotion_id', $promotions))->get();

        $centre = $this->centres[array_rand($this->centres)];
        $resultatsConnus = $session->statut === 'termine';
        $sequence = 0;
        $crees = 0;

        foreach ($eleves as $eleve) {
            $sequence++;

            $inscription = InscriptionExamen::firstOrNew([
                'session_examen_id' => $session->id,
                'eleve_id' => $eleve->id,
            ]);

            if ($inscription->exists) {
                continue;
            }

            $inscription->fill([
                'numero_inscription' => sprintf('%s-%d-%04d', $examen->code, $anneeEpreuves, $sequence),
                'centre_examen' => $centre,
            ] + $this->resultat($eleve, $resultatsConnus));

            $inscription->save();
            $crees++;
        }

        return $crees;
    }

    /**
     * Statut, moyenne et mention.
     *
     * Reproduit exactement les seuils appliques par l'ecran de saisie des
     * resultats (resources/views/examens/resultats/saisie.blade.php), y
     * compris les codes de mention stockes en base : TB, B, AB, Passable et
     * Insuffisant. Diverger ici afficherait des mentions non selectionnees
     * dans les listes deroulantes.
     */
    private function resultat(Eleve $eleve, bool $resultatsConnus): array
    {
        if (!$resultatsConnus) {
            return ['statut' => 'inscrit', 'moyenne_obtenue' => null, 'mention' => null];
        }

        // ~4% d'absents le jour de l'epreuve.
        if (ProfilEleve::cohorte($eleve->id, 'examen') < 4) {
            return ['statut' => 'absent', 'moyenne_obtenue' => null, 'mention' => null];
        }

        $facteur = ProfilEleve::facteur($eleve->id);
        $moyenne = 10.5 * $facteur + ProfilEleve::gauss(0, 1.2);
        $moyenne = round(max(4, min(18, $moyenne)) * 4) / 4;

        if ($moyenne < 10) {
            return ['statut' => 'ajourne', 'moyenne_obtenue' => $moyenne, 'mention' => 'Insuffisant'];
        }

        $mention = match (true) {
            $moyenne >= 16 => 'TB',
            $moyenne >= 14 => 'B',
            $moyenne >= 12 => 'AB',
            default => 'Passable',
        };

        return ['statut' => 'admis', 'moyenne_obtenue' => $moyenne, 'mention' => $mention];
    }
}
