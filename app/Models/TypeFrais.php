<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeFrais extends Model
{
    use HasFactory, HasHashidRouting;

    protected $table = 'types_frais';

    protected $fillable = [
        'nom',
        'code',
        'description',
        'obligatoire',
        'actif'
    ];

    protected $casts = [
        'obligatoire' => 'boolean',
        'actif' => 'boolean'
    ];

    public function configurations()
    {
        return $this->hasMany(ConfigurationFrais::class);
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeObligatoire($query)
    {
        return $query->where('obligatoire', true);
    }
}
