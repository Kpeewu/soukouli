<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamenOfficiel extends Model
{
    use HasFactory, HasHashidRouting;

    protected $table = 'examens_officiels';

    protected $fillable = [
        'nom',
        'code',
        'description',
        'cycle_id',
        'niveau_requis'
    ];

    /**
     * Relation avec le cycle
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    /**
     * Relation avec les sessions d'examen
     */
    public function sessions()
    {
        return $this->hasMany(SessionExamen::class);
    }

    /**
     * Relation avec les promotions qui ont cet examen
     */
    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Récupère la session active pour une année scolaire
     */
    public function getSessionPourAnnee($anneeScolaireId)
    {
        return $this->sessions()
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->first();
    }

    /**
     * Scope pour filtrer par cycle
     */
    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }
}
