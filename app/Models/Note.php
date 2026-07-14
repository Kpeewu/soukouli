<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable  = [
        'valeur',
        'eleve_id',
        'evaluation_id',
        'trimestre_id',
        'note_source_id'
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function trimestre()
    {
        return $this->belongsTo(Trimestre::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    /**
     * Note d'origine si celle-ci est une copie reportée suite à un changement
     * de classe de l'élève
     */
    public function source()
    {
        return $this->belongsTo(Note::class, 'note_source_id');
    }

    /**
     * Copies créées dans d'autres classes à partir de cette note
     */
    public function copies()
    {
        return $this->hasMany(Note::class, 'note_source_id');
    }
}
