<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasHashidRouting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasHashidRouting;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'nom',
        'prenom',
        'telephone',
        'civilite',
        'annee_scolaire_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function professeur()
    {
        return $this->hasOne(Professeur::class);
    }

    public function anneeScolaireActive()
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    /**
     * Vérifie si l'utilisateur possède un rôle correspondant à ce type,
     * soit exact (ex: 'professeur'), soit scopé a un cycle (ex: 'professeur_universitaire').
     */
    private function hasRoleType(string $type): bool
    {
        return $this->roles->contains(
            fn ($role) => $role->name === $type || str_starts_with($role->name, $type . '_')
        );
    }

    /**
     * Résout le cycle correspondant au rôle "{prefix}_{code}" de l'utilisateur, si présent.
     */
    private function resolveCycleForRolePrefix(string $prefix): ?Cycle
    {
        $role = $this->roles->first(
            fn ($role) => $role->name === $prefix || str_starts_with($role->name, $prefix . '_')
        );

        if (!$role || $role->name === $prefix) {
            return null;
        }

        $cycleCode = strtoupper(Str::after($role->name, $prefix . '_'));

        return Cycle::where('code', $cycleCode)->first();
    }

    /**
     * Retourne le texte masculin ou féminin selon la civilité de l'utilisateur.
     * Par défaut (civilité absente ou 'M'), retourne la forme masculine.
     */
    public function accordTitre(string $masculin, string $feminin): string
    {
        return $this->civilite === 'Mme' ? $feminin : $masculin;
    }

    /**
     * Retourne le cycle géré par ce directeur (null si directeur_general, i.e. tous les cycles)
     */
    public function getManagedCycle(): ?Cycle
    {
        if ($this->hasRole('directeur_general')) {
            return null;
        }

        return $this->resolveCycleForRolePrefix('directeur');
    }

    /**
     * Retourne le cycle auquel ce comptable est restreint (null si comptable_general, i.e. tous les cycles)
     */
    public function getComptableCycle(): ?Cycle
    {
        if ($this->hasRole('comptable_general')) {
            return null;
        }

        return $this->resolveCycleForRolePrefix('comptable');
    }

    /**
     * Retourne le cycle auquel ce secretaire est restreint (null si secretaire_general, i.e. tous les cycles)
     */
    public function getSecretaireCycle(): ?Cycle
    {
        if ($this->hasRole('secretaire_general')) {
            return null;
        }

        return $this->resolveCycleForRolePrefix('secretaire');
    }

    /**
     * Retourne le cycle auquel ce surveillant est restreint (null si surveillant_general, i.e. tous les cycles)
     */
    public function getSurveillantCycle(): ?Cycle
    {
        if ($this->hasRole('surveillant_general')) {
            return null;
        }

        return $this->resolveCycleForRolePrefix('surveillant');
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Vérifie si l'utilisateur est un directeur
     */
    public function isDirecteur(): bool
    {
        return $this->hasRoleType('directeur');
    }

    /**
     * Vérifie si l'utilisateur est un professeur
     */
    public function isProfesseur(): bool
    {
        return $this->hasRoleType('professeur');
    }

    /**
     * Vérifie si l'utilisateur est un comptable
     */
    public function isComptable(): bool
    {
        return $this->hasRoleType('comptable');
    }

    /**
     * Vérifie si l'utilisateur est un secretaire
     */
    public function isSecretaire(): bool
    {
        return $this->hasRoleType('secretaire');
    }

    /**
     * Vérifie si l'utilisateur est un surveillant
     */
    public function isSurveillant(): bool
    {
        return $this->hasRoleType('surveillant');
    }

    /**
     * Retourne le libellé de rôle accordé au genre de l'utilisateur (pour affichage)
     */
    public function roleLabelAccorde(): string
    {
        if ($this->isAdmin()) {
            return $this->accordTitre('Administrateur', 'Administratrice');
        }
        if ($this->isDirecteur()) {
            return $this->accordTitre('Directeur', 'Directrice');
        }
        if ($this->isComptable()) {
            return 'Comptable';
        }
        if ($this->isSecretaire()) {
            return 'Secrétaire';
        }
        if ($this->isSurveillant()) {
            return $this->accordTitre('Surveillant', 'Surveillante');
        }
        if ($this->isProfesseur()) {
            return $this->accordTitre('Professeur', 'Professeure');
        }

        return '';
    }

    /**
     * Retourne les paiements enregistrés par ce comptable
     */
    public function paiementsEnregistres()
    {
        return $this->hasMany(Paiement::class, 'comptable_id');
    }

    /**
     * Retourne les reçus émis par ce comptable
     */
    public function recusEmis()
    {
        return $this->hasMany(Recu::class, 'comptable_id');
    }

    /**
     * Vérifie si le professeur enseigne ce cours
     */
    public function enseigneCours(Cours $cours): bool
    {
        if (!$this->isProfesseur()) {
            return false;
        }

        $professeur = $this->professeur;
        if (!$professeur) {
            return false;
        }

        return $cours->professeur_id === $professeur->id;
    }

    /**
     * Retourne les cours enseignés par ce professeur
     */
    public function getCoursEnseignes()
    {
        if (!$this->isProfesseur() || !$this->professeur) {
            return collect();
        }

        return $this->professeur->cours;
    }

    /**
     * Vérifie si l'utilisateur peut modifier ou supprimer les évaluations/interrogations de ce
     * cours : le professeur qui l'enseigne, ou le secretaire du cycle du cours. Les directeurs,
     * le directeur general et le secretaire general n'ont qu'un droit de consultation.
     */
    public function canManageCours(Cours $cours): bool
    {
        if ($this->enseigneCours($cours)) {
            return true;
        }

        if ($secretaireCycle = $this->getSecretaireCycle()) {
            return $cours->classe->promotion->cycle_id === $secretaireCycle->id;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut modifier ou supprimer cette évaluation/interrogation.
     */
    public function canManageEvaluation(Evaluation $evaluation): bool
    {
        return $evaluation->cours && $this->canManageCours($evaluation->cours);
    }

    /**
     * Vérifie si l'utilisateur peut supprimer cette évaluation. Un devoir ou une composition ne
     * peut être supprimé que par le secrétaire du cycle (création et suppression réservées au
     * secrétaire, le professeur ne peut que saisir les notes) ; une interrogation suit la même
     * règle que sa modification (professeur du cours ou secrétaire de cycle).
     */
    public function canDeleteEvaluation(Evaluation $evaluation): bool
    {
        if (!$evaluation->cours) {
            return false;
        }

        if (in_array($evaluation->type, ['devoir', 'composition'])) {
            $secretaireCycle = $this->getSecretaireCycle();
            return $secretaireCycle && $evaluation->cours->classe->promotion->cycle_id === $secretaireCycle->id;
        }

        return $this->canManageEvaluation($evaluation);
    }

    /**
     * Retourne les cycles accessibles par l'utilisateur
     */
    public function getAccessibleCycles()
    {
        if ($this->isAdmin() || $this->hasRole('directeur_general') || $this->hasRole('comptable_general') || $this->hasRole('secretaire_general') || $this->hasRole('surveillant_general')) {
            return Cycle::orderBy('ordre')->get();
        }

        if ($this->isProfesseur() && $this->professeur) {
            return Cycle::whereIn('id', $this->professeur->getCycleIds())->orderBy('ordre')->get();
        }

        $cycle = $this->getManagedCycle() ?? $this->getComptableCycle() ?? $this->getSecretaireCycle() ?? $this->getSurveillantCycle();
        return $cycle ? collect([$cycle]) : collect();
    }

    /**
     * Retourne les IDs des cycles accessibles
     */
    public function getAccessibleCycleIds(): array
    {
        if ($this->isAdmin() || $this->hasRole('directeur_general') || $this->hasRole('comptable_general') || $this->hasRole('secretaire_general') || $this->hasRole('surveillant_general')) {
            return Cycle::pluck('id')->toArray();
        }

        if ($this->isProfesseur() && $this->professeur) {
            return $this->professeur->getCycleIds();
        }

        $cycle = $this->getManagedCycle() ?? $this->getComptableCycle() ?? $this->getSecretaireCycle() ?? $this->getSurveillantCycle();
        return $cycle ? [$cycle->id] : [];
    }
}
