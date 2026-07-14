<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\ConfigurationFrais;
use App\Models\Cycle;
use App\Models\Eleve;
use App\Models\Paiement;
use App\Services\ComptabiliteService;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComptabiliteController extends Controller
{
    use FiltersByCycle;

    protected ComptabiliteService $comptabiliteService;

    public function __construct(ComptabiliteService $comptabiliteService)
    {
        $this->comptabiliteService = $comptabiliteService;
    }

    /**
     * Dashboard comptable
     */
    public function dashboard(Request $request)
    {
        $cycle = $this->resolveRequestedCycle($request);

        $stats = $this->comptabiliteService->getDashboardStats($cycle);
        $derniersPaiements = $this->comptabiliteService->getDerniersPaiements(10, $cycle);
        $cycles = $this->getAccessibleCycles();

        return view('comptabilite.dashboard', compact('stats', 'derniersPaiements', 'cycles', 'cycle'));
    }

    /**
     * Liste des eleves avec leurs soldes (optimise)
     */
    public function listeEleves(Request $request)
    {
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();
        $niveau = $request->get('niveau');
        $classeId = $request->get('classe_id');
        $cycles = $this->getAccessibleCycles();
        $cycle = $this->resolveRequestedCycle($request);

        // Charger les niveaux disponibles pour le filtre
        $niveauxQuery = \App\Models\Promotion::query();
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

        if (!$anneeCourante) {
            return view('comptabilite.eleves.index', [
                'eleves' => collect(),
                'cycles' => $cycles,
                'cycle' => $cycle,
                'niveaux' => $niveaux,
                'niveau' => $niveau,
                'classes' => $classes,
                'classeId' => $classeId,
                'canCreatePaiement' => Auth::user()->isComptable() || Auth::user()->isSecretaire(),
            ]);
        }

        // Charger les configurations de frais en une seule requete
        $configsQuery = ConfigurationFrais::where('annee_scolaire_id', $anneeCourante->id)
            ->where('actif', true);
        if ($cycle) {
            $configsQuery->where('cycle_id', $cycle->id);
        }
        $configurations = $configsQuery->get();
        $configIds = $configurations->pluck('id')->toArray();

        // Charger les totaux payes par eleve en une seule requete
        $paiementsParEleve = Paiement::whereIn('configuration_frais_id', $configIds)
            ->valide()
            ->select('eleve_id', DB::raw('SUM(montant) as total_paye'))
            ->groupBy('eleve_id')
            ->pluck('total_paye', 'eleve_id');

        // Grouper les configurations par cycle et niveau
        $configsParCycleNiveau = $configurations->groupBy(function ($config) {
            return $config->cycle_id . '_' . ($config->niveau ?? 'all');
        });

        // Requete principale avec eager loading
        $elevesQuery = Eleve::with(['classes' => function ($q) use ($anneeCourante) {
                $q->whereHas('promotion', function ($p) use ($anneeCourante) {
                    $p->where('annee_scolaire_id', $anneeCourante->id);
                })->with('promotion.cycle');
            }])
            ->whereHas('classes.promotion', function ($q) use ($anneeCourante, $cycle, $niveau) {
                $q->where('annee_scolaire_id', $anneeCourante->id);
                if ($cycle) {
                    $q->where('cycle_id', $cycle->id);
                }
                if ($niveau) {
                    $q->where('nom', $niveau);
                }
            })
            ->when($classeId, function ($query) use ($classeId) {
                $query->whereHas('classes', function ($q) use ($classeId) {
                    $q->where('classes.id', $classeId);
                });
            })
            ->orderBy('nom')
            ->get();

        // Preparer les donnees
        $eleves = $elevesQuery->map(function ($eleve) use ($configsParCycleNiveau, $paiementsParEleve, $configurations) {
            $classe = $eleve->classes->first();
            $totalFrais = 0;

            if ($classe && $classe->promotion) {
                $cycleId = $classe->promotion->cycle_id;
                $niveau = $classe->promotion->nom;

                // Calcul total frais pour ce cycle/niveau
                $configsApplicables = $configurations->filter(function ($config) use ($cycleId, $niveau) {
                    return $config->cycle_id == $cycleId
                        && (is_null($config->niveau) || $config->niveau === $niveau);
                });
                $totalFrais = $configsApplicables->sum('montant');
            }

            $totalPaye = (float) ($paiementsParEleve[$eleve->id] ?? 0);
            $solde = $totalFrais - $totalPaye;

            $statut = 'impaye';
            if ($totalFrais <= 0 || $solde <= 0) {
                $statut = 'solde';
            } elseif ($totalPaye > 0) {
                $statut = 'partiel';
            }

            return [
                'eleve' => $eleve,
                'classe' => $classe,
                'total_frais' => $totalFrais,
                'total_paye' => $totalPaye,
                'solde' => $solde,
                'statut' => $statut,
            ];
        });

        // Filtrer par statut si demande
        if ($request->has('statut') && in_array($request->statut, ['solde', 'partiel', 'impaye'])) {
            $eleves = $eleves->filter(function ($item) use ($request) {
                return $item['statut'] === $request->statut;
            });
        }

        // Tri
        if ($request->get('tri') === 'solde') {
            $eleves = $eleves->sortByDesc('solde');
        }

        return view('comptabilite.eleves.index', [
            'eleves' => $eleves,
            'cycles' => $cycles,
            'cycle' => $cycle,
            'niveaux' => $niveaux,
            'niveau' => $niveau,
            'classes' => $classes,
            'classeId' => $classeId,
            'canCreatePaiement' => Auth::user()->isComptable() || Auth::user()->isSecretaire(),
        ]);
    }

    /**
     * Fiche comptable d'un eleve
     */
    public function ficheEleve(Eleve $eleve)
    {
        if (!$this->canAccessEleve($eleve)) {
            abort(403, 'Vous n\'avez pas accès à cet élève.');
        }

        $eleve->load(['classes.promotion.cycle', 'paiements.recu', 'paiements.configurationFrais.typeFrais', 'paiements.tranche']);

        $fraisAvecStatut = $this->comptabiliteService->getFraisEleve($eleve);
        $classe = $eleve->getClasseActuelle();
        $canCreatePaiement = Auth::user()->isComptable() || Auth::user()->isSecretaire();

        return view('comptabilite.eleves.fiche', compact('eleve', 'fraisAvecStatut', 'classe', 'canCreatePaiement'));
    }

    /**
     * Rapports financiers
     */
    public function rapports(Request $request)
    {
        $anneeScolaire = AnneeScolaire::getAnneeScolaireActive();
        $cycle = $this->resolveRequestedCycle($request);

        $rapport = null;
        if ($anneeScolaire) {
            $rapport = $this->comptabiliteService->getRapportFinancier($anneeScolaire, $cycle);
        }

        $cycles = $this->getAccessibleCycles();
        $anneesScolaires = AnneeScolaire::orderBy('created_at', 'desc')->get();

        return view('comptabilite.rapports.index', compact('rapport', 'cycles', 'cycle', 'anneesScolaires', 'anneeScolaire'));
    }

    /**
     * Liste des eleves en retard de paiement
     */
    public function elevesEnRetard(Request $request)
    {
        $cycle = $this->resolveRequestedCycle($request);

        $elevesEnRetard = $this->comptabiliteService->getElevesEnRetard($cycle);
        $cycles = $this->getAccessibleCycles();
        $canCreatePaiement = Auth::user()->isComptable() || Auth::user()->isSecretaire();

        return view('comptabilite.rapports.retard', compact('elevesEnRetard', 'cycles', 'cycle', 'canCreatePaiement'));
    }
}
