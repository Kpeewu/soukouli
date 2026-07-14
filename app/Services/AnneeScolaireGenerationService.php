<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\ConfigurationFrais;
use App\Models\Cours;
use App\Models\Cycle;
use App\Models\ExamenOfficiel;
use App\Models\Promotion;
use App\Models\SessionExamen;
use App\Models\TranchePaiement;
use App\Models\Trimestre;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AnneeScolaireGenerationService
{
    /**
     * Calcule le label de l'annee scolaire suivant l'annee courante (ex: si
     * l'annee courante est "2026-2027", retourne "2027-2028"). Centralise ici
     * pour que le controleur (affichage) et la generation elle-meme utilisent
     * toujours exactement le meme calcul.
     *
     * Se base sur l'annee courante elle-meme plutot que sur la date physique
     * du jour: sinon, si l'annee courante et l'annee civile en cours
     * coincident (ex: on est en 2026 et l'annee courante est deja
     * "2026-2027"), le calcul par date renverrait a tort "2026-2027" comme
     * "annee suivante" au lieu de "2027-2028".
     */
    public function calculerLabelAnneeSuivante(): string
    {
        $anneeCourante = AnneeScolaire::getAnneeScolaire();

        if ($anneeCourante && preg_match('/^(\d{4})-\d{4}$/', $anneeCourante->annee, $matches)) {
            $premiereAnnee = (int) $matches[1];

            return ($premiereAnnee + 1) . '-' . ($premiereAnnee + 2);
        }

        // Repli si aucune annee courante n'existe ou si son label ne suit pas
        // le format attendu: comportement historique base sur la date du jour.
        $aujourdHui = Carbon::now();

        return $aujourdHui->year . '-' . ($aujourdHui->year + 1);
    }

    /**
     * Genere la nouvelle annee scolaire : promotions/trimestres/classes par cycle,
     * copie des configurations de frais et de leurs tranches, copie des sessions
     * d'examen, copie des matieres/cours - y compris les affectations de
     * professeurs (titulaire de classe et professeur par cours), reprises depuis
     * l'annee precedente.
     *
     * Ne bascule PAS le flag "courant" : l'ancienne annee reste l'annee reelle
     * tant que le passage des eleves n'a pas eu lieu et qu'un admin/directeur
     * general n'a pas explicitement active la nouvelle annee via
     * activerAnneeScolaire(). C'est aussi ce qui empeche la tache planifiee
     * automatique de creer des annees en cascade : tant que "courant" ne bouge
     * pas, le label suivant recalcule reste toujours le meme et se heurte au
     * garde-fou anti double-execution ci-dessous.
     *
     * @throws RuntimeException si aucune annee courante n'existe, ou si l'annee
     *         suivante existe deja (garde-fou anti double-execution, y compris
     *         vis-a-vis de la tache planifiee automatique d'aout).
     * @return array{
     *     ancienne_annee: AnneeScolaire,
     *     nouvelle_annee: AnneeScolaire,
     *     nb_promotions: int,
     *     nb_classes: int,
     *     nb_trimestres: int,
     *     nb_configurations_frais: int,
     *     nb_tranches: int,
     *     nb_sessions_examen: int,
     *     nb_associations_matieres: int,
     *     nb_cours: int,
     * }
     * @see activerAnneeScolaire() pour l'activation manuelle de l'annee generee
     */
    public function genererAnneeSuivante(): array
    {
        $currentAnneeScolaire = AnneeScolaire::getAnneeScolaire();
        if (!$currentAnneeScolaire) {
            throw new RuntimeException('Aucune année scolaire courante trouvée. Impossible de générer l\'année suivante.');
        }

        $nextLabel = $this->calculerLabelAnneeSuivante();
        if (AnneeScolaire::where('annee', $nextLabel)->exists()) {
            throw new RuntimeException("L'année scolaire {$nextLabel} existe déjà.");
        }

        return DB::transaction(function () use ($currentAnneeScolaire, $nextLabel) {
            $stats = [
                'ancienne_annee' => $currentAnneeScolaire,
                'nb_promotions' => 0,
                'nb_classes' => 0,
                'nb_trimestres' => 0,
                'nb_configurations_frais' => 0,
                'nb_tranches' => 0,
                'nb_sessions_examen' => 0,
                'nb_associations_matieres' => 0,
                'nb_cours' => 0,
            ];

            $nouvelleAnneeScolaire = AnneeScolaire::create([
                'annee' => $nextLabel,
                'courant' => false,
            ]);
            $stats['nouvelle_annee'] = $nouvelleAnneeScolaire;

            foreach (Cycle::orderBy('ordre')->get() as $cycle) {
                $this->createPromotionsForCycle($cycle, $nouvelleAnneeScolaire, $stats);
            }

            $this->copyConfigurationsFrais($currentAnneeScolaire, $nouvelleAnneeScolaire, $stats);
            $this->copySessionsExamen($currentAnneeScolaire, $nouvelleAnneeScolaire, $stats);
            $this->copyMatieres($currentAnneeScolaire, $nouvelleAnneeScolaire, $stats);

            return $stats;
        });
    }

    /**
     * Active une annee scolaire deja generee : bascule le flag global "courant"
     * vers elle. Action manuelle et volontaire (jamais automatique a la fin du
     * passage des eleves : la detection "tous cycles termines" est jugee trop
     * fragile - redoublants, diplomes, erreurs partielles).
     *
     * Garde-fou important : seule "l'annee suivante" *calculee* (courant + 1)
     * peut etre activee - jamais une annee arbitraire - pour eviter d'activer
     * par erreur une annee passee/archivee ou une annee generee en avance avant
     * que le passage des eleves ait eu lieu.
     *
     * @throws RuntimeException si aucune annee courante n'existe, si l'annee
     *         cible est deja courante, ou si l'annee cible n'est pas exactement
     *         l'annee suivante de l'annee courante actuelle.
     */
    public function activerAnneeScolaire(AnneeScolaire $anneeScolaire): AnneeScolaire
    {
        $currentAnneeScolaire = AnneeScolaire::getAnneeScolaire();
        if (!$currentAnneeScolaire) {
            throw new RuntimeException("Aucune année scolaire courante trouvée. Impossible d'activer une nouvelle année.");
        }

        if ($anneeScolaire->id === $currentAnneeScolaire->id) {
            throw new RuntimeException("L'année scolaire {$anneeScolaire->annee} est déjà l'année courante.");
        }

        $nextLabel = $this->calculerLabelAnneeSuivante();
        if ($anneeScolaire->annee !== $nextLabel) {
            throw new RuntimeException(
                "Seule l'année scolaire suivante ({$nextLabel}) peut être activée, pas {$anneeScolaire->annee}."
            );
        }

        return DB::transaction(function () use ($currentAnneeScolaire, $anneeScolaire) {
            $currentAnneeScolaire->update(['courant' => false]);
            $anneeScolaire->update(['courant' => true]);

            return $anneeScolaire->fresh();
        });
    }

    /**
     * Cree les promotions, trimestres et la classe par defaut pour un cycle donne.
     */
    private function createPromotionsForCycle(Cycle $cycle, AnneeScolaire $anneeScolaire, array &$stats): void
    {
        $promotionNames = $cycle->getDefaultPromotions();
        $promotionsAvecExamen = $cycle->getPromotionsAvecExamen();

        foreach ($promotionNames as $index => $promotionName) {
            $aExamen = in_array($promotionName, $promotionsAvecExamen);
            $examenOfficiel = null;

            if ($aExamen) {
                $examenOfficiel = ExamenOfficiel::where('cycle_id', $cycle->id)
                    ->where('niveau_requis', $promotionName)
                    ->first();
            }

            $promotion = Promotion::create([
                'nom' => $promotionName,
                'ordre' => $index + 1,
                'annee_scolaire_id' => $anneeScolaire->id,
                'cycle_id' => $cycle->id,
                'type_periode' => 'trimestre',
                'a_examen_officiel' => $aExamen,
                'examen_officiel_id' => $examenOfficiel?->id,
            ]);
            $stats['nb_promotions']++;

            $nombrePeriodes = $promotion->type_periode === 'semestre' ? 2 : 3;
            $typePeriode = $promotion->type_periode === 'semestre' ? 'Semestre' : 'Trimestre';

            for ($j = 1; $j <= $nombrePeriodes; $j++) {
                Trimestre::create([
                    'intitule' => $typePeriode . ' ' . $j . ' ' . $promotionName . ' ' . $anneeScolaire->annee,
                    'promotion_id' => $promotion->id,
                ]);
                $stats['nb_trimestres']++;
            }

            // Classe par defaut : aucune promotion/classe de l'annee precedente ne
            // lui correspond encore a ce stade (le rapprochement ancien/nouveau se
            // fait dans copyMatieres) - le professeur_id y sera copie a ce moment-la.
            Classe::create([
                'nom' => $promotionName . ' A',
                'promotion_id' => $promotion->id,
            ]);
            $stats['nb_classes']++;
        }
    }

    /**
     * Copie les configurations de frais de l'annee precedente vers la nouvelle annee.
     */
    private function copyConfigurationsFrais(AnneeScolaire $ancienneAnnee, AnneeScolaire $nouvelleAnnee, array &$stats): void
    {
        $configurations = ConfigurationFrais::with('tranches')
            ->where('annee_scolaire_id', $ancienneAnnee->id)
            ->get();

        foreach ($configurations as $config) {
            $newConfig = ConfigurationFrais::create([
                'type_frais_id' => $config->type_frais_id,
                'cycle_id' => $config->cycle_id,
                'niveau' => $config->niveau,
                'annee_scolaire_id' => $nouvelleAnnee->id,
                'montant' => $config->montant,
                'actif' => $config->actif,
            ]);
            $stats['nb_configurations_frais']++;

            foreach ($config->tranches as $tranche) {
                $nouvelleDateLimite = null;
                if ($tranche->date_limite) {
                    $nouvelleDateLimite = Carbon::parse($tranche->date_limite)->addYear();
                }

                TranchePaiement::create([
                    'configuration_frais_id' => $newConfig->id,
                    'nom' => $tranche->nom,
                    'numero' => $tranche->numero,
                    'montant' => $tranche->montant,
                    'date_limite' => $nouvelleDateLimite,
                ]);
                $stats['nb_tranches']++;
            }
        }
    }

    /**
     * Copie les sessions d'examen de l'annee precedente vers la nouvelle annee.
     */
    private function copySessionsExamen(AnneeScolaire $ancienneAnnee, AnneeScolaire $nouvelleAnnee, array &$stats): void
    {
        if (!class_exists(SessionExamen::class)) {
            return;
        }

        $sessions = SessionExamen::where('annee_scolaire_id', $ancienneAnnee->id)->get();

        foreach ($sessions as $session) {
            $nouvelleDateDebut = null;
            $nouvelleDateFin = null;

            if ($session->date_debut) {
                $nouvelleDateDebut = Carbon::parse($session->date_debut)->addYear();
            }
            if ($session->date_fin) {
                $nouvelleDateFin = Carbon::parse($session->date_fin)->addYear();
            }

            SessionExamen::create([
                'examen_officiel_id' => $session->examen_officiel_id,
                'annee_scolaire_id' => $nouvelleAnnee->id,
                'date_debut' => $nouvelleDateDebut,
                'date_fin' => $nouvelleDateFin,
                'statut' => 'programme',
            ]);
            $stats['nb_sessions_examen']++;
        }
    }

    /**
     * Copie les associations matieres-promotions et les cours de l'annee
     * precedente, y compris les affectations de professeurs (titulaire de
     * classe et professeur par cours).
     */
    private function copyMatieres(AnneeScolaire $ancienneAnnee, AnneeScolaire $nouvelleAnnee, array &$stats): void
    {
        $anciennesPromotions = Promotion::with(['matieres', 'classes.cours'])
            ->where('annee_scolaire_id', $ancienneAnnee->id)
            ->get();

        $nouvellesPromotions = Promotion::with('classes')
            ->where('annee_scolaire_id', $nouvelleAnnee->id)
            ->get();

        foreach ($anciennesPromotions as $anciennePromotion) {
            $nouvellePromotion = $nouvellesPromotions->first(function ($p) use ($anciennePromotion) {
                return $p->nom === $anciennePromotion->nom
                    && $p->cycle_id === $anciennePromotion->cycle_id;
            });

            if (!$nouvellePromotion) {
                continue;
            }

            foreach ($anciennePromotion->matieres as $matiere) {
                $exists = DB::table('matiere_promotion')
                    ->where('promotion_id', $nouvellePromotion->id)
                    ->where('matiere_id', $matiere->id)
                    ->exists();

                if (!$exists) {
                    DB::table('matiere_promotion')->insert([
                        'promotion_id' => $nouvellePromotion->id,
                        'matiere_id' => $matiere->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $stats['nb_associations_matieres']++;
                }
            }

            foreach ($anciennePromotion->classes as $ancienneClasse) {
                // On prend la classe par defaut de la nouvelle promotion (son nom
                // contient l'annee) puisqu'une seule classe est generee par niveau.
                $nouvelleClasse = $nouvellePromotion->classes->first();

                if (!$nouvelleClasse) {
                    continue;
                }

                if (is_null($nouvelleClasse->professeur_id) && $ancienneClasse->professeur_id) {
                    $nouvelleClasse->update(['professeur_id' => $ancienneClasse->professeur_id]);
                }

                foreach ($ancienneClasse->cours as $ancienCours) {
                    $coursExists = Cours::where('classe_id', $nouvelleClasse->id)
                        ->where('matiere_id', $ancienCours->matiere_id)
                        ->exists();

                    if (!$coursExists) {
                        Cours::create([
                            'nom' => $ancienCours->nom,
                            'coefficient' => $ancienCours->coefficient,
                            'classe_id' => $nouvelleClasse->id,
                            'matiere_id' => $ancienCours->matiere_id,
                            'professeur_id' => $ancienCours->professeur_id,
                        ]);
                        $stats['nb_cours']++;
                    }
                }
            }
        }
    }
}
