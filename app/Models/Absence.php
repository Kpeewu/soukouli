<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    use HasFactory, HasHashidRouting;

    protected $fillable = [
        'date',
        'nombre_heure',
        'justification',
        'assiduite_id'
    ];

    public function assiduite()
    {
        return $this->belongsTo(Assiduite::class);
    }
}
