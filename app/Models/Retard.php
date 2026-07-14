<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retard extends Model
{
    use HasFactory, HasHashidRouting;

    protected $fillable = [
        'date',
        'heure_arrive',
        'justification',
        'assiduite_id',
    ];

    public function assiduite()
    {
        return $this->belongsTo(Assiduite::class);
    }
}
