<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Cycle;
use App\Models\Promotion;
use App\Models\Trimestre;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    use FiltersByCycle;

    public function index()
    {
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        if (!$anneeCourante) {
            return view('admin.promotions.index', [
                'cycles' => collect(),
                'anneeCourante' => null
            ])->with('notification', ['type' => 'warning', 'message' => 'Aucune annee scolaire courante definie']);
        }

        $cycles = Cycle::whereIn('id', $this->getAccessibleCycleIds())
            ->with(['promotions' => function ($query) use ($anneeCourante) {
                $query->where('annee_scolaire_id', $anneeCourante->id)
                      ->with(['classes.eleves', 'trimestres', 'examenOfficiel'])
                      ->orderBy('ordre');
            }])->orderBy('ordre')->get();

        return view('admin.promotions.index', compact('cycles', 'anneeCourante'));
    }

    public function updatePeriode(Request $request, Promotion $promotion)
    {
        $this->authorizeAccessPromotion($promotion);

        $request->validate([
            'type_periode' => 'required|in:trimestre,semestre'
        ]);

        // Verifier que le cycle supporte les semestres
        if ($request->type_periode === 'semestre' && !$promotion->cycle->supports_semestre) {
            return redirect()->route('promotions.index')
                ->with('notification', ['type' => 'danger', 'message' => 'Ce cycle ne supporte pas les semestres']);
        }

        $oldTypePeriode = $promotion->type_periode;
        $newTypePeriode = $request->type_periode;

        // Si le type change, recréer les périodes
        if ($oldTypePeriode !== $newTypePeriode) {
            // Supprimer les anciennes périodes (attention aux notes liées!)
            $hasNotes = $promotion->trimestres()
                ->whereHas('notes')
                ->exists();

            if ($hasNotes) {
                return redirect()->route('promotions.index')
                    ->with('notification', [
                        'type' => 'danger',
                        'message' => 'Impossible de changer le type de periode: des notes sont deja saisies pour ce niveau'
                    ]);
            }

            // Supprimer les trimestres existants
            $promotion->trimestres()->delete();

            // Créer les nouvelles périodes
            $nombrePeriodes = $newTypePeriode === 'semestre' ? 2 : 3;
            $typePeriodeLabel = $newTypePeriode === 'semestre' ? 'Semestre' : 'Trimestre';
            $anneeScolaire = $promotion->anneeScolaire;

            for ($i = 1; $i <= $nombrePeriodes; $i++) {
                Trimestre::create([
                    'intitule' => $typePeriodeLabel . ' ' . $i . ' ' . $promotion->nom . ' ' . ($anneeScolaire->annee ?? ''),
                    'promotion_id' => $promotion->id
                ]);
            }

            $promotion->update(['type_periode' => $newTypePeriode]);

            return redirect()->route('promotions.index')
                ->with('notification', [
                    'type' => 'success',
                    'message' => "Type de periode change en {$typePeriodeLabel}s pour {$promotion->nom}"
                ]);
        }

        return redirect()->route('promotions.index')
            ->with('notification', ['type' => 'info', 'message' => 'Aucun changement effectue']);
    }
}
