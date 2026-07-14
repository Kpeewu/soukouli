<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\ConfigurationFrais;
use App\Models\Eleve;
use App\Models\TranchePaiement;
use App\Services\ComptabiliteService;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    use FiltersByCycle;

    protected ComptabiliteService $comptabiliteService;

    public function __construct(ComptabiliteService $comptabiliteService)
    {
        $this->comptabiliteService = $comptabiliteService;
    }

    /**
     * Formulaire de paiement pour un eleve
     */
    public function create(Eleve $eleve)
    {
        $eleve->load(['classes.promotion.cycle']);
        $classe = $eleve->getClasseActuelle();
        $this->authorizeAccessClasse($classe);

        $fraisAvecStatut = $this->comptabiliteService->getFraisEleve($eleve);
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        return view('comptabilite.paiements.create', compact('eleve', 'fraisAvecStatut', 'classe', 'anneeCourante'));
    }

    /**
     * Enregistrer un paiement
     */
    public function store(Request $request, Eleve $eleve)
    {
        $request->validate([
            'configuration_frais_id' => 'required|exists:configurations_frais,id',
            'tranche_paiement_id' => 'nullable|exists:tranches_paiement,id',
            'montant' => 'required|numeric|min:1',
            'mode_paiement' => 'required|in:especes,cheque,mobile_money,virement',
            'reference' => 'required_unless:mode_paiement,especes|nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ], [
            'reference.required_unless' => 'La reference est obligatoire pour un paiement qui n\'est pas en especes.',
        ]);

        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        if (!$anneeCourante) {
            return back()->with('notification', ['type' => 'error', 'message' => 'Aucune annee scolaire courante']);
        }

        // Verifier que la configuration est valide pour l'eleve
        $config = ConfigurationFrais::find($request->configuration_frais_id);
        $classe = $eleve->getClasseActuelle();

        if (!$classe || !$classe->promotion) {
            return back()->with('notification', ['type' => 'error', 'message' => 'L\'eleve n\'est pas inscrit dans une classe']);
        }

        $this->authorizeAccessClasse($classe);

        if ($config->cycle_id !== $classe->promotion->cycle_id) {
            return back()->with('notification', ['type' => 'error', 'message' => 'Configuration de frais invalide pour cet eleve']);
        }

        // Verifier la tranche si fournie, et que le montant ne depasse pas le solde disponible
        if ($request->tranche_paiement_id) {
            $tranche = TranchePaiement::find($request->tranche_paiement_id);
            if (!$tranche || $tranche->configuration_frais_id !== $config->id) {
                return back()->withInput()->with('notification', ['type' => 'error', 'message' => 'Tranche invalide']);
            }

            $soldeTranche = (float) $tranche->montant - $tranche->getMontantPayeParEleve($eleve->id);

            if ($soldeTranche <= 0) {
                return back()->withInput()->with('notification', ['type' => 'error', 'message' => 'Cette tranche est deja soldee']);
            }

            if ($request->montant > $soldeTranche) {
                return back()->withInput()->with('notification', ['type' => 'error', 'message' => 'Le montant depasse le solde de la tranche (' . number_format($soldeTranche, 0, ',', ' ') . ' FCFA)']);
            }
        } else {
            $totalPayeConfig = (float) $eleve->paiements()->where('configuration_frais_id', $config->id)->valide()->sum('montant');
            $soldeConfig = (float) $config->montant - $totalPayeConfig;

            if ($soldeConfig <= 0) {
                return back()->withInput()->with('notification', ['type' => 'error', 'message' => 'Ce frais est deja solde']);
            }

            if ($request->montant > $soldeConfig) {
                return back()->withInput()->with('notification', ['type' => 'error', 'message' => 'Le montant depasse le solde du frais (' . number_format($soldeConfig, 0, ',', ' ') . ' FCFA)']);
            }
        }

        $paiement = $this->comptabiliteService->enregistrerPaiement([
            'eleve_id' => $eleve->id,
            'configuration_frais_id' => $request->configuration_frais_id,
            'tranche_paiement_id' => $request->tranche_paiement_id,
            'montant' => $request->montant,
            'mode_paiement' => $request->mode_paiement,
            'reference' => $request->reference,
            'notes' => $request->notes,
            'annee_scolaire_id' => $anneeCourante->id,
            'motif' => $config->typeFrais->nom,
        ], auth()->user());

        return redirect()->route('comptabilite.eleve.fiche', $eleve)
            ->with('notification', [
                'type' => 'success',
                'message' => 'Paiement enregistre avec succes. Recu N° ' . $paiement->recu->numero
            ]);
    }
}
