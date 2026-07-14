<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'motif',
        'montant',
        'methode',
        'date_paiement',
        'annee_scolaire_id',
        'eleve_id',
        'tranche_paiement_id',
        'configuration_frais_id',
        'comptable_id',
        'mode_paiement',
        'reference',
        'notes',
        'annule'
    ];

    protected $casts = [
        'date_paiement' => 'date',
        'montant' => 'decimal:2',
        'annule' => 'boolean'
    ];

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function tranche()
    {
        return $this->belongsTo(TranchePaiement::class, 'tranche_paiement_id');
    }

    public function configurationFrais()
    {
        return $this->belongsTo(ConfigurationFrais::class);
    }

    public function comptable()
    {
        return $this->belongsTo(User::class, 'comptable_id');
    }

    public function recu()
    {
        return $this->hasOne(Recu::class);
    }

    /**
     * Scope pour les paiements d'une annee scolaire
     */
    public function scopeForAnnee($query, $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    /**
     * Scope pour les paiements d'un eleve
     */
    public function scopeForEleve($query, $eleveId)
    {
        return $query->where('eleve_id', $eleveId);
    }

    /**
     * Scope pour les paiements non annules (a utiliser pour tout calcul de solde/montant du)
     */
    public function scopeValide($query)
    {
        return $query->where('annule', false);
    }

    /**
     * Scope pour les paiements annules
     */
    public function scopeAnnule($query)
    {
        return $query->where('annule', true);
    }
}
