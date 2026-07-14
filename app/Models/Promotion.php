<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory, HasHashidRouting;

    protected $fillable = [
        'nom',
        'ordre',
        'annee_scolaire_id',
        'cycle_id',
        'type_periode',
        'a_examen_officiel',
        'examen_officiel_id'
    ];

    protected $casts = [
        'a_examen_officiel' => 'boolean',
    ];

    public function trimestres()
    {
        return $this->hasMany(Trimestre::class);
    }

    public function matieres()
    {
        return $this->belongsToMany(Matiere::class);
    }

    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    /**
     * Relation avec le cycle
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    /**
     * Relation avec l'examen officiel
     */
    public function examenOfficiel()
    {
        return $this->belongsTo(ExamenOfficiel::class);
    }

    /**
     * Nombre de périodes selon le type (trimestre ou semestre)
     */
    public function getNombrePeriodesAttribute(): int
    {
        return $this->type_periode === 'semestre' ? 2 : 3;
    }

    /**
     * Vérifie si cette promotion utilise des semestres
     */
    public function utilisesSemestres(): bool
    {
        return $this->type_periode === 'semestre';
    }

    /**
     * Vérifie si cette promotion utilise des trimestres
     */
    public function utilisesTrimestres(): bool
    {
        return $this->type_periode === 'trimestre';
    }

    /**
     * Scope pour filtrer par cycle
     */
    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    /**
     * Scope pour filtrer par cycles multiples
     */
    public function scopeForCycles($query, array $cycleIds)
    {
        return $query->whereIn('cycle_id', $cycleIds);
    }

    /**
     * Scope pour les promotions avec examen
     */
    public function scopeAvecExamen($query)
    {
        return $query->where('a_examen_officiel', true);
    }
}
