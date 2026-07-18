<?php

namespace Database\Seeders;

use App\Models\Absence;
use App\Models\Assiduite;
use App\Models\Retard;
use Database\Seeders\Support\CalendrierScolaire;
use Database\Seeders\Support\ProfilEleve;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Absences, retards et comportement.
 *
 * Ne cree pas de fiches d'assiduite : EleveSeeder en cree deja une par
 * eleve et par trimestre. Ce seeder les remplit.
 *
 * Les eleves en difficulte scolaire (cf. ProfilEleve) sont plus souvent
 * absents : c'est ce qui rend la fiche d'un eleve coherente entre l'ecran
 * des notes et celui du surveillant.
 */
class AssiduiteSeeder extends Seeder
{
    private array $motifs = [
        'Maladie',
        'Raison familiale',
        'Rendez-vous medical',
        'Deces dans la famille',
        'Convocation administrative',
    ];

    /** Valeur par defaut de la colonne : celle que l'UI sait deja afficher. */
    private const NON_JUSTIFIE = 'Absence de justification';

    public function run(): void
    {
        $assiduites = Assiduite::with('trimestre.promotion.anneeScolaire', 'trimestre.promotion.trimestres')->get();

        if ($assiduites->isEmpty()) {
            $this->command->warn('Aucune fiche d\'assiduite. Executez d\'abord EleveSeeder.');

            return;
        }

        DB::connection()->disableQueryLog();

        $absences = 0;
        $retards = 0;

        foreach ($assiduites as $assiduite) {
            $promotion = $assiduite->trimestre?->promotion;
            $annee = $promotion?->anneeScolaire;

            if (!$promotion || !$annee) {
                continue;
            }

            // Rang du trimestre = son ordre de creation (cf. NoteSeeder).
            $trimestres = $promotion->trimestres->sortBy('id')->values();
            $rang = $trimestres->search(fn ($t) => $t->id === $assiduite->trimestre_id);

            if ($rang === false) {
                continue;
            }

            [$debut, $fin] = CalendrierScolaire::fenetre($annee, $rang + 1, $trimestres->count());

            $facteur = ProfilEleve::facteur($assiduite->eleve_id);
            $cohorte = ProfilEleve::cohorte($assiduite->eleve_id, 'assiduite');

            // 40% des eleves-trimestres sans aucune absence.
            $nbAbsences = $cohorte < 40 ? 0 : $this->tirage($facteur, 1, 6);
            $nbRetards = $this->tirage($facteur, 0, 4);

            for ($i = 0; $i < $nbAbsences; $i++) {
                Absence::create([
                    'assiduite_id' => $assiduite->id,
                    'date' => CalendrierScolaire::jourAleatoire($debut, $fin),
                    'nombre_heure' => [2, 4, 6][rand(0, 2)],
                    'justification' => rand(1, 100) <= 60
                        ? $this->motifs[array_rand($this->motifs)]
                        : self::NON_JUSTIFIE,
                ]);

                $absences++;
            }

            for ($i = 0; $i < $nbRetards; $i++) {
                Retard::create([
                    'assiduite_id' => $assiduite->id,
                    'date' => CalendrierScolaire::jourAleatoire($debut, $fin),
                    'heure_arrive' => sprintf('%02d:%02d', rand(7, 9), [0, 15, 30, 45][rand(0, 3)]),
                    'justification' => rand(1, 100) <= 50
                        ? $this->motifs[array_rand($this->motifs)]
                        : self::NON_JUSTIFIE,
                ]);

                $retards++;
            }

            $this->noterComportement($assiduite, $facteur, $nbAbsences);
        }

        $this->command->info("Absences creees: {$absences}, retards: {$retards}");
    }

    /**
     * Tirage pondere par le profil : un eleve en difficulte (facteur bas)
     * approche la borne haute, un bon eleve la borne basse.
     */
    private function tirage(float $facteur, int $min, int $max): int
    {
        // facteur va de 0.4 a 1.5 : on le renverse sur [0, 1].
        $risque = max(0.0, min(1.0, (1.5 - $facteur) / 1.1));

        return (int) round($min + ($max - $min) * $risque * (rand(50, 150) / 100));
    }

    /**
     * Appreciation et avertissements.
     *
     * Le champ `comportement` est encode a la main, comme le fait
     * AssiduiteController : le cast declare sur le modele est inoperant
     * (propriete $cast au lieu de $casts) et les vues font un json_decode
     * explicite. Ne pas "corriger" sans corriger aussi l'UI.
     */
    private function noterComportement(Assiduite $assiduite, float $facteur, int $nbAbsences): void
    {
        $appreciation = match (true) {
            $facteur >= 1.3 => 'Excellent',
            $facteur >= 1.1 => 'Tres bien',
            $facteur >= 0.9 => 'Bien',
            $facteur >= 0.7 => 'Assez bien',
            default => 'Passable',
        };

        $blame = rand(1, 100) <= 2;

        $assiduite->update([
            'comportement' => json_encode([
                'appreciation' => $appreciation,
                'avertissement' => [
                    'Travail' => $facteur < 0.7,
                    'Discipline' => $nbAbsences >= 5,
                ],
                'blame' => [
                    'Travail' => $blame && $facteur < 0.7,
                    'Discipline' => $blame && $nbAbsences >= 5,
                ],
            ]),
        ]);
    }
}
