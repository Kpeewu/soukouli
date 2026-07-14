<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Classe;
use App\Models\Promotion;
use App\Models\Eleve;
use App\Models\Professeur;

class CheckCycleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $resourceType
     */
    public function handle(Request $request, Closure $next, string $resourceType = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Admin a accès à tout
        if ($user->isAdmin()) {
            return $next($request);
        }

        $managedCycle = $user->getManagedCycle();

        if (!$managedCycle) {
            abort(403, 'Accès refusé.');
        }

        // Vérifier l'accès selon le type de ressource
        $resourceCycleId = $this->getResourceCycleId($request, $resourceType);

        if ($resourceCycleId && $resourceCycleId !== $managedCycle->id) {
            abort(403, 'Vous n\'avez pas accès à cette ressource.');
        }

        return $next($request);
    }

    /**
     * Récupère le cycle_id de la ressource demandée
     */
    private function getResourceCycleId(Request $request, ?string $resourceType): ?int
    {
        switch ($resourceType) {
            case 'classe':
                $classe = $request->route('classe');
                if ($classe instanceof Classe) {
                    $classe->load('promotion');
                    return $classe->promotion?->cycle_id;
                }
                if (is_numeric($classe)) {
                    $classe = Classe::with('promotion')->find($classe);
                    return $classe?->promotion?->cycle_id;
                }
                break;

            case 'promotion':
                $promotion = $request->route('promotion');
                if ($promotion instanceof Promotion) {
                    return $promotion->cycle_id;
                }
                if (is_numeric($promotion)) {
                    $promotion = Promotion::find($promotion);
                    return $promotion?->cycle_id;
                }
                break;

            case 'eleve':
                $eleve = $request->route('eleve');
                if ($eleve instanceof Eleve) {
                    $classe = $eleve->classes()->with('promotion')->first();
                    return $classe?->promotion?->cycle_id;
                }
                if (is_numeric($eleve)) {
                    $eleve = Eleve::find($eleve);
                    if ($eleve) {
                        $classe = $eleve->classes()->with('promotion')->first();
                        return $classe?->promotion?->cycle_id;
                    }
                }
                break;

            case 'professeur':
                $professeur = $request->route('professeur');
                if ($professeur instanceof Professeur) {
                    return $professeur->cycle_id;
                }
                if (is_numeric($professeur)) {
                    $professeur = Professeur::find($professeur);
                    return $professeur?->cycle_id;
                }
                break;
        }

        return null;
    }
}
