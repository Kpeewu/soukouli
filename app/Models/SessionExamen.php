<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionExamen extends Model
{
    use HasFactory, HasHashidRouting;

    protected $table = 'sessions_examen';

    protected $fillable = [
        'examen_officiel_id',
        'annee_scolaire_id',
        'date_debut',
        'date_fin',
        'statut'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    /**
     * Relation avec l'examen officiel
     */
    public function examenOfficiel()
    {
        return $this->belongsTo(ExamenOfficiel::class);
    }

    /**
     * Relation avec l'année scolaire
     */
    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    /**
     * Relation avec les inscriptions
     */
    public function inscriptions()
    {
        return $this->hasMany(InscriptionExamen::class);
    }

    /**
     * Récupère les élèves inscrits
     */
    public function eleves()
    {
        return $this->hasManyThrough(
            Eleve::class,
            InscriptionExamen::class,
            'session_examen_id',
            'id',
            'id',
            'eleve_id'
        );
    }

    /**
     * Nombre d'inscrits
     */
    public function getNombreInscritsAttribute(): int
    {
        return $this->inscriptions()->count();
    }

    /**
     * Nombre d'admis
     */
    public function getNombreAdmisAttribute(): int
    {
        return $this->inscriptions()->where('statut', 'admis')->count();
    }

    /**
     * Taux de réussite
     */
    public function getTauxReussiteAttribute(): float
    {
        $total = $this->nombre_inscrits;
        if ($total === 0) {
            return 0;
        }
        return round(($this->nombre_admis / $total) * 100, 2);
    }

    /**
     * Scope pour les sessions programmées
     */
    public function scopeProgramme($query)
    {
        return $query->where('statut', 'programme');
    }

    /**
     * Scope pour les sessions en cours
     */
    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    /**
     * Scope pour les sessions terminées
     */
    public function scopeTermine($query)
    {
        return $query->where('statut', 'termine');
    }

    /**
     * Scope pour l'année scolaire courante
     */
    public function scopeAnneeCourante($query)
    {
        $anneeScolaire = AnneeScolaire::getAnneeScolaireActive();
        if ($anneeScolaire) {
            return $query->where('annee_scolaire_id', $anneeScolaire->id);
        }
        return $query;
    }
}
