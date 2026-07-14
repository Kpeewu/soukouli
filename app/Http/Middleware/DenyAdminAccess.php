<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenyAdminAccess
{
    /**
     * Bloque l'accès aux routes de gestion scolaire courante / comptabilité pour l'admin,
     * dont le rôle est restreint à la gestion du système (cycles, années, utilisateurs, examens officiels).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin()) {
            abort(403, 'Cette section est réservée à la gestion scolaire, non accessible depuis un compte administrateur système.');
        }

        return $next($request);
    }
}
