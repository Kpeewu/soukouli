<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulletinMatiereOrdre extends Model
{
    use HasFactory;

    protected $fillable = ['cycle_id', 'niveau', 'matiere_id', 'ordre'];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }
}
