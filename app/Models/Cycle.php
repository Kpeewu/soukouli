<?php

namespace App\Models;

use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    use HasFactory, HasHashidRouting;

    const MATERNELLE = 'MATERNELLE';
    const PRIMAIRE = 'PRIMAIRE';
    const COLLEGE = 'COLLEGE';
    const LYCEE = 'LYCEE';

    protected $fillable = [
        'nom',
        'code',
        'description',
        'ordre',
        'supports_semestre',
        'niveaux',
        'cycle_suivant_id',
    ];

    protected $casts = [
        'supports_semestre' => 'boolean',
        'niveaux' => 'array',
    ];

    /**
     * Relation avec les promotions
     */
    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Relation avec les professeurs
     */
    public function professeurs()
    {
        return $this->hasMany(Professeur::class);
    }

    /**
     * Relation avec les examens officiels
     */
    public function examensOfficiels()
    {
        return $this->hasMany(ExamenOfficiel::class);
    }

    /**
     * Relation avec le cycle suivant
     */
    public function cycleSuivant()
    {
        return $this->belongsTo(Cycle::class, 'cycle_suivant_id');
    }

    /**
     * Relation inverse - cycle precedent
     */
    public function cyclePrecedent()
    {
        return $this->hasOne(Cycle::class, 'cycle_suivant_id');
    }

    /**
     * Retourne les noms de promotions par defaut pour ce cycle
     * Utilise le champ 'niveaux' de la base de donnees ou les valeurs par defaut
     */
    public function getDefaultPromotions(): array
    {
        // Si le champ niveaux est defini, l'utiliser
        if (!empty($this->niveaux)) {
            return $this->niveaux;
        }

        // Fallback sur les valeurs par defaut codees en dur
        return match($this->code) {
            self::MATERNELLE => ['Maternelle 1', 'Maternelle 2'],
            self::PRIMAIRE => ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'],
            self::COLLEGE => ['6ème', '5ème', '4ème', '3ème'],
            self::LYCEE => ['2nde', '1ere', 'Tle'],
            default => []
        };
    }

    /**
     * Retourne le niveau suivant dans ce cycle
     * @param string $niveauActuel Le niveau actuel
     * @return string|null Le niveau suivant ou null si dernier niveau
     */
    public function getNiveauSuivant(string $niveauActuel): ?string
    {
        $niveaux = $this->getDefaultPromotions();
        $index = array_search($niveauActuel, $niveaux);

        if ($index === false || $index >= count($niveaux) - 1) {
            return null;
        }

        return $niveaux[$index + 1];
    }

    /**
     * Verifie si un niveau est le dernier du cycle
     */
    public function estDernierNiveau(string $niveau): bool
    {
        $niveaux = $this->getDefaultPromotions();
        return !empty($niveaux) && end($niveaux) === $niveau;
    }

    /**
     * Retourne le premier niveau du cycle
     */
    public function getPremierNiveau(): ?string
    {
        $niveaux = $this->getDefaultPromotions();
        return $niveaux[0] ?? null;
    }

    /**
     * Retourne le dernier niveau du cycle
     */
    public function getDernierNiveau(): ?string
    {
        $niveaux = $this->getDefaultPromotions();
        return !empty($niveaux) ? end($niveaux) : null;
    }

    /**
     * Verifie si ce cycle a un cycle suivant
     */
    public function hasCycleSuivant(): bool
    {
        return $this->cycle_suivant_id !== null;
    }

    /**
     * Retourne les promotions avec examens officiels pour ce cycle
     */
    public function getPromotionsAvecExamen(): array
    {
        return match($this->code) {
            self::PRIMAIRE => ['CM2'],
            self::COLLEGE => ['3ème'],
            self::LYCEE => ['1ere', 'Tle'],
            default => []
        };
    }

    /**
     * Scope pour ordonner par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }
}
