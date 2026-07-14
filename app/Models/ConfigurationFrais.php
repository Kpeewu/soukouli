<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigurationFrais extends Model
{
    use HasFactory, HasHashidRouting;

    protected $table = 'configurations_frais';

    protected $fillable = [
        'type_frais_id',
        'cycle_id',
        'niveau',
        'annee_scolaire_id',
        'montant',
        'actif'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'actif' => 'boolean'
    ];

    public function typeFrais()
    {
        return $this->belongsTo(TypeFrais::class);
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function tranches()
    {
        return $this->hasMany(TranchePaiement::class)->orderBy('numero');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    public function scopeForAnnee($query, $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeForNiveau($query, $niveau)
    {
        return $query->where(function ($q) use ($niveau) {
            $q->whereNull('niveau')->orWhere('niveau', $niveau);
        });
    }

    /**
     * Retourne le total des montants des tranches
     */
    public function getTotalTranchesAttribute(): float
    {
        return $this->tranches->sum('montant');
    }

    /**
     * Vérifie si les tranches couvrent le montant total
     */
    public function tranchesCompletes(): bool
    {
        return abs($this->montant - $this->total_tranches) < 0.01;
    }
}
