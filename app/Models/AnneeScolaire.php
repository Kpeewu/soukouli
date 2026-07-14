<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnneeScolaire extends Model
{
    use HasFactory, HasHashidRouting;

    protected $fillable = [
        'annee',
        'courant'
    ];

    /**
     * Annee scolaire "reelle" (courant = true en base). Reservee aux flux
     * administratifs (bascule d'annee, passage en annee superieure) : pour
     * tout le reste, utiliser getAnneeScolaireActive().
     */
    public static function getAnneeScolaire()
    {
        return AnneeScolaire::where('courant', true)->first();
    }

    /**
     * Annee scolaire "active" pour l'utilisateur connecte : son choix
     * personnel (users.annee_scolaire_id) s'il en a un, sinon l'annee
     * reelle courante.
     */
    public static function getAnneeScolaireActive(): ?self
    {
        $user = auth()->user();

        if ($user && $user->annee_scolaire_id) {
            return $user->anneeScolaireActive ?? self::find($user->annee_scolaire_id);
        }

        return self::getAnneeScolaire();
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
