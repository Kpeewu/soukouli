<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FilterByCycle
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Admin a accès à tout
        if ($user->isAdmin()) {
            $request->merge([
                'user_is_admin' => true,
                'user_cycle_id' => null,
                'user_cycle' => null
            ]);
            return $next($request);
        }

        // Directeur général - accès à tout, aucun cycle spécifique
        if ($user->hasRole('directeur_general')) {
            $request->merge([
                'user_is_admin' => false,
                'user_cycle_id' => null,
                'user_cycle' => null
            ]);
            return $next($request);
        }

        // Directeur - vérifier et injecter le cycle
        if ($user->isDirecteur()) {
            $managedCycle = $user->getManagedCycle();

            if (!$managedCycle) {
                abort(403, 'Aucun cycle assigné à votre compte.');
            }

            // Partager le cycle dans la requête pour utilisation dans les contrôleurs
            $request->merge([
                'user_is_admin' => false,
                'user_cycle_id' => $managedCycle->id,
                'user_cycle' => $managedCycle
            ]);
        }

        return $next($request);
    }
}
