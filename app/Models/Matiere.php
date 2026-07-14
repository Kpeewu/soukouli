<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matiere extends Model
{
    use HasFactory, HasHashidRouting;

    protected $fillable = [
        'intitule'
    ];

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class);
    }

    public function cours()
    {
        return $this->hasMany(Cours::class);
    }
}
