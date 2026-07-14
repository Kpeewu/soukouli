<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\BulletinMatiereOrdre;
use App\Models\Cycle;
use App\Models\Promotion;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BulletinConfigController extends Controller
{
    use FiltersByCycle;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (!$user->isDirecteur() && !$user->isSecretaire()) {
                abort(403, 'Seuls les directeurs et secretaires peuvent configurer l\'affichage des bulletins.');
            }
            return $next($request);
        });
    }

    /**
     * Liste des niveaux (promotions de l'annee courante) groupes par cycle,
     * restreinte aux cycles accessibles a l'utilisateur connecte.
     */
    public function index()
    {
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        if (!$anneeCourante) {
            return view('admin.bulletin-config.index', [
                'cycles' => collect(),
                'anneeCourante' => null,
            ])->with('notification', ['type' => 'warning', 'message' => 'Aucune annee scolaire courante definie']);
        }

        $cycles = Cycle::whereIn('id', $this->getAccessibleCycleIds())
            ->with(['promotions' => function ($query) use ($anneeCourante) {
                $query->where('annee_scolaire_id', $anneeCourante->id)
                    ->withCount('matieres')
                    ->orderBy('ordre');
            }])
            ->orderBy('ordre')
            ->get();

        return view('admin.bulletin-config.index', compact('cycles', 'anneeCourante'));
    }

    /**
     * Formulaire de reordonnancement des matieres pour un niveau (promotion).
     */
    public function edit(Promotion $promotion)
    {
        $this->authorizeAccessPromotion($promotion);
        $promotion->load('cycle');

        $matieresAttachees = $promotion->matieres()->orderBy('intitule')->get();

        $ordreExistant = BulletinMatiereOrdre::where('cycle_id', $promotion->cycle_id)
            ->where('niveau', $promotion->nom)
            ->orderBy('ordre')
            ->pluck('matiere_id');

        $matieresParId = $matieresAttachees->keyBy('id');

        // Matieres deja configurees, dans leur ordre sauvegarde, en ne gardant que
        // celles encore reellement rattachees a ce niveau cette annee.
        $matieresOrdonnees = $ordreExistant
            ->map(fn ($id) => $matieresParId->get($id))
            ->filter()
            ->values();

        // Matieres attachees mais jamais configurees : ajoutees a la fin.
        $idsDejaOrdonnes = $matieresOrdonnees->pluck('id');
        $matieresNonConfigurees = $matieresAttachees->reject(
            fn ($matiere) => $idsDejaOrdonnes->contains($matiere->id)
        );

        $matieres = $matieresOrdonnees->merge($matieresNonConfigurees)->values();

        return view('admin.bulletin-config.edit', compact('promotion', 'matieres'));
    }

    /**
     * Enregistre le nouvel ordre d'affichage des matieres pour ce niveau.
     */
    public function update(Request $request, Promotion $promotion)
    {
        $this->authorizeAccessPromotion($promotion);

        $idsAttaches = $promotion->matieres()->pluck('matieres.id')->all();

        $request->validate([
            'matieres_ordre' => ['required', 'array', 'size:' . count($idsAttaches)],
            'matieres_ordre.*' => ['required', 'integer', Rule::in($idsAttaches), 'distinct'],
        ]);

        foreach ($request->matieres_ordre as $index => $matiereId) {
            BulletinMatiereOrdre::updateOrCreate(
                [
                    'cycle_id' => $promotion->cycle_id,
                    'niveau' => $promotion->nom,
                    'matiere_id' => $matiereId,
                ],
                ['ordre' => $index]
            );
        }

        return redirect()->route('bulletin-config.index')
            ->with('notification', [
                'type' => 'success',
                'message' => "Ordre des matieres mis a jour pour {$promotion->nom}",
            ]);
    }
}
