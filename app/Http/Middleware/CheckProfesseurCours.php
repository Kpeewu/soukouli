<?php

namespace App\Http\Middleware;

use App\Models\Classe;
use App\Models\Cours;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProfesseurCours
{
    /**
     * Vérifie que le professeur a accès au cours spécifié.
     * Les admins et directeurs passent sans restriction.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $resourceType = 'cours'): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Non authentifié');
        }

        // Admin et directeurs ont accès complet
        if ($user->isAdmin() || $user->isDirecteur()) {
            return $next($request);
        }

        // Si ce n'est pas un professeur, accès refusé
        if (!$user->isProfesseur()) {
            abort(403, 'Accès non autorisé');
        }

        $professeur = $user->professeur;
        if (!$professeur) {
            abort(403, 'Profil professeur non trouvé');
        }

        // Vérification selon le type de ressource
        switch ($resourceType) {
            case 'cours':
                $cours = $request->route('cours');
                if ($cours && !$this->professeurEnseigneCours($professeur, $cours)) {
                    abort(403, 'Vous n\'enseignez pas ce cours');
                }
                break;

            case 'classe':
                $classe = $request->route('classe');
                if ($classe && !$this->professeurEnseigneDansClasse($professeur, $classe)) {
                    abort(403, 'Vous n\'enseignez pas dans cette classe');
                }
                break;

            case 'evaluation':
                $evaluation = $request->route('evaluation');
                if ($evaluation && !$this->professeurPeutModifierEvaluation($professeur, $evaluation)) {
                    abort(403, 'Vous ne pouvez pas modifier cette évaluation');
                }
                break;
        }

        return $next($request);
    }

    /**
     * Vérifie si le professeur enseigne le cours
     */
    private function professeurEnseigneCours($professeur, $cours): bool
    {
        if ($cours instanceof Cours) {
            return $cours->professeur_id === $professeur->id;
        }

        $coursModel = Cours::find($cours);
        return $coursModel && $coursModel->professeur_id === $professeur->id;
    }

    /**
     * Vérifie si le professeur enseigne au moins un cours dans la classe
     */
    private function professeurEnseigneDansClasse($professeur, $classe): bool
    {
        if ($classe instanceof Classe) {
            return $classe->cours()->where('professeur_id', $professeur->id)->exists();
        }

        $classeModel = Classe::find($classe);
        return $classeModel && $classeModel->cours()->where('professeur_id', $professeur->id)->exists();
    }

    /**
     * Vérifie si le professeur peut modifier l'évaluation (via le cours)
     */
    private function professeurPeutModifierEvaluation($professeur, $evaluation): bool
    {
        if (!$evaluation->cours) {
            return false;
        }

        return $evaluation->cours->professeur_id === $professeur->id;
    }
}
