<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscriptionExamen extends Model
{
    use HasFactory, HasHashidRouting;

    protected $table = 'inscriptions_examen';

    protected $fillable = [
        'session_examen_id',
        'eleve_id',
        'numero_inscription',
        'centre_examen',
        'statut',
        'moyenne_obtenue',
        'mention'
    ];

    protected $casts = [
        'moyenne_obtenue' => 'float',
    ];

    /**
     * Relation avec la session d'examen
     */
    public function sessionExamen()
    {
        return $this->belongsTo(SessionExamen::class);
    }

    /**
     * Relation avec l'élève
     */
    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    /**
     * Détermine si l'élève est admis
     */
    public function getEstAdmisAttribute(): bool
    {
        return $this->statut === 'admis';
    }

    /**
     * Détermine si l'élève est ajourné
     */
    public function getEstAjourneAttribute(): bool
    {
        return $this->statut === 'ajourne';
    }

    /**
     * Calcule la mention selon la moyenne obtenue
     */
    public function calculerMention(): ?string
    {
        if ($this->moyenne_obtenue === null) {
            return null;
        }

        if ($this->moyenne_obtenue >= 16) {
            return 'Très Bien';
        } elseif ($this->moyenne_obtenue >= 14) {
            return 'Bien';
        } elseif ($this->moyenne_obtenue >= 12) {
            return 'Assez Bien';
        } elseif ($this->moyenne_obtenue >= 10) {
            return 'Passable';
        }

        return 'Insuffisant';
    }

    /**
     * Scope pour les inscrits
     */
    public function scopeInscrits($query)
    {
        return $query->where('statut', 'inscrit');
    }

    /**
     * Scope pour les admis
     */
    public function scopeAdmis($query)
    {
        return $query->where('statut', 'admis');
    }

    /**
     * Scope pour les ajournés
     */
    public function scopeAjournes($query)
    {
        return $query->where('statut', 'ajourne');
    }

    /**
     * Scope pour les absents
     */
    public function scopeAbsents($query)
    {
        return $query->where('statut', 'absent');
    }
}
