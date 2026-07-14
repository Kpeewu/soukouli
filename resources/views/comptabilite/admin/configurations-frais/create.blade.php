@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Nouvelle configuration de frais</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('configurations-frais.index') }}">Configurations</a></li>
                        <li class="breadcrumb-item">Nouveau</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        @if ($notification = Session::get('notification'))
            @if ($notification['type'] === 'success')
                <div class="alert alert-success alert-dismissable" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <p class="mb-0">{{ $notification['message'] }}</p>
                </div>
            @endif
            @if ($notification['type'] === 'warning')
                <div class="alert alert-warning alert-dismissable" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <p class="mb-0">{{ $notification['message'] }}</p>
                </div>
            @endif
            @if ($notification['type'] === 'error')
                <div class="alert alert-danger alert-dismissable" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <p class="mb-0">{{ $notification['message'] }}</p>
                </div>
            @endif
        @endif

        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Configuration des frais</h3>
            </div>
            <div class="block-content">
                <form action="{{ route('configurations-frais.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="annee_scolaire_id" value="{{ $anneeCourante->id }}">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type_frais_id">Type de frais <span class="text-danger">*</span></label>
                                <select class="form-control @error('type_frais_id') is-invalid @enderror"
                                        id="type_frais_id" name="type_frais_id" required>
                                    <option value="">Selectionner un type</option>
                                    @foreach($typesFrais as $type)
                                        <option value="{{ $type->id }}" {{ old('type_frais_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->nom }} ({{ $type->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('type_frais_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="montant">Montant (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('montant') is-invalid @enderror"
                                       id="montant" name="montant" value="{{ old('montant') }}" required min="0">
                                @error('montant')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cycle_id">Cycle <span class="text-danger">*</span></label>
                                <select class="form-control @error('cycle_id') is-invalid @enderror"
                                        id="cycle_id" name="cycle_id" required>
                                    <option value="">Selectionner un cycle</option>
                                    @foreach($cycles as $cycle)
                                        <option value="{{ $cycle->id }}" {{ old('cycle_id') == $cycle->id ? 'selected' : '' }}>
                                            {{ $cycle->nom }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('cycle_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="niveau">Niveau (optionnel)</label>
                                <select class="form-control @error('niveau') is-invalid @enderror"
                                        id="niveau" name="niveau">
                                    <option value="">Tous les niveaux du cycle</option>
                                </select>
                                @error('niveau')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Laisser vide pour appliquer a tous les niveaux</small>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5>Tranches de paiement (optionnel)</h5>
                    <p class="text-muted">Definissez les echeances de paiement si vous souhaitez diviser ce frais en plusieurs tranches.</p>

                    <div id="tranches-container">
                        {{-- Les tranches seront ajoutees ici via JavaScript --}}
                    </div>

                    <button type="button" class="btn btn-outline-primary mb-3" id="add-tranche">
                        <i class="fa fa-plus mr-2"></i>Ajouter une tranche
                    </button>

                    <div id="tranches-feedback" class="alert d-none mb-3" role="alert"></div>

                    <hr>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check mr-2"></i>Creer la configuration
                        </button>
                        <a href="{{ route('configurations-frais.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Niveaux par cycle
        const niveauxParCycle = {
            @foreach($cycles as $cycle)
                {{ $cycle->id }}: [
                    @php
                        $niveauxUniques = $cycle->promotions->pluck('nom')->unique()->sort()->values();
                    @endphp
                    @foreach($niveauxUniques as $niv)
                        '{{ $niv }}'{{ !$loop->last ? ',' : '' }}
                    @endforeach
                ],
            @endforeach
        };

        // Mise a jour des niveaux
        document.getElementById('cycle_id').addEventListener('change', function() {
            const cycleId = this.value;
            const niveauSelect = document.getElementById('niveau');
            niveauSelect.innerHTML = '<option value="">Tous les niveaux du cycle</option>';

            if (cycleId && niveauxParCycle[cycleId]) {
                niveauxParCycle[cycleId].forEach(function(niveau) {
                    const option = document.createElement('option');
                    option.value = niveau;
                    option.textContent = niveau;
                    niveauSelect.appendChild(option);
                });
            }
        });

        // Gestion des tranches
        let trancheIndex = 0;
        document.getElementById('add-tranche').addEventListener('click', function() {
            const container = document.getElementById('tranches-container');
            const div = document.createElement('div');
            div.className = 'row mb-2 tranche-row';
            div.innerHTML = `
                <div class="col-md-4">
                    <input type="text" class="form-control" name="tranches[${trancheIndex}][nom]" placeholder="Nom (ex: 1ere tranche)" required>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control" name="tranches[${trancheIndex}][montant]" placeholder="Montant" min="0" required>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="tranches[${trancheIndex}][date_limite]" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-block remove-tranche">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
            trancheIndex++;
            updateTranchesFeedback();
        });

        document.getElementById('tranches-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-tranche') || e.target.closest('.remove-tranche')) {
                e.target.closest('.tranche-row').remove();
                updateTranchesFeedback();
            }
        });

        // Verification en direct: somme des tranches = montant total, dates dans l'ordre chronologique
        function updateTranchesFeedback() {
            const rows = Array.from(document.querySelectorAll('#tranches-container .tranche-row'));
            const feedback = document.getElementById('tranches-feedback');

            rows.forEach(row => row.querySelector('input[type="date"]').classList.remove('is-invalid'));

            if (rows.length === 0) {
                feedback.classList.add('d-none');
                return true;
            }

            let somme = 0;
            let datePrecedente = null;
            let ordreValide = true;

            rows.forEach(row => {
                const montant = parseFloat(row.querySelector('input[name*="[montant]"]').value) || 0;
                const dateInput = row.querySelector('input[name*="[date_limite]"]');
                somme += montant;

                if (dateInput.value) {
                    if (datePrecedente && dateInput.value < datePrecedente) {
                        ordreValide = false;
                        dateInput.classList.add('is-invalid');
                    }
                    datePrecedente = dateInput.value;
                }
            });

            const montantTotal = parseFloat(document.getElementById('montant').value) || 0;
            const sommeOk = Math.abs(somme - montantTotal) < 0.01;

            if (sommeOk && ordreValide) {
                feedback.className = 'alert alert-success mb-3';
                feedback.textContent = `Somme des tranches: ${somme.toLocaleString('fr-FR')} FCFA — OK`;
            } else {
                feedback.className = 'alert alert-danger mb-3';
                const messages = [];
                if (!sommeOk) {
                    messages.push(`la somme des tranches (${somme.toLocaleString('fr-FR')} FCFA) doit etre egale au montant total (${montantTotal.toLocaleString('fr-FR')} FCFA)`);
                }
                if (!ordreValide) {
                    messages.push('les tranches doivent se suivre dans un ordre chronologique');
                }
                feedback.textContent = 'Attention : ' + messages.join(' et ');
            }
            feedback.classList.remove('d-none');

            return sommeOk && ordreValide;
        }

        document.getElementById('montant').addEventListener('input', updateTranchesFeedback);
        document.getElementById('tranches-container').addEventListener('input', updateTranchesFeedback);

        document.querySelector('form').addEventListener('submit', function(e) {
            if (!updateTranchesFeedback()) {
                e.preventDefault();
                document.getElementById('tranches-feedback').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
@endsection
