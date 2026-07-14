<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Recu extends Model
{
    use HasFactory, HasHashidRouting;

    protected $fillable = [
        'numero',
        'paiement_id',
        'comptable_id',
        'date_emission',
        'annule',
        'motif_annulation'
    ];

    protected $casts = [
        'date_emission' => 'datetime',
        'annule' => 'boolean'
    ];

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    public function comptable()
    {
        return $this->belongsTo(User::class, 'comptable_id');
    }

    /**
     * Génère un numéro de reçu unique
     * Format: RECU-YYYY-XXXXX
     */
    public static function genererNumero(): string
    {
        $annee = date('Y');
        $dernierRecu = self::whereYear('created_at', $annee)
            ->orderBy('id', 'desc')
            ->first();

        if ($dernierRecu) {
            $numero = intval(substr($dernierRecu->numero, -5)) + 1;
        } else {
            $numero = 1;
        }

        return sprintf('RECU-%s-%05d', $annee, $numero);
    }

    /**
     * Annule le reçu avec un motif, ainsi que le paiement associe
     * (le montant du par l'eleve est ainsi automatiquement remis a jour)
     */
    public function annuler(string $motif): bool
    {
        return DB::transaction(function () use ($motif) {
            $this->annule = true;
            $this->motif_annulation = $motif;
            $saved = $this->save();

            $this->paiement->update(['annule' => true]);

            return $saved;
        });
    }

    /**
     * Scope pour les reçus valides (non annulés)
     */
    public function scopeValide($query)
    {
        return $query->where('annule', false);
    }

    /**
     * Scope pour les reçus annulés
     */
    public function scopeAnnule($query)
    {
        return $query->where('annule', true);
    }
}
