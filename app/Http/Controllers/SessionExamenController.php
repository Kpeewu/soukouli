<?php

namespace App\Http\Controllers;

use App\Models\SessionExamen;
use App\Models\ExamenOfficiel;
use App\Models\AnneeScolaire;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionExamenController extends Controller
{
    use FiltersByCycle;

    public function index()
    {
        $user = Auth::user();
        $anneeScolaire = AnneeScolaire::getAnneeScolaireActive();

        $query = SessionExamen::with(['examenOfficiel.cycle', 'anneeScolaire', 'inscriptions']);

        // Filtrer par cycle si l'utilisateur n'est pas admin
        if (!$user->isAdmin()) {
            $cycleIds = $this->getAccessibleCycleIds();
            $query->whereHas('examenOfficiel', function ($q) use ($cycleIds) {
                $q->whereIn('cycle_id', $cycleIds);
            });
        }

        // Filtrer par année scolaire courante par défaut
        if ($anneeScolaire) {
            $query->where('annee_scolaire_id', $anneeScolaire->id);
        }

        $sessions = $query->get();

        return view('examens.sessions.index', compact('sessions', 'anneeScolaire'));
    }

    public function create()
    {
        $user = Auth::user();
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();
        $anneesScolaires = AnneeScolaire::orderBy('annee', 'desc')->get();

        $query = ExamenOfficiel::with('cycle');

        // Filtrer par cycle si l'utilisateur n'est pas admin
        if (!$user->isAdmin()) {
            $cycleIds = $this->getAccessibleCycleIds();
            $query->whereIn('cycle_id', $cycleIds);
        }

        $examens = $query->get();

        return view('examens.sessions.create', compact('examens', 'anneeCourante', 'anneesScolaires'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'examen_officiel_id' => 'required|exists:examens_officiels,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'statut' => 'nullable|in:programme,en_cours,termine',
        ]);

        $examenOfficiel = ExamenOfficiel::findOrFail($request->examen_officiel_id);
        $this->authorizeAccessCycle($examenOfficiel->cycle_id);

        $anneeScolaireId = $request->annee_scolaire_id;

        // Verifier qu'il n'existe pas deja une session pour cet examen et cette annee
        $existingSession = SessionExamen::where('examen_officiel_id', $request->examen_officiel_id)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->first();

        if ($existingSession) {
            return redirect()->back()
                ->with('notification', ['type' => 'danger', 'message' => 'Une session existe deja pour cet examen cette annee']);
        }

        SessionExamen::create([
            'examen_officiel_id' => $request->examen_officiel_id,
            'annee_scolaire_id' => $anneeScolaireId,
            'date_debut' => $request->date_debut ?: null,
            'date_fin' => $request->date_fin ?: null,
            'statut' => $request->statut ?? 'programme'
        ]);

        return redirect()->route('sessions.index')
            ->with('notification', ['type' => 'success', 'message' => 'Session d\'examen creee avec succes']);
    }

    public function show(SessionExamen $session)
    {
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);

        $session->load(['examenOfficiel.cycle', 'anneeScolaire', 'inscriptions.eleve']);

        return view('examens.sessions.show', compact('session'));
    }

    public function edit(SessionExamen $session)
    {
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);

        $user = Auth::user();
        $anneesScolaires = AnneeScolaire::orderBy('annee', 'desc')->get();

        $query = ExamenOfficiel::with('cycle');

        if (!$user->isAdmin()) {
            $cycleIds = $this->getAccessibleCycleIds();
            $query->whereIn('cycle_id', $cycleIds);
        }

        $examens = $query->get();

        return view('examens.sessions.edit', compact('session', 'examens', 'anneesScolaires'));
    }

    public function update(Request $request, SessionExamen $session)
    {
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);

        $request->validate([
            'examen_officiel_id' => 'required|exists:examens_officiels,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'statut' => 'required|in:programme,en_cours,termine'
        ]);

        $examenOfficiel = ExamenOfficiel::findOrFail($request->examen_officiel_id);
        $this->authorizeAccessCycle($examenOfficiel->cycle_id);

        $session->update([
            'examen_officiel_id' => $request->examen_officiel_id,
            'annee_scolaire_id' => $request->annee_scolaire_id,
            'date_debut' => $request->date_debut ?: null,
            'date_fin' => $request->date_fin ?: null,
            'statut' => $request->statut
        ]);

        return redirect()->route('sessions.index')
            ->with('notification', ['type' => 'success', 'message' => 'Session mise a jour avec succes']);
    }

    public function destroy(SessionExamen $session)
    {
        $this->authorizeAccessCycle($session->examenOfficiel->cycle_id);

        $session->delete();

        return redirect()->route('sessions.index')
            ->with('notification', ['type' => 'success', 'message' => 'Session supprimée avec succès']);
    }
}
