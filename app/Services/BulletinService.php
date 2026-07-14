<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Trimestre;
use App\Models\Cours;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BulletinService
{
    /**
     * Cache des moyennes calculées pour éviter les recalculs
     */
    private array $cache = [];

    /**
     * Index des notes de toute la promotion, par matière/élève/trimestre. Construit une
     * seule fois par promotion : permet de retrouver les notes d'un élève à travers plusieurs
     * classes (changement de groupe en cours d'année) sans requêtes N+1.
     */
    private array $notesIndex = [];
    private ?int $notesIndexPromotionId = null;

    /**
     * Calcule toutes les données nécessaires pour un bulletin d'un élève
     */
    public function getBulletinData(Eleve $eleve, Classe $classe, Trimestre $trimestre): array
    {
        // Charger les données avec eager loading optimisé
        $classe->load([
            'cours.matiere',
            'cours.professeur',
            'promotion.trimestres',
            'promotion.classes.cours.evaluations.notes',
            'eleves'
        ]);

        $this->indexerNotes($classe);

        // Calculer les moyennes de tous les élèves une seule fois
        $moyennesClasse = $this->calculerMoyennesClasse($classe, $trimestre);

        // Calculer les lignes du bulletin
        $lignes = $this->calculerLignesBulletin($eleve, $classe, $trimestre);

        // Calculer les moyennes et rangs pour tous les trimestres
        $moyennesTrimestres = $this->calculerMoyennesTrimestresEleve(
            $eleve,
            $classe,
            $moyennesClasse
        );

        return [
            'lignes' => $lignes,
            'moyennes_trimestres' => $moyennesTrimestres,
            'eleve' => $eleve,
            'classe' => $classe,
            'trimestre' => $trimestre,
        ];
    }

    /**
     * Calcule toutes les données nécessaires pour les bulletins d'une classe entière
     * Optimisé pour éviter les requêtes N+1
     */
    public function getBulletinsClasseData(Classe $classe, Trimestre $trimestre): array
    {
        // Charger toutes les données nécessaires en une seule fois
        $classe->load([
            'cours.matiere',
            'cours.professeur',
            'promotion.trimestres',
            'promotion.classes.cours.evaluations.notes',
            'eleves.assiduites' => fn($q) => $q->where('trimestre_id', $trimestre->id)
        ]);

        $this->indexerNotes($classe);

        // Pré-calculer toutes les notes par élève et par cours
        $notesParEleveParCours = $this->preCalculerNotes($classe, $trimestre);

        // Calculer les moyennes de tous les élèves pour tous les trimestres
        $moyennesClasseParTrimestre = [];
        foreach ($classe->promotion->trimestres as $trim) {
            $moyennesClasseParTrimestre[$trim->id] = $this->calculerMoyennesClasseOptimise(
                $classe,
                $trim,
                $notesParEleveParCours
            );
        }

        // Construire les bulletins
        $bulletins = [];
        foreach ($classe->eleves as $eleve) {
            $bulletin = $this->construireBulletinEleve(
                $eleve,
                $classe,
                $trimestre,
                $notesParEleveParCours,
                $moyennesClasseParTrimestre
            );
            $bulletins[] = $bulletin;
        }

        return [
            'bulletins' => $bulletins,
            'classe' => $classe,
            'trimestre' => $trimestre,
        ];
    }

    /**
     * Indexe les notes de toute la promotion par matière/élève/trimestre, une seule fois
     * par promotion. Regrouper par matière (et non par cours) permet à un élève transféré
     * en cours d'année de conserver, dans sa moyenne, les notes prises dans le cours de son
     * ancienne classe pour la même matière.
     */
    private function indexerNotes(Classe $classe): void
    {
        if ($this->notesIndexPromotionId === $classe->promotion_id) {
            return;
        }

        $index = [];

        foreach ($classe->promotion->classes as $classePromo) {
            foreach ($classePromo->cours as $cours) {
                foreach ($cours->evaluations as $evaluation) {
                    foreach ($evaluation->notes as $note) {
                        $index[$cours->matiere_id][$note->eleve_id][$note->trimestre_id][] = [
                            'id' => $note->id,
                            'valeur' => $note->valeur,
                            'type' => $evaluation->type,
                            'note_source_id' => $note->note_source_id,
                        ];
                    }
                }
            }
        }

        $this->notesIndex = $index;
        $this->notesIndexPromotionId = $classe->promotion_id;
    }

    /**
     * Pré-calcule toutes les notes organisées par élève et par cours
     */
    private function preCalculerNotes(Classe $classe, Trimestre $trimestre): array
    {
        $result = [];

        foreach ($classe->eleves as $eleve) {
            $result[$eleve->id] = [];

            foreach ($classe->cours as $cours) {
                $result[$eleve->id][$cours->id] = $this->calculerMoyenneCours($eleve, $cours, $trimestre);
            }
        }

        return $result;
    }

    /**
     * Calcule les moyennes de classe de manière optimisée
     */
    private function calculerMoyennesClasseOptimise(
        Classe $classe,
        Trimestre $trimestre,
        array $notesParEleveParCours
    ): array {
        $moyennes = [];

        foreach ($classe->eleves as $eleve) {
            $totalMoyenne = 0;
            $totalCoefficients = 0;

            foreach ($classe->cours as $cours) {
                $notes = $notesParEleveParCours[$eleve->id][$cours->id] ?? null;

                if ($notes) {
                    $moyenneCours = ($notes['moyenne_classe'] + $notes['compo']) / 2;
                    $totalMoyenne += $moyenneCours * $cours->coefficient;
                    $totalCoefficients += $cours->coefficient;
                }
            }

            $moyenne = $totalCoefficients > 0
                ? round($totalMoyenne / $totalCoefficients, 2)
                : 0;

            $moyennes[] = [
                'eleve_id' => $eleve->id,
                'moyenne' => $moyenne,
            ];
        }

        // Trier par moyenne décroissante
        usort($moyennes, fn($a, $b) => $b['moyenne'] <=> $a['moyenne']);

        // Ajouter les rangs
        foreach ($moyennes as $index => &$item) {
            $item['rang'] = $index + 1;
        }

        return $moyennes;
    }

    /**
     * Construit le bulletin complet d'un élève
     */
    private function construireBulletinEleve(
        Eleve $eleve,
        Classe $classe,
        Trimestre $trimestre,
        array $notesParEleveParCours,
        array $moyennesClasseParTrimestre
    ): array {
        $bulletin = [
            'eleve' => $eleve,
            'eleve_id' => $eleve->id,
            $eleve->id => [],
        ];

        // Lignes du bulletin (notes par matière)
        foreach ($classe->cours as $cours) {
            $bulletin[$eleve->id][] = [
                'notes_cours' => $notesParEleveParCours[$eleve->id][$cours->id],
            ];
        }

        // Moyennes et rangs par trimestre
        $bulletin['moyennes'] = [];
        $sommeAnnuelle = 0;

        foreach ($classe->promotion->trimestres as $trim) {
            $moyennesTrim = $moyennesClasseParTrimestre[$trim->id];
            $eleveData = collect($moyennesTrim)->firstWhere('eleve_id', $eleve->id);

            $bulletin['moyennes'][$trim->id] = [
                'moyenne' => $eleveData['moyenne'] ?? 0,
                'rang' => $eleveData['rang'] ?? count($classe->eleves),
                'eleve_id' => $eleve->id,
            ];

            $sommeAnnuelle += $eleveData['moyenne'] ?? 0;
        }

        // Moyenne annuelle
        $nombreTrimestres = $classe->promotion->trimestres->count();
        $bulletin['moyenne_annuelle'] = $nombreTrimestres > 0
            ? round($sommeAnnuelle / $nombreTrimestres, 2)
            : 0;

        // Moyenne en lettres
        $moyenneActuelle = $bulletin['moyennes'][$trimestre->id]['moyenne'] ?? 0;
        $bulletin['moyenne_lettres'] = $this->nombreEnLettres($moyenneActuelle);

        // Assiduité
        $bulletin['assiduite'] = $eleve->assiduites->first();

        return $bulletin;
    }

    /**
     * Calcule les moyennes de tous les élèves d'une classe pour un trimestre
     */
    private function calculerMoyennesClasse(Classe $classe, Trimestre $trimestre): array
    {
        $moyennes = [];

        foreach ($classe->eleves as $eleve) {
            $moyenne = $this->calculerMoyenneTrimestrielle($eleve, $classe, $trimestre);
            $moyennes[] = [
                'eleve_id' => $eleve->id,
                'moyenne' => $moyenne,
            ];
        }

        // Trier par moyenne décroissante et ajouter les rangs
        usort($moyennes, fn($a, $b) => $b['moyenne'] <=> $a['moyenne']);

        foreach ($moyennes as $index => &$item) {
            $item['rang'] = $index + 1;
        }

        return $moyennes;
    }

    /**
     * Calcule la moyenne trimestrielle d'un élève
     */
    private function calculerMoyenneTrimestrielle(Eleve $eleve, Classe $classe, Trimestre $trimestre): float
    {
        $cacheKey = "moyenne_{$eleve->id}_{$classe->id}_{$trimestre->id}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $totalMoyenne = 0;
        $totalCoefficients = 0;

        foreach ($classe->cours as $cours) {
            $moyenneCours = $this->calculerMoyenneCours($eleve, $cours, $trimestre);
            $moyenne2Notes = ($moyenneCours['moyenne_classe'] + $moyenneCours['compo']) / 2;
            $totalMoyenne += $moyenne2Notes * $cours->coefficient;
            $totalCoefficients += $cours->coefficient;
        }

        $moyenne = $totalCoefficients > 0
            ? round($totalMoyenne / $totalCoefficients, 2)
            : 0;

        $this->cache[$cacheKey] = $moyenne;

        return $moyenne;
    }

    /**
     * Calcule la moyenne d'un élève dans un cours pour un trimestre
     */
    private function calculerMoyenneCours(Eleve $eleve, Cours $cours, Trimestre $trimestre): array
    {
        $notesClasse = [];
        $noteCompo = 0;

        $notes = $this->notesIndex[$cours->matiere_id][$eleve->id][$trimestre->id] ?? [];

        // Une note reportée suite à un changement de classe et sa note d'origine représentent
        // le même devoir/composition : on exclut l'originale dès qu'une copie existe dans le
        // lot, pour ne pas la compter deux fois dans la moyenne.
        $idsSources = array_filter(array_column($notes, 'note_source_id'));
        $notes = array_filter($notes, fn ($note) => !in_array($note['id'], $idsSources));

        foreach ($notes as $note) {
            if ($note['type'] === 'composition') {
                $noteCompo = $note['valeur'];
            } else {
                $notesClasse[] = $note['valeur'];
            }
        }

        $moyenneClasse = count($notesClasse) > 0
            ? round(array_sum($notesClasse) / count($notesClasse), 2)
            : 0;

        return [
            'moyenne_classe' => $moyenneClasse,
            'compo' => $noteCompo,
            'cours' => $cours,
        ];
    }

    /**
     * Calcule les lignes du bulletin pour un élève
     */
    private function calculerLignesBulletin(Eleve $eleve, Classe $classe, Trimestre $trimestre): array
    {
        $lignes = [];

        foreach ($classe->cours as $cours) {
            $lignes[] = [
                'notes_cours' => $this->calculerMoyenneCours($eleve, $cours, $trimestre),
            ];
        }

        return $lignes;
    }

    /**
     * Calcule les moyennes et rangs d'un élève pour tous les trimestres
     */
    private function calculerMoyennesTrimestresEleve(
        Eleve $eleve,
        Classe $classe,
        array $moyennesClasse
    ): array {
        $result = [];

        foreach ($classe->promotion->trimestres as $trimestre) {
            $moyennesTrim = $this->calculerMoyennesClasse($classe, $trimestre);
            $eleveData = collect($moyennesTrim)->firstWhere('eleve_id', $eleve->id);

            $result[$trimestre->id] = [
                'moyenne' => $eleveData['moyenne'] ?? 0,
                'rang' => $eleveData['rang'] ?? count($classe->eleves),
                'eleve_id' => $eleve->id,
            ];
        }

        return $result;
    }

    /**
     * Convertit un nombre en lettres (français)
     */
    private function nombreEnLettres(float $nombre): string
    {
        if ($nombre === 0.0) {
            return 'Zéro';
        }

        try {
            return \Rmunate\Utilities\SpellNumber::float(strval($nombre))->toLetters();
        } catch (\Exception $e) {
            return strval($nombre);
        }
    }

    /**
     * Vide le cache interne
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->notesIndex = [];
        $this->notesIndexPromotionId = null;
    }
}
