<?php

namespace Database\Seeders;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Note;
use App\Models\Trimestre;
use Carbon\Carbon;
use Database\Seeders\Support\CalendrierScolaire;
use Database\Seeders\Support\ProfilEleve;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer toutes les évaluations avec leurs cours et classes
        $evaluations = Evaluation::with(
            'cours.classe.eleves',
            'cours.classe.promotion.trimestres',
            'cours.classe.promotion.anneeScolaire'
        )->get();

        if ($evaluations->isEmpty()) {
            $this->command->warn('Aucune évaluation trouvée. Exécutez d\'abord EvaluationSeeder.');
            return;
        }

        $notesCreees = 0;
        $classesTraitees = [];

        foreach ($evaluations as $evaluation) {
            if (!$evaluation->cours || !$evaluation->cours->classe) {
                continue;
            }

            $classe = $evaluation->cours->classe;
            $eleves = $classe->eleves;

            if ($eleves->isEmpty()) {
                continue;
            }

            // Déterminer le trimestre correspondant à cette évaluation
            $trimestre = $this->findTrimestreForEvaluation($evaluation);

            if (!$trimestre) {
                continue;
            }

            // Créer une note pour chaque élève de la classe
            foreach ($eleves as $eleve) {
                // Générer une note réaliste
                $valeur = $this->generateRealisticNote(
                    $evaluation->note_maximale,
                    $evaluation->type,
                    $eleve
                );

                Note::firstOrCreate(
                    [
                        'eleve_id' => $eleve->id,
                        'evaluation_id' => $evaluation->id,
                        'trimestre_id' => $trimestre->id
                    ],
                    [
                        'valeur' => $valeur
                    ]
                );

                $notesCreees++;
            }

            // Afficher progression par classe (une seule fois par classe)
            $classeKey = $classe->id;
            if (!isset($classesTraitees[$classeKey])) {
                $this->command->info("Notes créées pour la classe: {$classe->nom}");
                $classesTraitees[$classeKey] = true;
            }
        }

        $this->command->info("Total notes créées: {$notesCreees}");
    }

    /**
     * Trouve le trimestre correspondant à une évaluation basé sur sa date.
     *
     * Les bornes des périodes sont dérivées de l'année scolaire (cf.
     * CalendrierScolaire) : la table `trimestres` ne porte pas de dates.
     */
    private function findTrimestreForEvaluation(Evaluation $evaluation): ?Trimestre
    {
        $promotion = $evaluation->cours->classe->promotion ?? null;
        $annee = $promotion?->anneeScolaire;

        if (!$promotion || !$annee) {
            return null;
        }

        $trimestres = $promotion->trimestres->sortBy('id')->values();

        if ($trimestres->isEmpty()) {
            return null;
        }

        $dateEvaluation = Carbon::parse($evaluation->date);

        foreach ($trimestres as $index => $trimestre) {
            [$debut, $fin] = CalendrierScolaire::fenetre($annee, $index + 1, $trimestres->count());

            if ($dateEvaluation->betweenIncluded($debut, $fin)) {
                return $trimestre;
            }
        }

        // Si pas trouvé, retourner le premier trimestre par défaut
        return $trimestres->first();
    }

    /**
     * Génère une note réaliste selon une distribution normale
     * avec quelques variations selon le type d'évaluation
     */
    private function generateRealisticNote(float $noteMaximale, string $type, Eleve $eleve): float
    {
        // Moyenne et écart-type selon le type d'évaluation
        // Les compositions sont généralement plus difficiles
        switch ($type) {
            case 'interrogation':
                $moyennePourcent = 0.60; // 60% de la note max
                $ecartType = 0.20;
                break;

            case 'devoir':
                $moyennePourcent = 0.55; // 55% de la note max
                $ecartType = 0.22;
                break;

            case 'composition':
                $moyennePourcent = 0.50; // 50% de la note max
                $ecartType = 0.25;
                break;

            default:
                $moyennePourcent = 0.55;
                $ecartType = 0.22;
        }

        // Appliquer un facteur de "niveau" de l'élève (basé sur son ID pour être déterministe)
        // Certains élèves sont naturellement meilleurs ou moins bons
        $facteurEleve = ProfilEleve::facteur($eleve->id);
        $moyennePourcent = $moyennePourcent * $facteurEleve;

        // Générer une note avec distribution normale (Box-Muller)
        $note = ProfilEleve::gauss($moyennePourcent * $noteMaximale, $ecartType * $noteMaximale);

        // S'assurer que la note est dans les limites [0, noteMaximale]
        $note = max(0, min($noteMaximale, $note));

        // Arrondir à 0.25 près (standard togolais)
        $note = round($note * 4) / 4;

        return $note;
    }

    // Le facteur de performance de l'élève et le tirage gaussien vivent
    // désormais dans Support\ProfilEleve : ils sont partagés avec les seeders
    // d'assiduité et d'examens pour que le profil d'un élève reste cohérent
    // d'un module à l'autre.
}
