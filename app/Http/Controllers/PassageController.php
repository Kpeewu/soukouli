<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Cycle;
use App\Services\AnneeScolaireGenerationService;
use App\Services\PassageElevesService;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use RuntimeException;

class PassageController extends Controller
{
    use FiltersByCycle;

    public function __construct(
        private PassageElevesService $service,
        private AnneeScolaireGenerationService $anneeService
    ) {
        $this->middleware(function ($request, $next) {
            if (!$request->user()->isDirecteur()) {
                abort(403, 'Seuls les directeurs peuvent gérer le passage en année supérieure.');
            }
            return $next($request);
        });
    }

    /**
     * Page de gestion du passage en annee superieure.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isGeneral = $user->hasRole('directeur_general');

        $cycles = $isGeneral
            ? Cycle::orderBy('ordre')->get()
            : collect([$user->getManagedCycle()])->filter()->values();

        $currentAnneeScolaire = AnneeScolaire::getAnneeScolaire();
        $nextLabel = $this->anneeService->calculerLabelAnneeSuivante();
        $nextYearExists = AnneeScolaire::where('annee', $nextLabel)->exists();

        return view('admin.passage.index', compact(
            'isGeneral',
            'cycles',
            'currentAnneeScolaire',
            'nextLabel',
            'nextYearExists'
        ));
    }

    /**
     * Retourne la liste des classes a traiter pour le perimetre demande
     * (plan consomme sequentiellement par le JS de la page).
     */
    public function plan(Request $request)
    {
        $cycle = $this->resolveRequestedCycle($request);

        try {
            $this->service->resoudreAnneeSuivante();
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

        $classes = $this->service->getClassesAPasser($cycle)->map(function (Classe $classe) {
            return [
                'id' => $classe->hashid,
                'nom' => $classe->nom,
                'promotion' => $classe->promotion->nom,
                'cycle' => $classe->promotion->cycle->nom,
            ];
        })->values();

        return response()->json(['success' => true, 'classes' => $classes]);
    }

    /**
     * Traite le passage des eleves d'une seule classe.
     */
    public function executerClasse(Request $request, Classe $classe)
    {
        $this->authorizeAccessClasse($classe);

        try {
            $nextYear = $this->service->resoudreAnneeSuivante();
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

        $stats = $this->service->traiterClasse($classe, $nextYear);

        return response()->json(array_merge(['success' => true], $stats, [
            'classe_nom' => $classe->nom,
            'promotion_nom' => $classe->promotion->nom,
            'cycle_nom' => $classe->promotion->cycle->nom,
        ]));
    }
}
