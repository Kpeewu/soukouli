<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\Cycle;
use App\Models\Eleve;
use App\Models\Paiement;
use App\Models\Professeur;
use App\Models\Promotion;
use App\Models\User;
use App\Services\ComptabiliteService;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    use FiltersByCycle;

    protected ComptabiliteService $comptabiliteService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ComptabiliteService $comptabiliteService)
    {
        $this->middleware('auth');
        $this->comptabiliteService = $comptabiliteService;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        $data = [
            'anneeCourante' => $anneeCourante,
            'user' => $user,
        ];

        // Admin : toutes les statistiques
        if ($user->isAdmin()) {
            $data = array_merge($data, $this->getAdminStats($anneeCourante));
            $data['viewType'] = 'admin';
        }
        // Directeurs : statistiques de leur cycle
        elseif ($user->isDirecteur()) {
            $isDirecteurGeneral = $user->hasRole('directeur_general');
            $cycle = $isDirecteurGeneral ? $this->resolveRequestedCycle($request) : $this->getCycleForDirecteur($user);
            $data = array_merge($data, $this->getDirecteurStats($anneeCourante, $cycle, $isDirecteurGeneral));
            $data['cycle'] = $cycle;
            $data['isDirecteurGeneral'] = $isDirecteurGeneral;
            $data['cycles'] = $isDirecteurGeneral ? Cycle::orderBy('ordre')->get() : collect();
            $data['viewType'] = 'directeur';
        }
        // Professeur : ses cours et classes
        elseif ($user->isProfesseur()) {
            $data = array_merge($data, $this->getProfesseurStats($user, $anneeCourante));
            $data['viewType'] = 'professeur';
        }
        // Comptable : resume financier
        elseif ($user->isComptable()) {
            $cycle = $user->hasRole('comptable_general') ? null : $user->getComptableCycle();
            $data = array_merge($data, $this->getComptableStats($anneeCourante, $cycle));
            $data['viewType'] = 'comptable';
        }
        // Secretaire : eleves et classes de son cycle
        elseif ($user->isSecretaire()) {
            $isSecretaireGeneral = $user->hasRole('secretaire_general');
            $cycle = $isSecretaireGeneral ? null : $user->getSecretaireCycle();
            $data = array_merge($data, $this->getSecretaireStats($anneeCourante, $cycle));
            $data['cycle'] = $cycle;
            $data['isSecretaireGeneral'] = $isSecretaireGeneral;
            $data['viewType'] = 'secretaire';
        }
        // Utilisateur standard
        else {
            $data['viewType'] = 'default';
        }

        return view('index', $data);
    }

    /**
     * Statistiques pour l'administrateur
     */
    private function getAdminStats(AnneeScolaire $anneeCourante): array
    {
        $cycles = Cycle::orderBy('ordre')->get();
        $promotions = Promotion::where('annee_scolaire_id', $anneeCourante->id)->get();

        // Compter les eleves par cycle
        $elevesParCycle = [];
        foreach ($cycles as $cycle) {
            $count = Eleve::whereHas('classes.promotion', function ($q) use ($anneeCourante, $cycle) {
                $q->where('annee_scolaire_id', $anneeCourante->id)
                  ->where('cycle_id', $cycle->id);
            })->count();
            $elevesParCycle[$cycle->nom] = $count;
        }

        // Statistiques globales
        $totalEleves = Eleve::whereHas('classes.promotion', function ($q) use ($anneeCourante) {
            $q->where('annee_scolaire_id', $anneeCourante->id);
        })->count();

        $totalProfesseurs = Professeur::count();
        $totalClasses = Classe::whereHas('promotion', function ($q) use ($anneeCourante) {
            $q->where('annee_scolaire_id', $anneeCourante->id);
        })->count();

        $totalUsers = User::count();

        // Dernieres inscriptions
        $dernieresInscriptions = Eleve::whereHas('classes.promotion', function ($q) use ($anneeCourante) {
            $q->where('annee_scolaire_id', $anneeCourante->id);
        })
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();

        return [
            'cycles' => $cycles,
            'elevesParCycle' => $elevesParCycle,
            'totalEleves' => $totalEleves,
            'totalProfesseurs' => $totalProfesseurs,
            'totalClasses' => $totalClasses,
            'totalUsers' => $totalUsers,
            'dernieresInscriptions' => $dernieresInscriptions,
        ];
    }

    /**
     * Statistiques pour un directeur de cycle
     *
     * Si $tousLesCycles est vrai (directeur_general), les statistiques portent
     * sur l'ensemble des cycles plutot que sur un cycle unique.
     */
    private function getDirecteurStats(AnneeScolaire $anneeCourante, ?Cycle $cycle, bool $tousLesCycles = false): array
    {
        if (!$cycle && !$tousLesCycles) {
            return [
                'totalEleves' => 0,
                'totalProfesseurs' => 0,
                'totalClasses' => 0,
                'dernieresInscriptions' => collect(),
                'financeStats' => null,
            ];
        }

        // Eleves du/des cycle(s)
        $totalEleves = Eleve::whereHas('classes.promotion', function ($q) use ($anneeCourante, $cycle) {
            $q->where('annee_scolaire_id', $anneeCourante->id);
            if ($cycle) {
                $q->where('cycle_id', $cycle->id);
            }
        })->count();

        // Professeurs du/des cycle(s) : rattaches a ce cycle, ou y enseignant/tutorant
        $totalProfesseurs = $cycle
            ? Professeur::forCycle($cycle->id)->count()
            : Professeur::count();

        // Classes du/des cycle(s)
        $totalClasses = Classe::whereHas('promotion', function ($q) use ($anneeCourante, $cycle) {
            $q->where('annee_scolaire_id', $anneeCourante->id);
            if ($cycle) {
                $q->where('cycle_id', $cycle->id);
            }
        })->count();

        // Dernieres inscriptions du/des cycle(s)
        $dernieresInscriptions = Eleve::whereHas('classes.promotion', function ($q) use ($anneeCourante, $cycle) {
            $q->where('annee_scolaire_id', $anneeCourante->id);
            if ($cycle) {
                $q->where('cycle_id', $cycle->id);
            }
        })
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();

        // Donnees financieres du/des cycle(s)
        $financeStats = $this->comptabiliteService->getDashboardStats($cycle);

        return [
            'totalEleves' => $totalEleves,
            'totalProfesseurs' => $totalProfesseurs,
            'totalClasses' => $totalClasses,
            'dernieresInscriptions' => $dernieresInscriptions,
            'financeStats' => $financeStats,
        ];
    }

    /**
     * Statistiques pour un professeur
     */
    private function getProfesseurStats($user, AnneeScolaire $anneeCourante): array
    {
        // Trouver le professeur lie a l'utilisateur
        $professeur = Professeur::where('user_id', $user->id)->first();

        if (!$professeur) {
            return [
                'professeur' => null,
                'cours' => collect(),
                'totalEleves' => 0,
                'classes' => collect(),
            ];
        }

        // Cours du professeur pour l'annee courante
        $cours = Cours::with(['classe.promotion', 'matiere'])
            ->where('professeur_id', $professeur->id)
            ->whereHas('classe.promotion', function ($q) use ($anneeCourante) {
                $q->where('annee_scolaire_id', $anneeCourante->id);
            })
            ->get();

        // Classes uniques
        $classes = $cours->pluck('classe')->unique('id');

        // Total eleves enseignes
        $totalEleves = 0;
        foreach ($classes as $classe) {
            $totalEleves += $classe->eleves()->count();
        }

        // Cours par matiere
        $coursParMatiere = $cours->groupBy(function ($c) {
            return $c->matiere->intitule;
        });

        return [
            'professeur' => $professeur,
            'cours' => $cours,
            'totalEleves' => $totalEleves,
            'classes' => $classes,
            'coursParMatiere' => $coursParMatiere,
        ];
    }

    /**
     * Statistiques pour un comptable
     *
     * Sans cycle (comptable_general/admin), les statistiques portent sur tous les cycles.
     * Avec un cycle (comptable_{code}), elles sont restreintes a ce cycle uniquement.
     */
    private function getComptableStats(AnneeScolaire $anneeCourante, ?Cycle $cycle = null): array
    {
        $baseQuery = function () use ($cycle) {
            $query = Paiement::query();
            if ($cycle) {
                $query->whereHas('configurationFrais', fn ($q) => $q->where('cycle_id', $cycle->id));
            }
            return $query;
        };

        // Paiements du jour
        $paiementsJour = $baseQuery()->valide()->whereDate('created_at', today())->sum('montant');

        // Paiements de la semaine
        $paiementsSemaine = $baseQuery()->valide()->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->sum('montant');

        // Paiements du mois
        $paiementsMois = $baseQuery()->valide()->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('montant');

        // Nombre de paiements aujourd'hui
        $nbPaiementsJour = $baseQuery()->valide()->whereDate('created_at', today())->count();

        // Derniers paiements
        $derniersPaiements = $baseQuery()->with(['eleve', 'configurationFrais.typeFrais', 'recu'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return [
            'paiementsJour' => $paiementsJour,
            'paiementsSemaine' => $paiementsSemaine,
            'paiementsMois' => $paiementsMois,
            'nbPaiementsJour' => $nbPaiementsJour,
            'derniersPaiements' => $derniersPaiements,
        ];
    }

    /**
     * Determine le cycle pour un directeur selon son role
     */
    private function getCycleForDirecteur($user): ?Cycle
    {
        return $user->getManagedCycle();
    }

    /**
     * Statistiques pour un secretaire
     *
     * Sans cycle (secretaire_general), les statistiques portent sur l'ensemble
     * des cycles. Avec un cycle (secretaire_{code}), elles sont restreintes a ce cycle.
     */
    private function getSecretaireStats(AnneeScolaire $anneeCourante, ?Cycle $cycle): array
    {
        $totalEleves = Eleve::whereHas('classes.promotion', function ($q) use ($anneeCourante, $cycle) {
            $q->where('annee_scolaire_id', $anneeCourante->id);
            if ($cycle) {
                $q->where('cycle_id', $cycle->id);
            }
        })->count();

        $totalClasses = Classe::whereHas('promotion', function ($q) use ($anneeCourante, $cycle) {
            $q->where('annee_scolaire_id', $anneeCourante->id);
            if ($cycle) {
                $q->where('cycle_id', $cycle->id);
            }
        })->count();

        $dernieresInscriptions = Eleve::whereHas('classes.promotion', function ($q) use ($anneeCourante, $cycle) {
            $q->where('annee_scolaire_id', $anneeCourante->id);
            if ($cycle) {
                $q->where('cycle_id', $cycle->id);
            }
        })
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();

        return [
            'totalEleves' => $totalEleves,
            'totalClasses' => $totalClasses,
            'dernieresInscriptions' => $dernieresInscriptions,
        ];
    }
}
