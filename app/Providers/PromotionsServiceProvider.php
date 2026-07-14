<?php

namespace App\Providers;

use App\Models\AnneeScolaire;
use App\Models\Cycle;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class PromotionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Peuple les variables de sidebar pour un utilisateur restreint a un cycle (ou a tous les
     * cycles s'il porte le role "general" correspondant, ex: directeur_general). Retourne false
     * si l'utilisateur n'a ni cycle gere ni role general, pour laisser l'appelant continuer sa
     * recherche de branche (ex: professeur) au lieu de court-circuiter avec des valeurs vides.
     */
    private function applySidebarCycleScope($view, AnneeScolaire $anneeScolaire, ?Cycle $managedCycle, bool $isGeneral): bool
    {
        if ($managedCycle) {
            $sidebarPromotions = $anneeScolaire->promotions()
                ->where('cycle_id', $managedCycle->id)
                ->with('cycle', 'classes')
                ->get();

            $view->with('sidebarPromotions', $sidebarPromotions);
            $view->with('sidebarCycles', collect([$managedCycle]));
            $view->with('currentCycle', $managedCycle);
            $view->with('isAdmin', false);
            $view->with('isProfesseur', false);
            $view->with('professeurCours', collect());
            return true;
        }

        if ($isGeneral) {
            $sidebarCycles = Cycle::orderBy('ordre')->get();
            $sidebarPromotions = $anneeScolaire->promotions()
                ->with('cycle', 'classes')
                ->get()
                ->sortBy(function ($promotion) {
                    return $promotion->cycle ? $promotion->cycle->ordre : 999;
                });

            $view->with('sidebarPromotions', $sidebarPromotions);
            $view->with('sidebarCycles', $sidebarCycles);
            $view->with('currentCycle', null);
            $view->with('isAdmin', false);
            $view->with('isProfesseur', false);
            $view->with('professeurCours', collect());
            return true;
        }

        return false;
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        view()->composer('*', function ($view) {
            $anneeScolaire = AnneeScolaire::getAnneeScolaireActive();

            if (!$anneeScolaire) {
                $view->with('sidebarPromotions', collect());
                $view->with('sidebarCycles', collect());
                $view->with('currentCycle', null);
                $view->with('isAdmin', false);
                return;
            }

            $user = Auth::user();

            if (!$user) {
                $view->with('sidebarPromotions', collect());
                $view->with('sidebarCycles', collect());
                $view->with('currentCycle', null);
                $view->with('isAdmin', false);
                return;
            }

            // Admin voit tout
            if ($user->isAdmin()) {
                $sidebarCycles = Cycle::orderBy('ordre')->get();
                $sidebarPromotions = $anneeScolaire->promotions()
                    ->with('cycle', 'classes')
                    ->get()
                    ->sortBy(function ($promotion) {
                        return $promotion->cycle ? $promotion->cycle->ordre : 999;
                    });

                $view->with('sidebarPromotions', $sidebarPromotions);
                $view->with('sidebarCycles', $sidebarCycles);
                $view->with('currentCycle', null);
                $view->with('isAdmin', true);
                $view->with('isProfesseur', false);
                $view->with('professeurCours', collect());
                return;
            }

            // Directeur voit uniquement son cycle (ou tous les cycles si directeur_general)
            if ($user->isDirecteur() && $this->applySidebarCycleScope($view, $anneeScolaire, $user->getManagedCycle(), $user->hasRole('directeur_general'))) {
                return;
            }

            // Secretaire voit uniquement son cycle (ou tous les cycles si secretaire_general)
            if ($user->isSecretaire() && $this->applySidebarCycleScope($view, $anneeScolaire, $user->getSecretaireCycle(), $user->hasRole('secretaire_general'))) {
                return;
            }

            // Comptable voit uniquement son cycle (ou tous les cycles si comptable_general)
            if ($user->isComptable() && $this->applySidebarCycleScope($view, $anneeScolaire, $user->getComptableCycle(), $user->hasRole('comptable_general'))) {
                return;
            }

            // Surveillant voit uniquement son cycle (ou tous les cycles si surveillant_general)
            if ($user->isSurveillant() && $this->applySidebarCycleScope($view, $anneeScolaire, $user->getSurveillantCycle(), $user->hasRole('surveillant_general'))) {
                return;
            }

            // Professeur voit uniquement ses cours
            if ($user->isProfesseur() && $user->professeur) {
                $professeur = $user->professeur;
                // Eager load classe.promotion.cycle : necessaire pour grouper "Mes classes"
                // par cycle dans le sidebar sans declencher de lazy loading par cours.
                $professeurCours = $professeur->cours()->with('classe.promotion.cycle')->get();
                $coursIds = $professeurCours->pluck('id');
                $classeIds = $professeurCours->pluck('classe_id')->unique();

                // Récupérer les classes où le professeur enseigne
                $classes = \App\Models\Classe::whereIn('id', $classeIds)
                    ->with('promotion.cycle')
                    ->get();

                // Récupérer les promotions associées
                $promotionIds = $classes->pluck('promotion_id')->unique();
                $sidebarPromotions = $anneeScolaire->promotions()
                    ->whereIn('id', $promotionIds)
                    ->with('cycle', 'classes')
                    ->get();

                // Filtrer pour ne montrer que les classes où le professeur enseigne
                $sidebarPromotions = $sidebarPromotions->map(function ($promotion) use ($classeIds) {
                    $promotion->setRelation('classes', $promotion->classes->filter(function ($classe) use ($classeIds) {
                        return $classeIds->contains($classe->id);
                    }));
                    return $promotion;
                });

                $view->with('sidebarPromotions', $sidebarPromotions);
                $view->with('sidebarCycles', collect());
                $view->with('currentCycle', null);
                $view->with('isAdmin', false);
                $view->with('isProfesseur', true);
                $view->with('professeurCours', $professeurCours);
                return;
            }

            // Par défaut, rien
            $view->with('sidebarPromotions', collect());
            $view->with('sidebarCycles', collect());
            $view->with('currentCycle', null);
            $view->with('isAdmin', false);
            $view->with('isProfesseur', false);
            $view->with('professeurCours', collect());
        });
    }
}
