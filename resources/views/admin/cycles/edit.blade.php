@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Modifier le Cycle</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('cycles.index') }}">Cycles</a></li>
                        <li class="breadcrumb-item">{{ $cycle->nom }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Modifier : {{ $cycle->nom }}</h3>
            </div>
            <div class="block-content">
                <form action="{{ route('cycles.update', $cycle) }}" method="POST" id="form-cycle-edit">
                    @csrf
                    @method('PUT')

                    <div class="block block-rounded block-bordered mb-4 pb-4">
                        <div class="block-header block-header-default">
                            <h3 class="block-title"><i class="fa fa-layer-group mr-2 text-primary"
                                    aria-hidden="true"></i>Informations générales</h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="nom">Nom du cycle</label>
                                        <input type="text" class="form-control @error('nom') is-invalid @enderror"
                                            id="nom" name="nom" value="{{ old('nom', $cycle->nom) }}" required>
                                        @error('nom')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="code">Code</label>
                                        <input type="text"
                                            class="form-control text-uppercase @error('code') is-invalid @enderror"
                                            id="code" name="code" value="{{ old('code', $cycle->code) }}" required>
                                        @error('code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Code unique en majuscules.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ordre"><i class="fa fa-hashtag mr-1" aria-hidden="true"></i>Ordre
                                            d'affichage</label>
                                        <input type="number"
                                            class="form-control @error('ordre') is-invalid @enderror" id="ordre"
                                            name="ordre" value="{{ old('ordre', $cycle->ordre) }}" min="1" required>
                                        @error('ordre')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label for="description"><i class="fa fa-align-left mr-1"
                                                aria-hidden="true"></i>Description</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                            id="description" name="description" rows="2">{{ old('description', $cycle->description) }}</textarea>
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
                            <h3 class="block-title"><i class="fa fa-cogs mr-2 text-primary" aria-hidden="true"></i>
                                Progression des élèves</h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="d-block"><i class="fa fa-calendar-alt mr-1"
                                                aria-hidden="true"></i>Supporte les semestres</label>
                                        @php $supportsSemestre = old('supports_semestre', $cycle->supports_semestre) ? '1' : '0'; @endphp
                                        <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                            <label
                                                class="btn btn-alt-secondary flex-fill @if ($supportsSemestre == '0') active @endif">
                                                <input type="radio" name="supports_semestre" value="0"
                                                    autocomplete="off" @checked($supportsSemestre == '0')>
                                                Non (trimestres)
                                            </label>
                                            <label
                                                class="btn btn-alt-secondary flex-fill @if ($supportsSemestre == '1') active @endif">
                                                <input type="radio" name="supports_semestre" value="1"
                                                    autocomplete="off" @checked($supportsSemestre == '1')>
                                                Oui (trimestres ou semestres)
                                            </label>
                                        </div>
                                        @error('supports_semestre')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="cycle_suivant_id"><i class="fa fa-arrow-right mr-1"
                                                aria-hidden="true"></i>Cycle suivant</label>
                                        <select
                                            class="form-control @error('cycle_suivant_id') is-invalid @enderror"
                                            id="cycle_suivant_id" name="cycle_suivant_id">
                                            <option value="">-- Aucun (fin de scolarité) --</option>
                                            @foreach ($cyclesDisponibles as $c)
                                                <option value="{{ $c->id }}"
                                                    {{ old('cycle_suivant_id', $cycle->cycle_suivant_id) == $c->id ? 'selected' : '' }}>
                                                    {{ $c->nom }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('cycle_suivant_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Cycle vers lequel les élèves passent
                                            après avoir terminé celui-ci.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Mettre à jour</button>
                        <button type="button" class="btn btn-alt-secondary" id="btn-reset-cycle-edit">
                            <i class="fa fa-undo mr-1" aria-hidden="true"></i>Réinitialiser
                        </button>
                        <a href="{{ route('cycles.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section Configuration des niveaux -->
        @php
            $niveauxJSON = $cycle->niveaux ?? [];
            $niveauxPromotions = $cycle->promotions->pluck('nom')->unique()->sort()->values()->toArray();
            $niveauxManquants = array_diff($niveauxPromotions, $niveauxJSON);
        @endphp

        <div class="block block-rounded block-bordered mb-4 pb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-list-ol mr-2 text-primary" aria-hidden="true"></i>Ordre de
                    progression - {{ $cycle->nom }}</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-6">
                        <small class="form-text text-muted mb-3">
                            Définissez l'ordre de progression des élèves dans ce cycle. Glissez-déposez la poignée
                            <i class="fa fa-grip-vertical"></i> pour réordonner. Le dernier niveau mène au cycle
                            suivant.
                        </small>

                        <form action="{{ route('cycles.update-niveaux', $cycle) }}" method="POST"
                            id="form-niveaux-ordre">
                            @csrf
                            <ul class="list-group mb-3" id="sortable-niveaux">
                                @if (!empty($niveauxJSON))
                                    @foreach ($niveauxJSON as $index => $niveau)
                                        <li class="list-group-item d-flex justify-content-between align-items-center niveau-item"
                                            data-niveau="{{ $niveau }}">
                                            <div class="d-flex align-items-center">
                                                <span class="mr-3 text-muted" style="cursor: grab;"><i
                                                        class="fa fa-grip-vertical"></i></span>
                                                <span class="niveau-ordre badge badge-primary mr-2">{{ $index + 1 }}</span>
                                                <input type="hidden" name="niveaux_ordre[]" value="{{ $niveau }}">
                                                <span>{{ $niveau }}</span>
                                            </div>
                                            <div>
                                                @if ($index === count($niveauxJSON) - 1)
                                                    <span class="badge badge-info mr-2 dernier-niveau-badge">
                                                        @if ($cycle->cycleSuivant)
                                                            → {{ $cycle->cycleSuivant->nom }}
                                                        @else
                                                            Fin de scolarité
                                                        @endif
                                                    </span>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="removeNiveauOrdre(this)" title="Retirer">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>

                            @if (!empty($niveauxJSON))
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa fa-save mr-1"></i> Enregistrer l'ordre
                                </button>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle mr-2"></i>
                                    Aucun ordre de progression défini. Le passage automatique ne fonctionnera pas
                                    correctement.
                                </div>
                            @endif
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fa fa-graduation-cap mr-2 text-primary" aria-hidden="true"></i>Promotions
                            existantes (année courante)</h5>
                        <small class="form-text text-muted mb-3">Ces promotions ont des élèves inscrits.</small>

                        @if (!empty($niveauxPromotions))
                            <div class="mb-3">
                                @foreach ($niveauxPromotions as $niveau)
                                    <span class="badge badge-primary mr-1 mb-1">{{ $niveau }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">Aucune promotion pour cette année scolaire.</p>
                        @endif

                        @if (!empty($niveauxManquants))
                            <div class="alert alert-warning mt-3">
                                <strong>Niveaux non inclus dans l'ordre de progression :</strong>
                                <div class="mt-2">
                                    @foreach ($niveauxManquants as $niveau)
                                        <button type="button" class="btn btn-sm btn-outline-primary mr-1 mb-1"
                                            onclick="addNiveauToOrdre('{{ $niveau }}')">
                                            <i class="fa fa-plus mr-1"></i>{{ $niveau }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded block-bordered mb-4 pb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-plus-circle mr-2 text-primary" aria-hidden="true"></i>Ajouter
                    de nouveaux niveaux</h3>
            </div>
            <div class="block-content">
                <small class="form-text text-muted mb-3">
                    Les nouveaux niveaux seront créés pour l'année scolaire courante et ajoutés à l'ordre de
                    progression.
                </small>

                <form action="{{ route('cycles.add-niveaux', $cycle) }}" method="POST">
                    @csrf
                    <div id="niveaux-container">
                        <div class="input-group mb-2 niveau-row">
                            <input type="text" class="form-control" name="niveaux[]" placeholder="Ex : CM1">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-danger btn-remove-niveau"
                                    onclick="removeNiveau(this)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addNiveau()">
                        <i class="fa fa-plus mr-1"></i> Ajouter un niveau
                    </button>

                    <div class="form-group mt-3 mb-0">
                        <button type="submit" class="btn btn-primary">Créer les niveaux</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sortable.js pour le drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // Initialiser le drag and drop pour l'ordre des niveaux
        document.addEventListener('DOMContentLoaded', function() {
            const sortableList = document.getElementById('sortable-niveaux');
            if (sortableList) {
                new Sortable(sortableList, {
                    animation: 150,
                    handle: '.fa-grip-vertical',
                    ghostClass: 'bg-light',
                    onEnd: function() {
                        updateNiveauxOrdre();
                    }
                });
            }
        });

        function updateNiveauxOrdre() {
            const items = document.querySelectorAll('#sortable-niveaux .niveau-item');
            items.forEach((item, index) => {
                item.querySelector('.niveau-ordre').textContent = index + 1;
                // Mettre a jour le badge du dernier niveau
                const badge = item.querySelector('.dernier-niveau-badge');
                if (badge) badge.remove();
            });

            // Ajouter le badge au dernier element
            if (items.length > 0) {
                const lastItem = items[items.length - 1];
                const badgeContainer = lastItem.querySelector('div:last-child');
                const cycleSuivant = '{{ $cycle->cycleSuivant ? $cycle->cycleSuivant->nom : '' }}';
                const badgeText = cycleSuivant ? '→ ' + cycleSuivant : 'Fin de scolarite';
                const badge = document.createElement('span');
                badge.className = 'badge badge-info mr-2 dernier-niveau-badge';
                badge.textContent = badgeText;
                badgeContainer.insertBefore(badge, badgeContainer.firstChild);
            }
        }

        function removeNiveauOrdre(button) {
            const item = button.closest('.niveau-item');
            const container = document.getElementById('sortable-niveaux');
            if (container.querySelectorAll('.niveau-item').length > 1) {
                item.remove();
                updateNiveauxOrdre();
            } else {
                alert('Vous devez garder au moins un niveau dans l\'ordre de progression.');
            }
        }

        function addNiveauToOrdre(niveau) {
            const container = document.getElementById('sortable-niveaux');
            const count = container.querySelectorAll('.niveau-item').length + 1;

            const newItem = document.createElement('li');
            newItem.className = 'list-group-item d-flex justify-content-between align-items-center niveau-item';
            newItem.dataset.niveau = niveau;
            newItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="mr-3 text-muted" style="cursor: grab;"><i class="fa fa-grip-vertical"></i></span>
                    <span class="niveau-ordre badge badge-primary mr-2">${count}</span>
                    <input type="hidden" name="niveaux_ordre[]" value="${niveau}">
                    <span>${niveau}</span>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeNiveauOrdre(this)" title="Retirer">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            `;

            container.appendChild(newItem);
            updateNiveauxOrdre();

            // Cacher le bouton d'ajout
            const buttons = document.querySelectorAll('[onclick*="addNiveauToOrdre"]');
            buttons.forEach(btn => {
                if (btn.textContent.includes(niveau)) {
                    btn.style.display = 'none';
                }
            });
        }

        // Fonctions pour ajouter de nouveaux niveaux (section du bas)
        function addNiveau() {
            const container = document.getElementById('niveaux-container');
            const newRow = document.createElement('div');
            newRow.className = 'input-group mb-2 niveau-row';
            newRow.innerHTML = `
                <input type="text" class="form-control" name="niveaux[]" placeholder="Ex : CM1">
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger btn-remove-niveau" onclick="removeNiveau(this)">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
        }

        function removeNiveau(button) {
            const row = button.closest('.niveau-row');
            const container = document.getElementById('niveaux-container');
            if (container.querySelectorAll('.niveau-row').length > 1) {
                row.remove();
            } else {
                row.querySelector('input').value = '';
            }
        }

        // Genere automatiquement le code a partir du nom, tant que l'utilisateur
        // n'a pas lui-meme modifie le champ code (deja verrouille ici puisque
        // le code existant est pre-rempli).
        const nomField = document.getElementById('nom');
        const codeField = document.getElementById('code');
        const DIACRITICS = /[\u0300-\u036f]/g;
        let codeLocked = codeField.value.trim() !== '';

        codeField.addEventListener('input', () => {
            codeLocked = true;
        });

        nomField.addEventListener('input', () => {
            if (!codeLocked) {
                codeField.value = nomField.value
                    .toUpperCase()
                    .normalize('NFD')
                    .replace(DIACRITICS, '')
                    .trim();
            }
        });

        // Reinitialise le formulaire "Informations generales" a l'etat charge au
        // depart (annule les modifications non enregistrees).
        document.getElementById('btn-reset-cycle-edit').addEventListener('click', () => {
            document.getElementById('form-cycle-edit').reset();

            document.querySelectorAll('#form-cycle-edit .btn-group-toggle .btn').forEach((label) => {
                label.classList.toggle('active', label.querySelector('input').checked);
            });

            codeLocked = codeField.value.trim() !== '';
        });
    </script>
@endsection
