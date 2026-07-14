@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Ajouter un Examen Officiel</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx"
                                href="{{ route('examens-officiels.index') }}">Examens Officiels</a></li>
                        <li class="breadcrumb-item">Nouveau</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Nouvel Examen Officiel</h3>
            </div>
            <div class="block-content">
                <form action="{{ route('examens-officiels.store') }}" method="POST" id="form-examen">
                    @csrf

                    <div class="block block-rounded block-bordered mb-4 pb-4">
                        <div class="block-header block-header-default">
                            <h3 class="block-title"><i class="fa fa-graduation-cap mr-2 text-primary"
                                    aria-hidden="true"></i>Informations générales</h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nom">Nom de l'examen</label>
                                        <input type="text" class="form-control @error('nom') is-invalid @enderror"
                                            id="nom" name="nom" value="{{ old('nom') }}"
                                            placeholder="Ex: Brevet d'Etudes du Premier Cycle" required>
                                        @error('nom')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="code">Code</label>
                                        <input type="text"
                                            class="form-control text-uppercase @error('code') is-invalid @enderror"
                                            id="code" name="code" value="{{ old('code') }}"
                                            placeholder="Ex: BEPC, BAC1, BAC2..." required>
                                        @error('code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Code unique en majuscules. À adapter si
                                            plusieurs examens partagent un nom proche (BAC1 / BAC2).</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label for="description"><i class="fa fa-align-left mr-1"
                                                aria-hidden="true"></i>Description</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                            id="description" name="description" rows="2" placeholder="Description de l'examen...">{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded block-bordered mb-4 pb-4">
                        <div class="block-header block-header-default">
                            <h3 class="block-title"><i class="fa fa-link mr-2 text-primary" aria-hidden="true"></i>
                                Rattachement pédagogique</h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cycle_id"><i class="fa fa-layer-group mr-1"
                                                aria-hidden="true"></i>Cycle</label>
                                        <select class="form-control @error('cycle_id') is-invalid @enderror"
                                            id="cycle_id" name="cycle_id" required>
                                            <option value="">Sélectionner un cycle</option>
                                            @foreach ($cycles as $cycle)
                                                <option value="{{ $cycle->id }}"
                                                    {{ old('cycle_id') == $cycle->id ? 'selected' : '' }}>
                                                    {{ $cycle->nom }}</option>
                                            @endforeach
                                        </select>
                                        @error('cycle_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="niveau_requis"><i class="fa fa-signal mr-1"
                                                aria-hidden="true"></i>Niveau requis</label>
                                        <select class="form-control @error('niveau_requis') is-invalid @enderror"
                                            id="niveau_requis" name="niveau_requis" required disabled>
                                            <option value="">Sélectionnez d'abord un cycle</option>
                                        </select>
                                        @error('niveau_requis')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Le niveau de classe pour passer cet
                                            examen.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-center mb-0 mt-2" id="liaison-preview"
                                style="display: none;">
                                <i class="fa fa-info-circle mr-2" aria-hidden="true"></i>
                                <span id="liaison-preview-text"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Créer l'examen</button>
                        <a href="{{ route('examens-officiels.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Niveaux par cycle (chargés dynamiquement depuis les promotions)
        const niveauxParCycle = {
            @foreach ($cycles as $cycle)
                {{ $cycle->id }}: [
                    @php
                        $niveauxUniques = $cycle->promotions->pluck('nom')->unique()->sort()->values();
                    @endphp
                    @foreach ($niveauxUniques as $niveau)
                        '{{ $niveau }}'{{ !$loop->last ? ',' : '' }}
                    @endforeach
                ],
            @endforeach
        };
        const nomsCycles = {
            @foreach ($cycles as $cycle)
                {{ $cycle->id }}: '{{ $cycle->nom }}',
            @endforeach
        };

        const cycleSelect = document.getElementById('cycle_id');
        const niveauSelect = document.getElementById('niveau_requis');
        const oldNiveau = '{{ old('niveau_requis') }}';
        const preview = document.getElementById('liaison-preview');
        const previewText = document.getElementById('liaison-preview-text');

        function updateNiveaux() {
            const cycleId = cycleSelect.value;
            niveauSelect.innerHTML = '';
            preview.style.display = 'none';

            if (!cycleId) {
                niveauSelect.innerHTML = '<option value="">Sélectionnez d\'abord un cycle</option>';
                niveauSelect.disabled = true;
                return;
            }

            if (!niveauxParCycle[cycleId] || niveauxParCycle[cycleId].length === 0) {
                niveauSelect.innerHTML = '<option value="">Aucun niveau disponible pour ce cycle</option>';
                niveauSelect.disabled = true;
                return;
            }

            niveauSelect.disabled = false;
            niveauSelect.innerHTML = '<option value="">Sélectionner un niveau</option>';

            niveauxParCycle[cycleId].forEach(function(niveau) {
                const option = document.createElement('option');
                option.value = niveau;
                option.textContent = niveau;
                if (oldNiveau === niveau) {
                    option.selected = true;
                }
                niveauSelect.appendChild(option);
            });

            updatePreview();
        }

        function updatePreview() {
            const cycleId = cycleSelect.value;
            const niveau = niveauSelect.value;

            if (!cycleId || !niveau) {
                preview.style.display = 'none';
                return;
            }

            previewText.textContent = 'Cet examen sera automatiquement associé à la promotion "' + niveau +
                '" du cycle ' + nomsCycles[cycleId] + '.';
            preview.style.display = 'flex';
        }

        cycleSelect.addEventListener('change', updateNiveaux);
        niveauSelect.addEventListener('change', updatePreview);

        if (cycleSelect.value) {
            updateNiveaux();
        }
    </script>
@endsection
