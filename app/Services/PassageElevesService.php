<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Assiduite;
use App\Models\Classe;
use App\Models\Cycle;
use App\Models\Eleve;
use App\Models\InscriptionExamen;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PassageElevesService
{
    public function __construct(private AnneeScolaireGenerationService $anneeService)
    {
    }

    /**
     * Resout l'annee scolaire suivante, avec les memes garde-fous que la
     * commande historique (annee courante requise, annee suivante deja creee).
     *
     * @throws RuntimeException
     */
    public function resoudreAnneeSuivante(): AnneeScolaire
    {
        $anneeScolaire = AnneeScolaire::getAnneeScolaire();
        if (!$anneeScolaire) {
            throw new RuntimeException('Aucune année scolaire courante trouvée.');
        }

        $nextLabel = $this->anneeService->calculerLabelAnneeSuivante();
        $nextYear = AnneeScolaire::where('annee', $nextLabel)->first();

        if (!$nextYear) {
            throw new RuntimeException("L'année scolaire suivante ({$nextLabel}) n'existe pas encore. Veuillez d'abord la générer.");
        }

        return $nextYear;
    }

    /**
     * Liste les classes de l'annee scolaire courante a traiter, filtrees par
     * cycle si fourni. Sert de "plan" pour l'UI web (une etape = une classe)
     * et d'iteration pour le traitement complet en CLI.
     */
    public function getClassesAPasser(?Cycle $cycle = null): Collection
    {
        $anneeScolaire = AnneeScolaire::getAnneeScolaire();
        if (!$anneeScolaire) {
            return new Collection();
        }

        $query = Classe::with(['eleves', 'promotion.cycle'])
            ->whereHas('promotion', function ($q) use ($anneeScolaire, $cycle) {
                $q->where('annee_scolaire_id', $anneeScolaire->id);
                if ($cycle) {
                    $q->where('cycle_id', $cycle->id);
                }
            });

        return $query->get();
    }

    /**
     * Traite le passage de tous les eleves d'une seule classe. Atomique par
     * classe (une requete web = une classe), pour permettre un traitement
     * sequentiel avec suivi de progression cote client.
     *
     * @return array{nb_passes: int, nb_redoublants: int, nb_diplomes: int, nb_erreurs: int, erreurs: string[]}
     */
    public function traiterClasse(Classe $classe, AnneeScolaire $nextYear): array
    {
        return DB::transaction(function () use ($classe, $nextYear) {
            $stats = [
                'nb_passes' => 0,
                'nb_redoublants' => 0,
                'nb_diplomes' => 0,
                'nb_erreurs' => 0,
                'erreurs' => [],
            ];

            $promotion = $classe->promotion;

            foreach ($classe->eleves as $eleve) {
                try {
                    $this->traiterEleve($eleve, $classe, $promotion, $nextYear, $stats);
                } catch (\Exception $e) {
                    $stats['nb_erreurs']++;
                    $stats['erreurs'][] = "{$eleve->nom} {$eleve->prenom}: {$e->getMessage()}";
                }
            }

            return $stats;
        });
    }

    /**
     * Traite le passage de toutes les classes d'un perimetre donne en une
     * seule fois - utilise par la commande CLI uniquement.
     */
    public function executerPassageComplet(?Cycle $cycle = null): array
    {
        $nextYear = $this->resoudreAnneeSuivante();

        $totaux = [
            'nb_passes' => 0,
            'nb_redoublants' => 0,
            'nb_diplomes' => 0,
            'nb_erreurs' => 0,
            'erreurs' => [],
            'nouvelle_annee' => $nextYear,
        ];

        foreach ($this->getClassesAPasser($cycle) as $classe) {
            $stats = $this->traiterClasse($classe, $nextYear);
            $totaux['nb_passes'] += $stats['nb_passes'];
            $totaux['nb_redoublants'] += $stats['nb_redoublants'];
            $totaux['nb_diplomes'] += $stats['nb_diplomes'];
            $totaux['nb_erreurs'] += $stats['nb_erreurs'];
            $totaux['erreurs'] = array_merge($totaux['erreurs'], $stats['erreurs']);
        }

        return $totaux;
    }

    private function traiterEleve(Eleve $eleve, Classe $classe, Promotion $promotion, AnneeScolaire $nextYear, array &$stats): void
    {
        $cycle = $promotion->cycle;
        $niveauActuel = $promotion->nom;

        // La reussite a l'examen officiel du niveau (CEPD, BEPC, BAC1, BAC2...)
        // fait passer l'eleve en classe superieure meme si sa moyenne annuelle
        // est inferieure a 10.
        $passe = $eleve->passeEnClasseSup($classe->id) || $this->aReussiExamenOfficiel($eleve, $promotion);

        if ($passe) {
            $niveauSuivant = $cycle->getNiveauSuivant($niveauActuel);

            if ($niveauSuivant === null) {
                if ($cycle->hasCycleSuivant()) {
                    $cycleSuivant = $cycle->cycleSuivant;
                    $premierNiveau = $cycleSuivant->getPremierNiveau();

                    if ($premierNiveau) {
                        $this->inscrireEleveDansPromotion($eleve, $cycleSuivant, $premierNiveau, $nextYear, false);
                        $stats['nb_passes']++;
                    } else {
                        $stats['nb_erreurs']++;
                        $stats['erreurs'][] = "{$eleve->nom} {$eleve->prenom}: aucun niveau trouvé dans le cycle {$cycleSuivant->nom}";
                    }
                } else {
                    $stats['nb_diplomes']++;
                }
            } else {
                $this->inscrireEleveDansPromotion($eleve, $cycle, $niveauSuivant, $nextYear, false);
                $stats['nb_passes']++;
            }
        } else {
            $this->inscrireEleveDansPromotion($eleve, $cycle, $niveauActuel, $nextYear, true);
            $stats['nb_redoublants']++;
        }
    }

    /**
     * Verifie si l'eleve a ete declare admis a l'examen officiel (CEPD, BEPC,
     * BAC1, BAC2...) rattache a ce niveau pour l'annee scolaire de la
     * promotion. Retourne false si le niveau n'a pas d'examen officiel, ou si
     * aucune inscription "admis" n'existe (absence de resultat = pas de
     * derogation, la decision reste basee sur la moyenne).
     */
    private function aReussiExamenOfficiel(Eleve $eleve, Promotion $promotion): bool
    {
        if (!$promotion->a_examen_officiel || !$promotion->examen_officiel_id) {
            return false;
        }

        return InscriptionExamen::where('eleve_id', $eleve->id)
            ->where('statut', 'admis')
            ->whereHas('sessionExamen', function ($q) use ($promotion) {
                $q->where('examen_officiel_id', $promotion->examen_officiel_id)
                    ->where('annee_scolaire_id', $promotion->annee_scolaire_id);
            })
            ->exists();
    }

    private function inscrireEleveDansPromotion(Eleve $eleve, Cycle $cycle, string $niveau, AnneeScolaire $nextYear, bool $redoublant): void
    {
        $promotion = Promotion::with(['classes', 'trimestres'])
            ->where('annee_scolaire_id', $nextYear->id)
            ->where('cycle_id', $cycle->id)
            ->where('nom', $niveau)
            ->first();

        if (!$promotion) {
            throw new \Exception("promotion non trouvée: {$niveau} dans le cycle {$cycle->nom}");
        }

        $nouvelleClasse = $promotion->classes->first();
        if (!$nouvelleClasse) {
            throw new \Exception("aucune classe trouvée pour la promotion {$niveau}");
        }

        $dejaInscrit = $eleve->classes()
            ->whereHas('promotion', function ($q) use ($nextYear) {
                $q->where('annee_scolaire_id', $nextYear->id);
            })
            ->exists();

        if ($dejaInscrit) {
            return;
        }

        $eleve->classes()->attach($nouvelleClasse);
        $eleve->update(['redoublant' => $redoublant]);

        foreach ($promotion->trimestres as $trimestre) {
            Assiduite::firstOrCreate([
                'trimestre_id' => $trimestre->id,
                'eleve_id' => $eleve->id,
            ]);
        }
    }
}
