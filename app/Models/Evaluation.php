<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory, HasHashidRouting;

    protected $fillable = [
        'intitule',
        'type',
        'date',
        'note_maximale',
        'cours_id',
        'evaluation_source_id'
    ];

    public function cours()
    {
        return $this->belongsTo(Cours::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Évaluation d'origine si celle-ci est un clone reporté suite à un
     * changement de classe de l'élève
     */
    public function source()
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_source_id');
    }

    /**
     * Clones créés dans d'autres classes à partir de cette évaluation
     */
    public function clones()
    {
        return $this->hasMany(Evaluation::class, 'evaluation_source_id');
    }

    /**
     * Statistiques de l'évaluation : effectif noté, moyenne générale, nombre d'élèves ayant
     * la moyenne (>= la moitié du barème), taux de réussite, notes extrêmes.
     */
    public function statistiques(): array
    {
        $valeurs = $this->notes->pluck('valeur');
        $effectif = $valeurs->count();

        if ($effectif === 0) {
            return [
                'effectif' => 0,
                'moyenne' => null,
                'note_max' => null,
                'note_min' => null,
                'nombre_moyennes' => 0,
                'taux_reussite' => null,
            ];
        }

        $seuil = $this->note_maximale / 2;
        $nombreMoyennes = $valeurs->filter(fn ($valeur) => $valeur >= $seuil)->count();

        return [
            'effectif' => $effectif,
            'moyenne' => round($valeurs->avg(), 2),
            'note_max' => $valeurs->max(),
            'note_min' => $valeurs->min(),
            'nombre_moyennes' => $nombreMoyennes,
            'taux_reussite' => round($nombreMoyennes / $effectif * 100, 1),
        ];
    }
}
