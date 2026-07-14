<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Cycle;
use App\Models\Promotion;
use App\Models\Trimestre;
use App\Services\CycleRoleProvisioner;
use Illuminate\Http\Request;

class CycleController extends Controller
{
    public function index()
    {
        $cycles = Cycle::with('cycleSuivant')->orderBy('ordre')->get();
        return view('admin.cycles.index', compact('cycles'));
    }

    public function create()
    {
        // Charger tous les cycles existants pour le select du cycle suivant
        $cyclesDisponibles = Cycle::orderBy('ordre')->get();
        // Par defaut, proposer le prochain ordre disponible
        $prochainOrdre = (Cycle::max('ordre') ?? 0) + 1;
        return view('admin.cycles.create', compact('cyclesDisponibles', 'prochainOrdre'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cycles',
            'ordre' => 'required|integer',
            'niveaux' => 'nullable|array',
            'niveaux.*' => 'nullable|string|max:50',
            'cycle_suivant_id' => 'nullable|exists:cycles,id',
        ]);

        // Filtrer les niveaux vides
        $niveaux = array_filter($request->niveaux ?? [], fn($n) => !empty(trim($n)));
        $niveaux = array_values($niveaux); // Reindexter le tableau

        $cycle = Cycle::create([
            'nom' => $request->nom,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'ordre' => $request->ordre,
            'supports_semestre' => $request->supports_semestre ?? false,
            'niveaux' => !empty($niveaux) ? $niveaux : null,
            'cycle_suivant_id' => $request->cycle_suivant_id ?: null,
        ]);

        // Creer les promotions (niveaux) pour l'annee scolaire courante
        if (!empty($niveaux)) {
            $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

            if ($anneeCourante) {
                foreach ($niveaux as $index => $niveau) {
                    $promotion = Promotion::create([
                        'nom' => trim($niveau),
                        'ordre' => $index + 1,
                        'annee_scolaire_id' => $anneeCourante->id,
                        'cycle_id' => $cycle->id,
                        'type_periode' => 'trimestre'
                    ]);

                    // Creer les 3 trimestres pour cette promotion
                    $this->createTrimestres($promotion);

                    // Creer une classe par defaut
                    Classe::create([
                        'nom' => trim($niveau) . ' A',
                        'promotion_id' => $promotion->id
                    ]);
                }
            }
        }

        CycleRoleProvisioner::provision($cycle);

        $message = 'Cycle cree avec succes';
        if (!empty($niveaux)) {
            $message .= ' avec ' . count($niveaux) . ' niveau(x)';
        }

        return redirect()->route('cycles.index')
            ->with('notification', ['type' => 'success', 'message' => $message]);
    }

    public function edit(Cycle $cycle)
    {
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        // Charger uniquement les promotions de l'annee courante
        $cycle->load(['promotions' => function($query) use ($anneeCourante) {
            if ($anneeCourante) {
                $query->where('annee_scolaire_id', $anneeCourante->id)->orderBy('ordre');
            }
        }]);

        // Charger tous les cycles sauf celui en cours d'edition
        $cyclesDisponibles = Cycle::where('id', '!=', $cycle->id)->orderBy('ordre')->get();
        return view('admin.cycles.edit', compact('cycle', 'cyclesDisponibles'));
    }

    public function update(Request $request, Cycle $cycle)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cycles,code,' . $cycle->id,
            'ordre' => 'required|integer',
            'cycle_suivant_id' => 'nullable|exists:cycles,id',
        ]);

        // Empecher un cycle de pointer vers lui-meme
        $cycleSuivantId = $request->cycle_suivant_id;
        if ($cycleSuivantId == $cycle->id) {
            $cycleSuivantId = null;
        }

        // Mettre a jour les niveaux JSON si fournis
        $niveaux = null;
        if ($request->has('niveaux_json')) {
            $niveaux = array_filter($request->niveaux_json ?? [], fn($n) => !empty(trim($n)));
            $niveaux = !empty($niveaux) ? array_values($niveaux) : null;
        } else {
            $niveaux = $cycle->niveaux; // Garder les niveaux existants
        }

        $cycle->update([
            'nom' => $request->nom,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'ordre' => $request->ordre,
            'supports_semestre' => $request->supports_semestre ?? false,
            'niveaux' => $niveaux,
            'cycle_suivant_id' => $cycleSuivantId ?: null,
        ]);

        return redirect()->route('cycles.index')
            ->with('notification', ['type' => 'success', 'message' => 'Cycle modifie avec succes']);
    }

    public function destroy(Cycle $cycle)
    {
        // Verifier si ce cycle est reference comme cycle suivant par un autre cycle
        $cycleReferent = Cycle::where('cycle_suivant_id', $cycle->id)->first();
        if ($cycleReferent) {
            return redirect()->route('cycles.index')
                ->with('notification', [
                    'type' => 'danger',
                    'message' => 'Impossible de supprimer ce cycle car il est defini comme cycle suivant pour "' . $cycleReferent->nom . '"'
                ]);
        }

        $cycle->delete();

        return redirect()->route('cycles.index')
            ->with('notification', ['type' => 'success', 'message' => 'Cycle supprime avec succes']);
    }

    /**
     * Ajouter des niveaux (promotions) a un cycle existant
     */
    public function addNiveaux(Request $request, Cycle $cycle)
    {
        $request->validate([
            'niveaux' => 'required|array',
            'niveaux.*' => 'nullable|string|max:50'
        ]);

        $niveaux = array_filter($request->niveaux, fn($n) => !empty(trim($n)));

        if (empty($niveaux)) {
            return redirect()->route('cycles.edit', $cycle)
                ->with('notification', ['type' => 'warning', 'message' => 'Aucun niveau a ajouter']);
        }

        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        if (!$anneeCourante) {
            return redirect()->route('cycles.edit', $cycle)
                ->with('notification', ['type' => 'error', 'message' => 'Aucune annee scolaire courante definie']);
        }

        // Recuperer l'ordre maximum actuel pour ce cycle
        $maxOrdre = Promotion::where('cycle_id', $cycle->id)
            ->where('annee_scolaire_id', $anneeCourante->id)
            ->max('ordre') ?? 0;

        $count = 0;
        $nouveauxNiveaux = $cycle->niveaux ?? [];

        foreach ($niveaux as $niveau) {
            $niveauNom = trim($niveau);

            // Verifier si le niveau existe deja pour cette annee et ce cycle
            $exists = Promotion::where('nom', $niveauNom)
                ->where('annee_scolaire_id', $anneeCourante->id)
                ->where('cycle_id', $cycle->id)
                ->exists();

            if (!$exists) {
                $maxOrdre++;
                $promotion = Promotion::create([
                    'nom' => $niveauNom,
                    'ordre' => $maxOrdre,
                    'annee_scolaire_id' => $anneeCourante->id,
                    'cycle_id' => $cycle->id,
                    'type_periode' => 'trimestre'
                ]);

                $this->createTrimestres($promotion);

                // Creer une classe par defaut
                Classe::create([
                    'nom' => $niveauNom . ' A',
                    'promotion_id' => $promotion->id
                ]);

                // Ajouter au tableau des niveaux JSON du cycle
                if (!in_array($niveauNom, $nouveauxNiveaux)) {
                    $nouveauxNiveaux[] = $niveauNom;
                }

                $count++;
            }
        }

        // Mettre a jour les niveaux JSON du cycle
        if ($count > 0) {
            $cycle->update(['niveaux' => $nouveauxNiveaux]);
        }

        if ($count > 0) {
            return redirect()->route('cycles.edit', $cycle)
                ->with('notification', ['type' => 'success', 'message' => "$count niveau(x) ajoute(s) avec succes"]);
        } else {
            return redirect()->route('cycles.edit', $cycle)
                ->with('notification', ['type' => 'warning', 'message' => 'Ces niveaux existent deja pour cette annee scolaire']);
        }
    }

    /**
     * Mettre a jour l'ordre des niveaux d'un cycle
     */
    public function updateNiveaux(Request $request, Cycle $cycle)
    {
        $request->validate([
            'niveaux_ordre' => 'required|array',
            'niveaux_ordre.*' => 'required|string|max:50'
        ]);

        $niveaux = array_filter($request->niveaux_ordre, fn($n) => !empty(trim($n)));
        $niveaux = array_values($niveaux);

        if (empty($niveaux)) {
            return redirect()->route('cycles.edit', $cycle)
                ->with('notification', ['type' => 'warning', 'message' => 'L\'ordre des niveaux ne peut pas etre vide']);
        }

        // Mettre a jour les niveaux JSON du cycle
        $cycle->update(['niveaux' => $niveaux]);

        // Mettre a jour l'ordre des promotions existantes pour l'annee courante
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();
        if ($anneeCourante) {
            foreach ($niveaux as $index => $niveau) {
                Promotion::where('cycle_id', $cycle->id)
                    ->where('annee_scolaire_id', $anneeCourante->id)
                    ->where('nom', $niveau)
                    ->update(['ordre' => $index + 1]);
            }
        }

        return redirect()->route('cycles.edit', $cycle)
            ->with('notification', ['type' => 'success', 'message' => 'Ordre des niveaux mis a jour avec succes']);
    }

    /**
     * Creer les trimestres pour une promotion
     */
    private function createTrimestres(Promotion $promotion)
    {
        $trimestres = [
            ['intitule' => 'Trimestre 1', 'rang' => 1],
            ['intitule' => 'Trimestre 2', 'rang' => 2],
            ['intitule' => 'Trimestre 3', 'rang' => 3],
        ];

        foreach ($trimestres as $trimestre) {
            Trimestre::create([
                'intitule' => $trimestre['intitule'],
                'rang' => $trimestre['rang'] ?? null,
                'promotion_id' => $promotion->id
            ]);
        }
    }
}
