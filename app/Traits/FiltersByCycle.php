<?php

namespace App\Traits;

use App\Models\Cycle;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait FiltersByCycle
{
    /**
     * Retourne le cycle de l'utilisateur connecté ou null pour admin
     */
    protected function getUserCycle(): ?Cycle
    {
        $user = Auth::user();

        if (!$user || $user->isAdmin()) {
            return null;
        }

        return $user->getManagedCycle() ?? $user->getComptableCycle() ?? $user->getSecretaireCycle() ?? $user->getSurveillantCycle();
    }

    /**
     * Retourne les IDs des cycles accessibles
     */
    protected function getAccessibleCycleIds(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        return $user->getAccessibleCycleIds();
    }

    /**
     * Retourne les cycles accessibles
     */
    protected function getAccessibleCycles()
    {
        $user = Auth::user();

        if (!$user) {
            return collect();
        }

        return $user->getAccessibleCycles();
    }

    /**
     * Filtre les promotions selon le cycle de l'utilisateur
     */
    protected function getFilteredPromotions()
    {
        $anneeScolaire = AnneeScolaire::getAnneeScolaireActive();

        if (!$anneeScolaire) {
            return collect();
        }

        $cycleIds = $this->getAccessibleCycleIds();

        if (empty($cycleIds)) {
            return collect();
        }

        return $anneeScolaire->promotions()
            ->whereIn('cycle_id', $cycleIds)
            ->with('cycle', 'classes')
            ->get();
    }

    /**
     * Vérifie si l'utilisateur a accès à un cycle spécifique
     */
    protected function canAccessCycle(int $cycleId): bool
    {
        return in_array($cycleId, $this->getAccessibleCycleIds());
    }

    /**
     * Vérifie si l'utilisateur a accès à une promotion spécifique
     */
    protected function canAccessPromotion($promotion): bool
    {
        if (!$promotion || !$promotion->cycle_id) {
            return false;
        }
        return $this->canAccessCycle($promotion->cycle_id);
    }

    /**
     * Vérifie si l'utilisateur a accès à une classe spécifique
     */
    protected function canAccessClasse($classe): bool
    {
        if (!$classe || !$classe->promotion) {
            return false;
        }
        return $this->canAccessPromotion($classe->promotion);
    }

    /**
     * Vérifie si l'utilisateur a accès à un élève spécifique
     */
    protected function canAccessEleve($eleve): bool
    {
        $classe = $eleve->classes()->with('promotion.cycle')->orderByDesc('classe_eleve.id')->first();
        if (!$classe) {
            return false;
        }
        return $this->canAccessClasse($classe);
    }

    /**
     * Vérifie si l'utilisateur a accès à un professeur spécifique : son cycle de rattachement,
     * mais aussi tout cycle où il enseigne un cours ou est titulaire d'une classe (un
     * professeur peut intervenir dans plusieurs cycles).
     */
    protected function canAccessProfesseur($professeur): bool
    {
        if (!$professeur->cycle_id) {
            // Si le professeur n'a pas de cycle de rattachement,
            // on permet l'accès seulement aux admins
            return Auth::user()->isAdmin();
        }

        foreach ($this->getAccessibleCycleIds() as $cycleId) {
            if ($professeur->intervientDansCycle($cycleId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Avorte avec 403 si l'accès au cycle n'est pas autorisé
     */
    protected function authorizeAccessCycle(int $cycleId): void
    {
        if (!$this->canAccessCycle($cycleId)) {
            abort(403, 'Vous n\'avez pas accès à ce cycle.');
        }
    }

    /**
     * Resout le cycle demande en query string ('cycle_id'), restreint aux cycles accessibles.
     * Sans cycle_id fourni, un utilisateur limite a un seul cycle (ex: comptable_{cycle}) y est
     * automatiquement cantonne, au lieu de laisser passer un filtre "tous cycles" par defaut.
     */
    protected function resolveRequestedCycle(Request $request): ?Cycle
    {
        if ($request->filled('cycle_id')) {
            $this->authorizeAccessCycle((int) $request->cycle_id);
            return Cycle::find($request->cycle_id);
        }

        $user = Auth::user();
        if ($user->isAdmin() || $user->hasRole('directeur_general') || $user->hasRole('comptable_general') || $user->hasRole('secretaire_general') || $user->hasRole('surveillant_general')) {
            return null;
        }

        $cycleIds = $this->getAccessibleCycleIds();
        if (empty($cycleIds)) {
            abort(403, 'Vous n\'avez pas accès à ce cycle.');
        }

        return Cycle::find($cycleIds[0]);
    }

    /**
     * Avorte avec 403 si l'accès à la promotion n'est pas autorisé
     */
    protected function authorizeAccessPromotion($promotion): void
    {
        if (!$this->canAccessPromotion($promotion)) {
            abort(403, 'Vous n\'avez pas accès à cette promotion.');
        }
    }

    /**
     * Avorte avec 403 si l'accès à la classe n'est pas autorisé
     */
    protected function authorizeAccessClasse($classe): void
    {
        if (!$this->canAccessClasse($classe)) {
            abort(403, 'Vous n\'avez pas accès à cette classe.');
        }
    }
}
