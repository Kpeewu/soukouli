<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\ConfigurationFrais;
use App\Models\Cycle;
use App\Models\TranchePaiement;
use App\Models\TypeFrais;
use Illuminate\Http\Request;

class ConfigurationFraisController extends Controller
{
    public function index()
    {
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        $configurations = ConfigurationFrais::with(['typeFrais', 'cycle', 'tranches'])
            ->when($anneeCourante, fn($q) => $q->where('annee_scolaire_id', $anneeCourante->id))
            ->orderBy('cycle_id')
            ->orderBy('niveau')
            ->get();

        $cycles = Cycle::orderBy('ordre')->get();

        return view('comptabilite.admin.configurations-frais.index', compact('configurations', 'cycles', 'anneeCourante'));
    }

    public function create()
    {
        $typesFrais = TypeFrais::actif()->orderBy('nom')->get();
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        $cycles = Cycle::with(['promotions' => function($query) use ($anneeCourante) {
            if ($anneeCourante) {
                $query->where('annee_scolaire_id', $anneeCourante->id)->orderBy('ordre');
            }
        }])->orderBy('ordre')->get();

        return view('comptabilite.admin.configurations-frais.create', compact('typesFrais', 'cycles', 'anneeCourante'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type_frais_id' => 'required|exists:types_frais,id',
            'cycle_id' => 'required|exists:cycles,id',
            'niveau' => 'nullable|string|max:50',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'montant' => 'required|numeric|min:0',
            'tranches' => 'nullable|array',
            'tranches.*.nom' => 'required_with:tranches|string|max:100',
            'tranches.*.montant' => 'required_with:tranches|numeric|min:0',
            'tranches.*.date_limite' => 'required_with:tranches|date',
        ]);

        // Verifier unicite
        $exists = ConfigurationFrais::where('type_frais_id', $request->type_frais_id)
            ->where('cycle_id', $request->cycle_id)
            ->where('niveau', $request->niveau)
            ->where('annee_scolaire_id', $request->annee_scolaire_id)
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->with('notification', ['type' => 'error', 'message' => 'Cette configuration existe deja']);
        }
        
        // Verifier la somme des tranches et leur ordre chronologique
        if ($request->has('tranches') && is_array($request->tranches)) {
            if ($erreur = $this->validerTranches($request->tranches, $request->montant)) {
                return back()->withInput()
                    ->with('notification', ['type' => 'error', 'message' => $erreur]);
            }
        }


        $config = ConfigurationFrais::create([
            'type_frais_id' => $request->type_frais_id,
            'cycle_id' => $request->cycle_id,
            'niveau' => $request->niveau ?: null,
            'annee_scolaire_id' => $request->annee_scolaire_id,
            'montant' => $request->montant,
            'actif' => true,
        ]);

        // Creer les tranches si fournies
        if ($request->has('tranches') && is_array($request->tranches)) {
            foreach ($request->tranches as $index => $trancheData) {
                if (!empty($trancheData['nom']) && !empty($trancheData['montant'])) {
                    TranchePaiement::create([
                        'configuration_frais_id' => $config->id,
                        'nom' => $trancheData['nom'],
                        'numero' => $index + 1,
                        'montant' => $trancheData['montant'],
                        'date_limite' => $trancheData['date_limite'],
                    ]);
                }
            }
        }

        return redirect()->route('configurations-frais.index')
            ->with('notification', ['type' => 'success', 'message' => 'Configuration de frais creee avec succes']);
    }

    public function edit(ConfigurationFrais $configurations_frai)
    {
        $config = $configurations_frai->load(['typeFrais', 'cycle', 'tranches', 'anneeScolaire']);
        $typesFrais = TypeFrais::actif()->orderBy('nom')->get();

        // Utiliser l'annee de la configuration pour afficher les bons niveaux
        $anneeScolaire = $config->anneeScolaire;

        $cycles = Cycle::with(['promotions' => function($query) use ($anneeScolaire) {
            if ($anneeScolaire) {
                $query->where('annee_scolaire_id', $anneeScolaire->id)->orderBy('ordre');
            }
        }])->orderBy('ordre')->get();

        return view('comptabilite.admin.configurations-frais.edit', compact('config', 'typesFrais', 'cycles'));
    }

    public function update(Request $request, ConfigurationFrais $configurations_frai)
    {
        $request->validate([
            'type_frais_id' => 'required|exists:types_frais,id',
            'cycle_id' => 'required|exists:cycles,id',
            'niveau' => 'nullable|string|max:50',
            'montant' => 'required|numeric|min:0',
            'actif' => 'boolean',
            'tranches' => 'nullable|array',
            'tranches.*.id' => 'nullable|exists:tranches_paiement,id',
            'tranches.*.nom' => 'required_with:tranches|string|max:100',
            'tranches.*.montant' => 'required_with:tranches|numeric|min:0',
            'tranches.*.date_limite' => 'required_with:tranches|date',
        ]);

        // Verifier unicite (excluant l'enregistrement actuel)
        $exists = ConfigurationFrais::where('type_frais_id', $request->type_frais_id)
            ->where('cycle_id', $request->cycle_id)
            ->where('niveau', $request->niveau)
            ->where('annee_scolaire_id', $configurations_frai->annee_scolaire_id)
            ->where('id', '!=', $configurations_frai->id)
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->with('notification', ['type' => 'error', 'message' => 'Cette configuration existe deja']);
        }

        // Verifier la somme des tranches et leur ordre chronologique
        if ($request->has('tranches') && is_array($request->tranches)) {
            if ($erreur = $this->validerTranches($request->tranches, $request->montant)) {
                return back()->withInput()
                    ->with('notification', ['type' => 'error', 'message' => $erreur]);
            }
        }


        $configurations_frai->update([
            'type_frais_id' => $request->type_frais_id,
            'cycle_id' => $request->cycle_id,
            'niveau' => $request->niveau ?: null,
            'montant' => $request->montant,
            'actif' => $request->boolean('actif', true),
        ]);

        // Mettre a jour les tranches
        $tranchesIds = [];
        if ($request->has('tranches') && is_array($request->tranches)) {
            foreach ($request->tranches as $index => $trancheData) {
                if (!empty($trancheData['nom']) && !empty($trancheData['montant'])) {
                    if (!empty($trancheData['id'])) {
                        // Mise a jour
                        $tranche = TranchePaiement::find($trancheData['id']);
                        if ($tranche) {
                            $tranche->update([
                                'nom' => $trancheData['nom'],
                                'numero' => $index + 1,
                                'montant' => $trancheData['montant'],
                                'date_limite' => $trancheData['date_limite'],
                            ]);
                            $tranchesIds[] = $tranche->id;
                        }
                    } else {
                        // Nouvelle tranche
                        $tranche = TranchePaiement::create([
                            'configuration_frais_id' => $configurations_frai->id,
                            'nom' => $trancheData['nom'],
                            'numero' => $index + 1,
                            'montant' => $trancheData['montant'],
                            'date_limite' => $trancheData['date_limite'],
                        ]);
                        $tranchesIds[] = $tranche->id;
                    }
                }
            }
        }

        // Supprimer les tranches non presentes
        $configurations_frai->tranches()->whereNotIn('id', $tranchesIds)->delete();

        return redirect()->route('configurations-frais.index')
            ->with('notification', ['type' => 'success', 'message' => 'Configuration mise a jour']);
    }

    public function destroy(ConfigurationFrais $configurations_frai)
    {
        if ($configurations_frai->paiements()->exists()) {
            return redirect()->route('configurations-frais.index')
                ->with('notification', ['type' => 'error', 'message' => 'Cette configuration a des paiements associes']);
        }

        $configurations_frai->tranches()->delete();
        $configurations_frai->delete();

        return redirect()->route('configurations-frais.index')
            ->with('notification', ['type' => 'success', 'message' => 'Configuration supprimee']);
    }

    /**
     * Ajouter une tranche a une configuration
     */
    public function storeTranche(Request $request, ConfigurationFrais $config)
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'montant' => 'required|numeric|min:0',
            'date_limite' => 'required|date',
        ]);

        $numero = $config->tranches()->max('numero') + 1;

        TranchePaiement::create([
            'configuration_frais_id' => $config->id,
            'nom' => $request->nom,
            'numero' => $numero,
            'montant' => $request->montant,
            'date_limite' => $request->date_limite,
        ]);

        return back()->with('notification', ['type' => 'success', 'message' => 'Tranche ajoutee']);
    }

    /**
     * Verifie que la somme des tranches correspond au montant total et que
     * les dates limites se suivent dans l'ordre chronologique (dans l'ordre
     * de saisie, qui determine aussi le numero de chaque tranche).
     * Retourne un message d'erreur, ou null si tout est valide.
     */
    private function validerTranches(array $tranches, $montantTotal): ?string
    {
        $sommeTranches = 0.0;
        $dateLimitePrecedente = null;

        foreach ($tranches as $trancheData) {
            if (empty($trancheData['montant'])) {
                continue;
            }

            $sommeTranches += (float) $trancheData['montant'];

            if ($dateLimitePrecedente !== null && $trancheData['date_limite'] < $dateLimitePrecedente) {
                return "Les tranches doivent se suivre dans un ordre chronologique";
            }
            $dateLimitePrecedente = $trancheData['date_limite'];
        }

        if (abs($sommeTranches - (float) $montantTotal) > 0.01) {
            return 'La somme des tranches (' . number_format($sommeTranches, 0, ',', ' ')
                . ') doit etre egale au montant total (' . number_format((float) $montantTotal, 0, ',', ' ') . ')';
        }

        return null;
    }
}
