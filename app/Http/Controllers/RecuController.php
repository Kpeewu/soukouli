<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Cycle;
use App\Models\Promotion;
use App\Models\Recu;
use App\Services\RecuPdfService;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecuController extends Controller
{
    use FiltersByCycle;

    protected RecuPdfService $recuPdfService;

    public function __construct(RecuPdfService $recuPdfService)
    {
        $this->recuPdfService = $recuPdfService;
    }

    /**
     * Liste des recus
     */
    public function index(Request $request)
    {
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();
        $niveau = $request->get('niveau');
        $classeId = $request->get('classe_id');
        $search = $request->get('search');
        $cycles = $this->getAccessibleCycles();
        $cycle = $this->resolveRequestedCycle($request);

        // Charger les niveaux disponibles pour le filtre
        $niveauxQuery = Promotion::query();
        if ($anneeCourante) {
            $niveauxQuery->where('annee_scolaire_id', $anneeCourante->id);
        }
        if ($cycle) {
            $niveauxQuery->where('cycle_id', $cycle->id);
        }
        $niveaux = $niveauxQuery->orderBy('nom')->pluck('nom')->unique()->values();

        // Charger les classes disponibles pour le filtre
        $classesQuery = \App\Models\Classe::whereHas('promotion', function ($q) use ($anneeCourante, $cycle, $niveau) {
            if ($anneeCourante) {
                $q->where('annee_scolaire_id', $anneeCourante->id);
            }
            if ($cycle) {
                $q->where('cycle_id', $cycle->id);
            }
            if ($niveau) {
                $q->where('nom', $niveau);
            }
        });
        $classes = $classesQuery->orderBy('nom')->get();

        $query = Recu::with([
                'paiement.eleve.classes.promotion',
                'paiement.configurationFrais.typeFrais',
                'comptable'
            ])
            ->orderBy('created_at', 'desc');

        // Filtre par annee
        if ($anneeCourante) {
            $query->whereHas('paiement.configurationFrais', function ($q) use ($anneeCourante) {
                $q->where('annee_scolaire_id', $anneeCourante->id);
            });
        }

        // Filtre par cycle
        if ($cycle) {
            $query->whereHas('paiement.configurationFrais', function ($q) use ($cycle) {
                $q->where('cycle_id', $cycle->id);
            });
        }

        // Filtre par niveau
        if ($niveau) {
            $query->whereHas('paiement.eleve.classes.promotion', function ($q) use ($niveau, $anneeCourante) {
                $q->where('nom', $niveau);
                if ($anneeCourante) {
                    $q->where('annee_scolaire_id', $anneeCourante->id);
                }
            });
        }

        // Filtre par classe
        if ($classeId) {
            $query->whereHas('paiement.eleve.classes', function ($q) use ($classeId) {
                $q->where('classes.id', $classeId);
            });
        }

        // Filtre par nom / prenom de l'eleve
        if ($search) {
            $query->whereHas('paiement.eleve', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%");
                });
            });
        }

        // Filtre par statut
        if ($request->has('statut') && $request->statut) {
            if ($request->statut === 'valide') {
                $query->where('annule', false);
            } elseif ($request->statut === 'annule') {
                $query->where('annule', true);
            }
        }

        $recus = $query->get();
        $canAnnuler = Auth::user()->isComptable();

        return view('comptabilite.recus.index', compact('recus', 'cycles', 'cycle', 'niveaux', 'niveau', 'classes', 'classeId', 'search', 'canAnnuler'));
    }

    /**
     * Afficher un recu
     */
    public function show(Recu $recu)
    {
        $this->authorizeAccessRecu($recu);

        $recu->load([
            'paiement.eleve.classes.promotion',
            'paiement.configurationFrais.typeFrais',
            'paiement.tranche',
            'comptable'
        ]);

        return view('comptabilite.recus.show', compact('recu'));
    }

    /**
     * Telecharger le PDF d'un recu
     */
    public function pdf(Recu $recu)
    {
        $this->authorizeAccessRecu($recu);

        return $this->recuPdfService->download($recu);
    }

    /**
     * Annuler un recu
     */
    public function annuler(Request $request, Recu $recu)
    {
        $this->authorizeAccessRecu($recu);

        $request->validate([
            'motif_annulation' => 'required|string|max:500',
        ]);

        if ($recu->annule) {
            return back()->with('notification', ['type' => 'error', 'message' => 'Ce recu est deja annule']);
        }

        $recu->annuler($request->motif_annulation);

        return back()->with('notification', ['type' => 'success', 'message' => 'Recu annule avec succes']);
    }

    /**
     * Avorte avec 403 si le cycle du recu n'est pas accessible par l'utilisateur
     */
    private function authorizeAccessRecu(Recu $recu): void
    {
        $recu->loadMissing('paiement.configurationFrais');

        $this->authorizeAccessCycle($recu->paiement->configurationFrais->cycle_id);
    }
}
