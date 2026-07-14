<?php

namespace Database\Seeders;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Note;
use App\Models\Trimestre;
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
        $evaluations = Evaluation::with('cours.classe.eleves', 'cours.classe.promotion.trimestres')->get();

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
     * Trouve le trimestre correspondant à une évaluation basé sur sa date
     */
    private function findTrimestreForEvaluation(Evaluation $evaluation): ?Trimestre
    {
        $trimestres = $evaluation->cours->classe->promotion->trimestres ?? collect();

        if ($trimestres->isEmpty()) {
            return null;
        }

        $dateEvaluation = strtotime($evaluation->date);

        foreach ($trimestres as $trimestre) {
            $debut = strtotime($trimestre->date_debut);
            $fin = strtotime($trimestre->date_fin);

            if ($dateEvaluation >= $debut && $dateEvaluation <= $fin) {
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
        $facteurEleve = $this->getFacteurEleve($eleve);
        $moyennePourcent = $moyennePourcent * $facteurEleve;

        // Générer une note avec distribution normale (Box-Muller)
        $note = $this->generateNormalDistribution($moyennePourcent * $noteMaximale, $ecartType * $noteMaximale);

        // S'assurer que la note est dans les limites [0, noteMaximale]
        $note = max(0, min($noteMaximale, $note));

        // Arrondir à 0.25 près (standard togolais)
        $note = round($note * 4) / 4;

        return $note;
    }

    /**
     * Génère un facteur de performance constant pour un élève
     * basé sur son ID (pour que ce soit reproductible)
     */
    private function getFacteurEleve(Eleve $eleve): float
    {
        // Utiliser l'ID pour créer un facteur "pseudo-aléatoire" mais constant
        $hash = crc32((string) $eleve->id);
        $normalized = ($hash % 1000) / 1000; // Valeur entre 0 et 1

        // Distribuer les élèves :
        // - 10% excellents (facteur 1.3-1.5)
        // - 30% bons (facteur 1.0-1.3)
        // - 40% moyens (facteur 0.8-1.0)
        // - 15% en difficulté (facteur 0.6-0.8)
        // - 5% en grande difficulté (facteur 0.4-0.6)

        if ($normalized < 0.05) {
            return 0.4 + ($normalized / 0.05) * 0.2; // 0.4-0.6
        } elseif ($normalized < 0.20) {
            return 0.6 + (($normalized - 0.05) / 0.15) * 0.2; // 0.6-0.8
        } elseif ($normalized < 0.60) {
            return 0.8 + (($normalized - 0.20) / 0.40) * 0.2; // 0.8-1.0
        } elseif ($normalized < 0.90) {
            return 1.0 + (($normalized - 0.60) / 0.30) * 0.3; // 1.0-1.3
        } else {
            return 1.3 + (($normalized - 0.90) / 0.10) * 0.2; // 1.3-1.5
        }
    }

    /**
     * Génère une valeur suivant une distribution normale (méthode Box-Muller)
     */
    private function generateNormalDistribution(float $moyenne, float $ecartType): float
    {
        // Box-Muller transform
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();

        // Éviter log(0)
        $u1 = max($u1, 0.0001);

        $z = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);

        return $moyenne + $z * $ecartType;
    }
}
