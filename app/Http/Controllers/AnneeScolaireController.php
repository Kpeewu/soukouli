<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Services\AnneeScolaireGenerationService;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class AnneeScolaireController extends Controller
{
    public function __construct(private AnneeScolaireGenerationService $generationService)
    {
    }

    /**
     * Methode ajax permettant a l'utilisateur connecte de choisir l'annee
     * scolaire qu'il consulte, sans affecter les autres utilisateurs.
     */
    public function changeAppCurrentYear(Request $request, AnneeScolaire $anneeScolaire)
    {
        $request->user()->update(['annee_scolaire_id' => $anneeScolaire->id]);

        return response()->json([
            'success' => true,
            'message' => 'Année scolaire changée pour ' . $anneeScolaire->annee,
            'url' => '/dashboard',
        ]);
    }

    /**
     * Page de gestion des années scolaires (admin + directeur général).
     */
    public function index()
    {
        $anneesScolaires = AnneeScolaire::orderByDesc('annee')->get();
        $currentAnneeScolaire = AnneeScolaire::getAnneeScolaire();
        $nextLabel = $this->generationService->calculerLabelAnneeSuivante();
        $nextAnneeScolaire = AnneeScolaire::where('annee', $nextLabel)->first();
        $nextYearExists = $nextAnneeScolaire !== null;

        return view('admin.annees-scolaires.index', compact(
            'anneesScolaires',
            'currentAnneeScolaire',
            'nextLabel',
            'nextAnneeScolaire',
            'nextYearExists'
        ));
    }

    /**
     * Déclenche la génération de l'année scolaire suivante.
     */
    public function genererAnneeSuivante()
    {
        try {
            $stats = $this->generationService->genererAnneeSuivante();
        } catch (RuntimeException $e) {
            return redirect()->route('annees-scolaires.index')
                ->with('notification', ['type' => 'warning', 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('annees-scolaires.index')
                ->with('notification', [
                    'type' => 'danger',
                    'message' => "Une erreur est survenue pendant la génération de l'année scolaire. Aucune donnée n'a été modifiée (opération annulée automatiquement). Merci de contacter un administrateur technique.",
                ]);
        }

        $message = "Année scolaire {$stats['nouvelle_annee']->annee} créée avec succès (pas encore active) : "
            . "{$stats['nb_promotions']} promotion(s), {$stats['nb_classes']} classe(s), "
            . "{$stats['nb_configurations_frais']} configuration(s) de frais, "
            . "{$stats['nb_cours']} cours copiés. Pensez à effectuer le passage des élèves "
            . "puis à activer cette année depuis cette page.";

        return redirect()->route('annees-scolaires.index')
            ->with('notification', ['type' => 'success', 'message' => $message]);
    }

    /**
     * Active manuellement l'année scolaire suivante (bascule "courant").
     */
    public function activer(AnneeScolaire $anneeScolaire)
    {
        try {
            $activee = $this->generationService->activerAnneeScolaire($anneeScolaire);
        } catch (RuntimeException $e) {
            return redirect()->route('annees-scolaires.index')
                ->with('notification', ['type' => 'warning', 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('annees-scolaires.index')
                ->with('notification', [
                    'type' => 'danger',
                    'message' => "Une erreur est survenue pendant l'activation de l'année scolaire. Merci de contacter un administrateur technique.",
                ]);
        }

        return redirect()->route('annees-scolaires.index')
            ->with('notification', [
                'type' => 'success',
                'message' => "Année scolaire {$activee->annee} activée avec succès : elle est désormais l'année courante de l'application.",
            ]);
    }
}
