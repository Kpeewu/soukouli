<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Type\Integer;

class Eleve extends Model
{
    use HasFactory, HasHashidRouting;

    protected $fillable = [
        'nom',
        'prenom',
        'date_naissance',
        'contact_tuteur',
        'matricule',
        'sexe',
        'lieu_naissance',
        'profil',
        'adresse',
        'pere',
        'mere',
        'sante',
        'redoublant'
    ];

    protected $casts = [
        'contact_tuteur' => 'json',
        'pere' => 'json',
        'mere' => 'json',
        'sante' => 'json',
    ];




    // calcul de la moyenne  en classe et composition d'un étudiant dans un cours dans un trimestre donné
    //
    // Les notes sont recherchées à travers tous les cours de la même matière (pas seulement
    // $id_cours) : un élève ayant changé de classe en cours d'année peut avoir des notes de
    // devoir/interrogation/composition dans le cours de son ancienne classe pour cette même
    // matière et ce même trimestre ; les ignorer ferait varier sa moyenne du simple fait du
    // changement de classe.
    public function getMoyenneCours($id_cours, $id_trimestre)
    {
        $cours = Cours::find($id_cours);

        $notes = Note::where('eleve_id', $this->id)
            ->where('trimestre_id', $id_trimestre)
            ->whereHas('evaluation.cours', function ($query) use ($cours) {
                $query->where('matiere_id', $cours->matiere_id);
            })
            ->with('evaluation')
            ->get();

        // Une note reportée suite à un changement de classe (EleveTransfertService) et sa note
        // d'origine représentent le même devoir/composition : on exclut l'originale dès qu'une
        // copie existe dans le lot, pour ne pas la compter deux fois dans la moyenne.
        $idsSources = $notes->pluck('note_source_id')->filter()->unique();
        $notes = $notes->reject(fn ($note) => $idsSources->contains($note->id));

        $notes_classes = $notes->filter(fn ($note) => in_array($note->evaluation->type, ['devoir', 'interrogation']));

        $somme_notes = 0.0;

        foreach ($notes_classes as $note) {
            // passage à 20 de la note dans le cas où le barême est inférieur à 20
            if ($note->evaluation->note_maximale < 20) {
                $note_sur_20 = (20 * $note->valeur) / $note->evaluation->note_maximale;
                $somme_notes += $note->valeur;
            } else {
                $somme_notes += $note->valeur;
            }
        }

        $nbre_notes = 1;
        if ($notes_classes->count() != 0) {
            $nbre_notes = $notes_classes->count();
        }

        // calcul de la moyenne de classe
        $moyenne_matiere = ['moyenne_classe' => round($somme_notes / $nbre_notes, 2)];

        $note_composition = 0;

        $notes_compo = $notes->first(fn ($note) => $note->evaluation->type === 'composition');
        if ($notes_compo) {
            $note_composition = $notes_compo->valeur * 1;
        }

        $moyenne_matiere['compo'] = $note_composition;

        $moyenne_matiere['cours'] = $cours;

        return $moyenne_matiere;
    }


    public function getMoyenneTrimestrielle($classe_id, $trimestre_id)
    {
        $classe = Classe::with('cours')->find($classe_id);

        $cours = $classe->cours;

        // tableau contenant l'ensemble des moyennes dans chaque cours au cours du trimestre
        $liste_moyennes = [];

        // calcul de la moyenne de l'étudiant dans chaque cours
        foreach ($cours as $cour) {
            array_push($liste_moyennes, $this->getMoyenneCours($cour->id, $trimestre_id));
        }

        // calcul de moyenne trimestrielle
        $total_moyenne = 0.0;
        $total_coefficients = 0.0;
        foreach ($liste_moyennes as $moyenne) {
            $total_moyenne += (($moyenne['moyenne_classe'] + $moyenne['compo']) / 2) * $moyenne['cours']->coefficient;
            $total_coefficients += $moyenne['cours']->coefficient;
        }

        if ($total_coefficients === 0.0) {
            $total_coefficients = 1.0;
        }

        $moyenne_trimestre = round($total_moyenne / $total_coefficients, 2);
        return $moyenne_trimestre;
    }


    public function rangTrimestre($trimestre_id, $classe_id)
    {
        $classe = Classe::with('eleves')->find($classe_id);

        $trimestre = Trimestre::find($trimestre_id);

        $moyenne_eleve = $this->getMoyenneTrimestrielle($classe->id, $trimestre->id);

        $moyennes = [];
        foreach ($classe->eleves as $eleve) {
            array_push($moyennes, $eleve->getMoyenneTrimestrielle($classe->id, $trimestre->id));
        }

        arsort($moyennes);

        if ($moyenne_eleve === 0.0) {
            return count($classe->eleves);
        }

        $rang = 0;
        foreach ($moyennes as $moyenne) {
            $rang++;
            if ($moyenne_eleve === $moyenne) {
                return $rang;
            }
        }
    }


    public function passeEnClasseSup($classe_id)
    {
        $classe = Classe::with('promotion.trimestres')->find($classe_id);
        $trimestres = $classe->promotion->trimestres;

        if ($trimestres->isEmpty()) {
            return false;
        }

        $sum_moyenne_annuelle = 0.0;
        foreach ($trimestres as $trimestre) {
            $sum_moyenne_annuelle += $this->getMoyenneTrimestrielle($classe_id, $trimestre->id);
        }

        // Diviser par le nombre de periodes (trimestres ou semestres)
        $nombrePeriodes = $trimestres->count();

        return $sum_moyenne_annuelle / $nombrePeriodes >= 10;
    }


    public function classes()
    {
        return $this->belongsToMany(Classe::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assiduites()
    {
        return $this->hasMany(Assiduite::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Relation avec les inscriptions aux examens
     */
    public function inscriptionsExamen()
    {
        return $this->hasMany(InscriptionExamen::class);
    }

    /**
     * Récupère le cycle actuel de l'élève basé sur sa classe
     */
    public function getCycleActuel(): ?Cycle
    {
        $classe = $this->getClasseActuelle();
        if ($classe && $classe->promotion && $classe->promotion->cycle) {
            return $classe->promotion->cycle;
        }
        return null;
    }

    /**
     * Récupère la classe actuelle de l'élève
     *
     * Trié par id de pivot décroissant : un élève peut avoir plusieurs lignes
     * classe_eleve historiques (redoublement, passage d'année, changement de
     * groupe), la plus récemment attachée est la classe actuelle.
     */
    public function getClasseActuelle(): ?Classe
    {
        return $this->classes()->orderByDesc('classe_eleve.id')->first();
    }

    /**
     * Calcule le total des frais applicables à l'élève pour l'année courante
     */
    public function getTotalFrais(): float
    {
        $classe = $this->getClasseActuelle();
        if (!$classe || !$classe->promotion) {
            return 0;
        }

        $promotion = $classe->promotion;
        $anneeScolaire = $promotion->anneeScolaire;
        $cycle = $promotion->cycle;

        if (!$anneeScolaire || !$cycle) {
            return 0;
        }

        return ConfigurationFrais::where('cycle_id', $cycle->id)
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->where(function ($q) use ($promotion) {
                $q->whereNull('niveau')->orWhere('niveau', $promotion->nom);
            })
            ->where('actif', true)
            ->sum('montant');
    }

    /**
     * Calcule le total des paiements effectués par l'élève pour l'année courante
     */
    public function getTotalPaye(): float
    {
        $classe = $this->getClasseActuelle();
        if (!$classe || !$classe->promotion) {
            return 0;
        }

        $promotion = $classe->promotion;
        $anneeScolaire = $promotion->anneeScolaire;
        $cycle = $promotion->cycle;

        if (!$anneeScolaire || !$cycle) {
            return 0;
        }

        $configIds = ConfigurationFrais::where('cycle_id', $cycle->id)
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->where(function ($q) use ($promotion) {
                $q->whereNull('niveau')->orWhere('niveau', $promotion->nom);
            })
            ->where('actif', true)
            ->pluck('id');

        return $this->paiements()
            ->whereIn('configuration_frais_id', $configIds)
            ->valide()
            ->sum('montant');
    }

    /**
     * Calcule le solde restant à payer pour l'élève
     */
    public function getSoldeRestant(): float
    {
        return $this->getTotalFrais() - $this->getTotalPaye();
    }

    /**
     * Retourne le statut de paiement de l'élève
     *
     * @return string 'solde' | 'partiel' | 'impaye'
     */
    public function getStatutPaiement(): string
    {
        $solde = $this->getSoldeRestant();
        $totalFrais = $this->getTotalFrais();

        if ($totalFrais <= 0) {
            return 'solde';
        }

        if ($solde <= 0) {
            return 'solde';
        }

        $totalPaye = $this->getTotalPaye();
        if ($totalPaye > 0) {
            return 'partiel';
        }

        return 'impaye';
    }

    /**
     * Retourne les frais applicables à l'élève avec leur statut de paiement
     */
    public function getFraisAvecStatut(): array
    {
        $classe = $this->getClasseActuelle();
        if (!$classe || !$classe->promotion) {
            return [];
        }

        $promotion = $classe->promotion;
        $anneeScolaire = $promotion->anneeScolaire;
        $cycle = $promotion->cycle;

        if (!$anneeScolaire || !$cycle) {
            return [];
        }

        $configs = ConfigurationFrais::with(['typeFrais', 'tranches'])
            ->where('cycle_id', $cycle->id)
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->where(function ($q) use ($promotion) {
                $q->whereNull('niveau')->orWhere('niveau', $promotion->nom);
            })
            ->where('actif', true)
            ->get();

        $result = [];
        foreach ($configs as $config) {
            $paye = $this->paiements()
                ->where('configuration_frais_id', $config->id)
                ->valide()
                ->sum('montant');

            $result[] = [
                'configuration' => $config,
                'type_frais' => $config->typeFrais,
                'montant_total' => (float) $config->montant,
                'montant_paye' => (float) $paye,
                'solde' => (float) ($config->montant - $paye),
                'statut' => $paye >= $config->montant ? 'solde' : ($paye > 0 ? 'partiel' : 'impaye'),
                'tranches' => $config->tranches->map(function ($tranche) {
                    $payeTranche = $this->paiements()
                        ->where('tranche_paiement_id', $tranche->id)
                        ->valide()
                        ->sum('montant');

                    return [
                        'tranche' => $tranche,
                        'montant_paye' => (float) $payeTranche,
                        'solde' => (float) ($tranche->montant - $payeTranche),
                        'en_retard' => $tranche->estEnRetard() && $payeTranche < $tranche->montant,
                    ];
                })->toArray()
            ];
        }

        return $result;
    }
}
