@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-plus-circle mr-2"></i>Enregistrer un paiement
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('comptabilite.eleve.fiche', $eleve) }}">{{ $eleve->matricule }}</a></li>
                        <li class="breadcrumb-item">Paiement</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        @if ($notification = Session::get('notification'))
            <div class="alert alert-{{ $notification['type'] }} alert-dismissable" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <p class="mb-0">{{ $notification['message'] }}</p>
            </div>
        @endif

        <div class="row">
            {{-- Informations eleve --}}
            <div class="col-lg-4">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Eleve</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td><strong>Matricule:</strong></td>
                                <td>{{ $eleve->matricule }}</td>
                            </tr>
                            <tr>
                                <td><strong>Nom:</strong></td>
                                <td>{{ $eleve->nom }} {{ $eleve->prenom }}</td>
                            </tr>
                            <tr>
                                <td><strong>Classe:</strong></td>
                                <td>{{ $classe ? $classe->nom : '-' }}</td>
                            </tr>
                        </table>
                        <hr>
                        <div class="text-center">
                            <div class="font-size-h4 font-w700 text-danger">
                                {{ number_format($eleve->getSoldeRestant(), 0, ',', ' ') }} FCFA
                            </div>
                            <div class="text-muted">Solde a payer</div>
                        </div>
                    </div>
                </div>

                {{-- Resume des frais --}}
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Frais a payer</h3>
                    </div>
                    <div class="block-content">
                        @foreach($fraisAvecStatut as $frais)
                            @if($frais['solde'] > 0)
                                <div class="mb-2 p-2 border-left border-3x border-warning">
                                    <div class="font-w600">{{ $frais['type_frais']->nom }}</div>
                                    <small class="text-muted">Reste: {{ number_format($frais['solde'], 0, ',', ' ') }} FCFA</small>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Formulaire de paiement --}}
            <div class="col-lg-8">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Nouveau paiement</h3>
                    </div>
                    <div class="block-content">
                        <form action="{{ route('paiements.store', $eleve) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="configuration_frais_id">Type de frais <span class="text-danger">*</span></label>
                                        <select class="form-control @error('configuration_frais_id') is-invalid @enderror"
                                                id="configuration_frais_id" name="configuration_frais_id" required>
                                            <option value="">Selectionner un type de frais</option>
                                            @foreach($fraisAvecStatut as $frais)
                                                @if($frais['solde'] > 0)
                                                    <option value="{{ $frais['configuration']->id }}">
                                                        {{ $frais['type_frais']->nom }} (Reste: {{ number_format($frais['solde'], 0, ',', ' ') }} FCFA)
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('configuration_frais_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" id="tranche-group" style="display: none;">
                                        <label for="tranche_paiement_id">Tranche</label>
                                        <select class="form-control @error('tranche_paiement_id') is-invalid @enderror"
                                                id="tranche_paiement_id" name="tranche_paiement_id">
                                            <option value="">Paiement libre (sans tranche)</option>
                                        </select>
                                        @error('tranche_paiement_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="montant">Montant (FCFA) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control @error('montant') is-invalid @enderror"
                                               id="montant" name="montant" min="1" required
                                               value="{{ old('montant') }}" placeholder="Ex: 50000">
                                        @error('montant')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted" id="solde-info"></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mode_paiement">Mode de paiement <span class="text-danger">*</span></label>
                                        <select class="form-control @error('mode_paiement') is-invalid @enderror"
                                                id="mode_paiement" name="mode_paiement" required>
                                            <option value="especes" {{ old('mode_paiement') == 'especes' ? 'selected' : '' }}>Especes</option>
                                            <option value="mobile_money" {{ old('mode_paiement') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                            <option value="cheque" {{ old('mode_paiement') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                            <option value="virement" {{ old('mode_paiement') == 'virement' ? 'selected' : '' }}>Virement bancaire</option>
                                        </select>
                                        @error('mode_paiement')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="reference">Reference <span class="text-danger" id="reference-required" style="display: none;">*</span></label>
                                        <input type="text" class="form-control @error('reference') is-invalid @enderror"
                                               id="reference" name="reference" value="{{ old('reference') }}"
                                               placeholder="Ex: Numero de transaction">
                                        @error('reference')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted" id="reference-help">Obligatoire pour tout paiement autre qu'en especes.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="notes">Notes (optionnel)</label>
                                        <textarea class="form-control @error('notes') is-invalid @enderror"
                                                  id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-check mr-2"></i>Enregistrer le paiement
                                </button>
                                <a href="{{ route('comptabilite.eleve.fiche', $eleve) }}" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Frais et tranches de l'eleve, avec le solde deja calcule cote serveur
        // (montant total - montant deja paye par cet eleve)
        const fraisData = @json($fraisAvecStatut);

        const configSelect = document.getElementById('configuration_frais_id');
        const trancheGroup = document.getElementById('tranche-group');
        const trancheSelect = document.getElementById('tranche_paiement_id');
        const soldeInfo = document.getElementById('solde-info');
        const montantInput = document.getElementById('montant');

        function getFraisSelectionne() {
            return fraisData.find(f => f.configuration.id == configSelect.value);
        }

        configSelect.addEventListener('change', function() {
            const frais = getFraisSelectionne();

            if (!frais) {
                trancheGroup.style.display = 'none';
                soldeInfo.textContent = '';
                montantInput.removeAttribute('max');
                return;
            }

            montantInput.max = frais.solde;
            soldeInfo.textContent = 'Solde restant: ' + frais.solde.toLocaleString('fr-FR') + ' FCFA';

            // Seules les tranches pas encore soldees sont proposees
            const tranchesDisponibles = frais.tranches.filter(t => t.solde > 0);

            trancheSelect.innerHTML = '<option value="">Paiement libre (sans tranche)</option>';
            tranchesDisponibles.forEach(t => {
                const option = document.createElement('option');
                option.value = t.tranche.id;
                option.textContent = t.tranche.nom + ' - ' + parseInt(t.solde).toLocaleString('fr-FR') + ' FCFA (limite: ' + t.tranche.date_limite + ')';
                option.dataset.solde = t.solde;
                trancheSelect.appendChild(option);
            });

            trancheGroup.style.display = tranchesDisponibles.length > 0 ? 'block' : 'none';
        });

        trancheSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const frais = getFraisSelectionne();

            if (selectedOption.dataset.solde) {
                // Paiement d'une tranche precise: plafonne au solde de cette tranche
                montantInput.value = selectedOption.dataset.solde;
                montantInput.max = selectedOption.dataset.solde;
                soldeInfo.textContent = 'Solde restant pour cette tranche: ' + parseFloat(selectedOption.dataset.solde).toLocaleString('fr-FR') + ' FCFA';
            } else if (frais) {
                // Paiement libre: plafonne au solde global du frais
                montantInput.max = frais.solde;
                soldeInfo.textContent = 'Solde restant: ' + frais.solde.toLocaleString('fr-FR') + ' FCFA';
            }
        });

        // La reference est obligatoire pour tout mode de paiement autre que especes
        const modePaiementSelect = document.getElementById('mode_paiement');
        const referenceInput = document.getElementById('reference');
        const referenceRequired = document.getElementById('reference-required');

        function updateReferenceRequirement() {
            const requiert = modePaiementSelect.value !== 'especes';
            referenceInput.required = requiert;
            referenceRequired.style.display = requiert ? 'inline' : 'none';
        }

        modePaiementSelect.addEventListener('change', updateReferenceRequirement);
        updateReferenceRequirement();
    </script>
@endsection
