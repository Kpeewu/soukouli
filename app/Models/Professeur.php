<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Professeur extends Model
{
    use HasFactory, HasHashidRouting;

    protected $fillable = [
        'nom',
        'prenom',
        'contact',
        'sexe',
        'user_id',
        'cycle_id'
    ];

    // Le champ contact est juste un numéro de téléphone (string simple)

    public function cours()
    {
        return $this->hasMany(Cours::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    /**
     * Relation avec le cycle
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    /**
     * Scope pour filtrer les professeurs associes a un cycle : leur cycle de rattachement,
     * mais aussi tout professeur qui enseigne un cours ou est titulaire d'une classe dans ce
     * cycle. Un professeur peut intervenir dans plusieurs cycles alors que son cycle de
     * rattachement (cycle_id) n'en retient qu'un seul.
     */
    public function scopeForCycle($query, $cycleId)
    {
        return $query->where(function ($q) use ($cycleId) {
            $q->where('cycle_id', $cycleId)
                ->orWhereHas('cours.classe.promotion', fn ($q2) => $q2->where('cycle_id', $cycleId))
                ->orWhereHas('classes.promotion', fn ($q2) => $q2->where('cycle_id', $cycleId));
        });
    }

    /**
     * Scope pour filtrer par cycles multiples (voir scopeForCycle)
     */
    public function scopeForCycles($query, array $cycleIds)
    {
        return $query->where(function ($q) use ($cycleIds) {
            $q->whereIn('cycle_id', $cycleIds)
                ->orWhereHas('cours.classe.promotion', fn ($q2) => $q2->whereIn('cycle_id', $cycleIds))
                ->orWhereHas('classes.promotion', fn ($q2) => $q2->whereIn('cycle_id', $cycleIds));
        });
    }

    /**
     * Vérifie si ce professeur est rattaché, enseigne un cours, ou est titulaire d'une classe
     * dans le cycle donné.
     */
    public function intervientDansCycle(int $cycleId): bool
    {
        if ($this->cycle_id === $cycleId) {
            return true;
        }

        if ($this->cours()->whereHas('classe.promotion', fn ($q) => $q->where('cycle_id', $cycleId))->exists()) {
            return true;
        }

        return $this->classes()->whereHas('promotion', fn ($q) => $q->where('cycle_id', $cycleId))->exists();
    }

    /**
     * Retourne les IDs de tous les cycles ou ce professeur intervient : son cycle de
     * rattachement, les cycles ou il enseigne un cours, et les cycles des classes dont il
     * est titulaire (un professeur peut intervenir dans plusieurs cycles).
     */
    public function getCycleIds(): array
    {
        $this->loadMissing(['cours.classe.promotion', 'classes.promotion']);

        return collect([$this->cycle_id])
            ->merge($this->cours->pluck('classe.promotion.cycle_id'))
            ->merge($this->classes->pluck('promotion.cycle_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
