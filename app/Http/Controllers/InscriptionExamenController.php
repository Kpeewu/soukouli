<?php

namespace App\Http\Controllers;

use App\Models\InscriptionExamen;
use App\Models\SessionExamen;
use App\Models\Eleve;
use App\Models\Promotion;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InscriptionExamenController extends Controller
{
    use FiltersByCycle;

    /**
     * Les directeurs ont un acces en lecture seule aux examens : ils ne peuvent
     * ni inscrire d'eleve, ni retirer une inscription, ni saisir de resultats.
     */
    private function denyDirecteur(): void
    {
        if (Auth::user() && Auth::user()->isDirecteur()) {
            abort(403, "Vous n'avez pas le droit de modifier les inscriptions ou les resultats de cet examen.");
        }
    }

    public function index(SessionExamen $session)
    {
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);

        $session->load(['examenOfficiel.cycle', 'inscriptions.eleve']);
        $inscriptions = $session->inscriptions;

        return view('examens.inscriptions.index', compact('session', 'inscriptions'));
    }

    public function create(SessionExamen $session)
    {
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);
        $this->denyDirecteur();

        $session->load(['examenOfficiel.cycle', 'inscriptions', 'anneeScolaire']);

        // Recuperer les eleves eligibles (dans le niveau requis)
        $niveauRequis = $session->examenOfficiel->niveau_requis;
        $cycleId = $session->examenOfficiel->cycle_id;

        // Trouver les promotions correspondant au niveau requis pour l'annee de la session
        // Le niveau requis peut etre "CM2", "3ème", "1ere", "Terminale" etc.
        $promotions = Promotion::where('cycle_id', $cycleId)
            ->where('annee_scolaire_id', $session->annee_scolaire_id)
            ->where('nom', $niveauRequis)
            ->with('classes.eleves')
            ->get();

        $eleveIds = [];
        foreach ($promotions as $promotion) {
            foreach ($promotion->classes as $classe) {
                foreach ($classe->eleves as $eleve) {
                    $eleveIds[] = $eleve->id;
                }
            }
        }

        // Exclure les eleves deja inscrits
        $elevesDejaInscrits = $session->inscriptions()->pluck('eleve_id')->toArray();
        $eleveIds = array_diff($eleveIds, $elevesDejaInscrits);

        $elevesEligibles = Eleve::whereIn('id', $eleveIds)->with('classes')->get();

        return view('examens.inscriptions.create', compact('session', 'elevesEligibles'));
    }

    public function store(Request $request, SessionExamen $session)
    {
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);
        $this->denyDirecteur();

        $request->validate([
            'eleves' => 'required|array',
            'eleves.*' => 'exists:eleves,id',
            'centre_examen' => 'nullable|string|max:255',
            'numero_serie' => 'nullable|string|max:50'
        ]);

        $session->load(['examenOfficiel', 'anneeScolaire']);
        $centreExamen = $request->centre_examen;
        $numeroSerie = $request->numero_serie ?? '001';

        $count = 0;
        foreach ($request->eleves as $eleveId) {
            // Verifier que l'eleve n'est pas deja inscrit
            $existingInscription = InscriptionExamen::where('session_examen_id', $session->id)
                ->where('eleve_id', $eleveId)
                ->first();

            if (!$existingInscription) {
                // Generer un numero d'inscription unique
                $numeroInscription = $this->generateNumeroInscription($session, $numeroSerie, $count);

                InscriptionExamen::create([
                    'session_examen_id' => $session->id,
                    'eleve_id' => $eleveId,
                    'numero_inscription' => $numeroInscription,
                    'centre_examen' => $centreExamen,
                    'statut' => 'inscrit'
                ]);

                $count++;
            }
        }

        return redirect()->route('sessions.show', $session)
            ->with('notification', ['type' => 'success', 'message' => $count . ' eleve(s) inscrit(s) avec succes']);
    }

    public function destroy(InscriptionExamen $inscription)
    {
        $session = $inscription->sessionExamen;
        $session->load('examenOfficiel');
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);
        $this->denyDirecteur();

        $inscription->delete();

        return redirect()->route('inscriptions.index', $session)
            ->with('notification', ['type' => 'success', 'message' => 'Inscription annulee']);
    }

    /**
     * Affiche la page de saisie des resultats
     */
    public function saisie(SessionExamen $session)
    {
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);
        $this->denyDirecteur();

        $session->load(['examenOfficiel', 'anneeScolaire', 'inscriptions.eleve']);
        $inscriptions = $session->inscriptions;

        return view('examens.resultats.saisie', compact('session', 'inscriptions'));
    }

    /**
     * Affiche la liste des resultats
     */
    public function liste(SessionExamen $session)
    {
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);

        $session->load(['examenOfficiel', 'anneeScolaire', 'inscriptions.eleve']);
        $inscriptions = $session->inscriptions->sortByDesc('moyenne_obtenue');

        $total = $inscriptions->count();
        $admis = $inscriptions->where('statut', 'admis')->count();
        $ajournes = $inscriptions->where('statut', 'ajourne')->count();
        $tauxReussite = $total > 0 ? round(($admis / $total) * 100, 1) : 0;

        return view('examens.resultats.liste', compact('session', 'inscriptions', 'total', 'admis', 'ajournes', 'tauxReussite'));
    }

    /**
     * Enregistre les resultats des examens
     */
    public function enregistrerResultats(Request $request, SessionExamen $session)
    {
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);
        $this->denyDirecteur();

        $request->validate([
            'resultats' => 'required|array',
        ]);

        foreach ($request->resultats as $inscriptionId => $resultat) {
            $inscription = InscriptionExamen::find($inscriptionId);

            if ($inscription && $inscription->session_examen_id === $session->id) {
                $moyenne = $resultat['moyenne'] ?? null;
                $statut = $resultat['statut'] ?? 'inscrit';
                $mention = $resultat['mention'] ?? null;

                $inscription->update([
                    'statut' => $statut,
                    'moyenne_obtenue' => $moyenne,
                    'mention' => $mention
                ]);
            }
        }

        return redirect()->route('resultats.liste', $session)
            ->with('notification', ['type' => 'success', 'message' => 'Resultats enregistres avec succes']);
    }

    /**
     * Genere un numero d'inscription unique
     */
    private function generateNumeroInscription(SessionExamen $session, string $baseSerie, int $index): string
    {
        $examenCode = $session->examenOfficiel->code;
        $annee = substr($session->anneeScolaire->annee ?? date('Y'), 0, 4);

        // Si la serie de base est numerique, on l'incremente
        if (is_numeric($baseSerie)) {
            $numero = str_pad((int)$baseSerie + $index, 4, '0', STR_PAD_LEFT);
        } else {
            // Sinon on ajoute un index
            $numero = $baseSerie . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        }

        return $examenCode . $annee . $numero;
    }
}
