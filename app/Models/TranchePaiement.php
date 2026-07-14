<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TranchePaiement extends Model
{
    use HasFactory;

    protected $table = 'tranches_paiement';

    protected $fillable = [
        'configuration_frais_id',
        'nom',
        'numero',
        'montant',
        'date_limite'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_limite' => 'date:Y-m-d'
    ];

    public function configurationFrais()
    {
        return $this->belongsTo(ConfigurationFrais::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Vérifie si la tranche est en retard
     */
    public function estEnRetard(): bool
    {
        return $this->date_limite->isPast();
    }

    /**
     * Calcule le montant payé pour cette tranche par un élève
     */
    public function getMontantPayeParEleve(int $eleveId): float
    {
        return $this->paiements()
            ->where('eleve_id', $eleveId)
            ->valide()
            ->sum('montant');
    }

    /**
     * Vérifie si la tranche est soldée pour un élève
     */
    public function estSoldeeParEleve(int $eleveId): bool
    {
        return $this->getMontantPayeParEleve($eleveId) >= $this->montant;
    }
}
