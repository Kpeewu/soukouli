@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Ajouter un Cycle</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('cycles.index') }}">Cycles</a></li>
                        <li class="breadcrumb-item">Nouveau</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Nouveau Cycle</h3>
            </div>
            <div class="block-content">

                <div class="alert alert-info d-flex align-items-center flex-wrap mb-4" role="alert">
                    <div class="mr-3 mb-2 mb-sm-0">
                        <i class="fa fa-magic mr-1" aria-hidden="true"></i>
                        <strong>Démarrage rapide :</strong> pré-remplit le nom, le code et les niveaux standards.
                    </div>
                    <div class="btn-group flex-wrap mb-2 mb-sm-0" id="cycle-presets">
                        <button type="button" class="btn btn-sm btn-alt-primary" data-preset="maternelle">Maternelle</button>
                        <button type="button" class="btn btn-sm btn-alt-primary" data-preset="primaire">Primaire</button>
                        <button type="button" class="btn btn-sm btn-alt-primary" data-preset="college">Collège</button>
                        <button type="button" class="btn btn-sm btn-alt-primary" data-preset="lycee">Lycée</button>
                    </div>
                </div>

                <form action="{{ route('cycles.store') }}" method="POST" id="form-cycle">
                    @csrf

                    <div class="block block-rounded block-bordered pb-4 mb-4">
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
                                            id="nom" name="nom" value="{{ old('nom') }}"
                                            placeholder="Ex: Maternelle, Primaire..." required>
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
                                            id="code" name="code" value="{{ old('code') }}"
                                            placeholder="Ex: MATERNELLE..." required>
                                        @error('code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Généré depuis le nom, modifiable.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ordre"><i class="fa fa-hashtag mr-1" aria-hidden="true"></i>Ordre
                                            d'affichage</label>
                                        <input type="number"
                                            class="form-control @error('ordre') is-invalid @enderror" id="ordre"
                                            name="ordre" value="{{ old('ordre', $prochainOrdre) }}" min="1" required>
                                        @error('ordre')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Suggéré automatiquement.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label for="description"><i class="fa fa-align-left mr-1"
                                                aria-hidden="true"></i>Description</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                            id="description" name="description" rows="2" placeholder="Description du cycle...">{{ old('description') }}</textarea>
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
                                        @php $supportsSemestre = old('supports_semestre', '0'); @endphp
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
                                                    {{ old('cycle_suivant_id') == $c->id ? 'selected' : '' }}>
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

                    <div class="block block-rounded block-bordered mb-4 pb-4">
                        <div class="block-header block-header-default">
                            <h3 class="block-title"><i class="fa fa-list-ol mr-2 text-primary" aria-hidden="true"></i>
                                Niveaux du cycle</h3>
                        </div>
                        <div class="block-content">
                            <small class="form-text text-muted mb-2">
                                Ajoutez les niveaux dans l'ordre de progression (le niveau 1 est le premier suivi par
                                les élèves). Glissez-déposez la poignée <i class="fa fa-grip-vertical"></i> pour
                                réordonner.
                                <br><strong>Important :</strong> cet ordre détermine le passage en classe supérieure.
                            </small>

                            <div id="niveaux-container">
                                @if (old('niveaux'))
                                    @foreach (old('niveaux') as $index => $niveau)
                                        <div class="input-group mb-2 niveau-row">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text niveau-drag" style="cursor: grab;">
                                                    <i class="fa fa-grip-vertical text-muted mr-2"
                                                        aria-hidden="true"></i>
                                                    <span class="niveau-ordre">{{ $index + 1 }}</span>
                                                </span>
                                            </div>
                                            <input type="text" class="form-control" name="niveaux[]"
                                                value="{{ $niveau }}" placeholder="Ex : CM1">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-danger btn-remove-niveau"
                                                    onclick="removeNiveau(this)">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="input-group mb-2 niveau-row">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text niveau-drag" style="cursor: grab;">
                                                <i class="fa fa-grip-vertical text-muted mr-2" aria-hidden="true"></i>
                                                <span class="niveau-ordre">1</span>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control" name="niveaux[]"
                                            placeholder="Ex : CM1">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger btn-remove-niveau"
                                                onclick="removeNiveau(this)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addNiveau()">
                                <i class="fa fa-plus mr-1"></i> Ajouter un niveau
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Créer le cycle</button>
                        <button type="button" class="btn btn-alt-secondary" id="btn-reset-cycle">
                            <i class="fa fa-undo mr-1" aria-hidden="true"></i>Réinitialiser
                        </button>
                        <a href="{{ route('cycles.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sortable.js pour le drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        const NIVEAUX_PRESETS = {
            maternelle: {
                nom: 'Maternelle',
                code: 'MATERNELLE',
                niveaux: ['Maternelle 1', 'Maternelle 2']
            },
            primaire: {
                nom: 'Primaire',
                code: 'PRIMAIRE',
                niveaux: ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2']
            },
            college: {
                nom: 'Collège',
                code: 'COLLEGE',
                niveaux: ['6ème', '5ème', '4ème', '3ème']
            },
            lycee: {
                nom: 'Lycée',
                code: 'LYCEE',
                niveaux: ['2nde', '1ère', 'Terminale']
            }
        };

        function addNiveau(valeur) {
            const container = document.getElementById('niveaux-container');
            const ordre = container.querySelectorAll('.niveau-row').length + 1;
            const newRow = document.createElement('div');
            newRow.className = 'input-group mb-2 niveau-row';
            newRow.innerHTML = `
                <div class="input-group-prepend">
                    <span class="input-group-text niveau-drag" style="cursor: grab;">
                        <i class="fa fa-grip-vertical text-muted mr-2" aria-hidden="true"></i>
                        <span class="niveau-ordre">${ordre}</span>
                    </span>
                </div>
                <input type="text" class="form-control" name="niveaux[]" placeholder="Ex : CM1">
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger btn-remove-niveau" onclick="removeNiveau(this)">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            if (valeur) {
                newRow.querySelector('input').value = valeur;
            }
        }

        function removeNiveau(button) {
            const row = button.closest('.niveau-row');
            const container = document.getElementById('niveaux-container');
            // Garder au moins une ligne
            if (container.querySelectorAll('.niveau-row').length > 1) {
                row.remove();
                updateOrdres();
            } else {
                // Vider le champ au lieu de supprimer la derniere ligne
                row.querySelector('input').value = '';
            }
        }

        function updateOrdres() {
            const container = document.getElementById('niveaux-container');
            const rows = container.querySelectorAll('.niveau-row');
            rows.forEach((row, index) => {
                row.querySelector('.niveau-ordre').textContent = index + 1;
            });
        }

        function setNiveaux(liste) {
            const container = document.getElementById('niveaux-container');
            container.innerHTML = '';
            liste.forEach((valeur) => addNiveau(valeur));
        }

        // Glisser-deposer pour reordonner les niveaux (poignee .niveau-drag).
        new Sortable(document.getElementById('niveaux-container'), {
            animation: 150,
            handle: '.niveau-drag',
            ghostClass: 'bg-light',
            onEnd: updateOrdres
        });

        const nomField = document.getElementById('nom');
        const codeField = document.getElementById('code');
        const DIACRITICS = /[\u0300-\u036f]/g;
        let codeLocked = codeField.value.trim() !== '';

        codeField.addEventListener('input', () => {
            codeLocked = true;
        });

        // Genere automatiquement le code a partir du nom, tant que l'utilisateur
        // n'a pas lui-meme modifie le champ code.
        nomField.addEventListener('input', () => {
            if (!codeLocked) {
                codeField.value = nomField.value
                    .toUpperCase()
                    .normalize('NFD')
                    .replace(DIACRITICS, '')
                    .trim();
            }
        });

        // Boutons de demarrage rapide : pre-remplissent nom, code et niveaux standards.
        document.getElementById('cycle-presets').addEventListener('click', (event) => {
            const button = event.target.closest('[data-preset]');
            if (!button) {
                return;
            }

            const preset = NIVEAUX_PRESETS[button.dataset.preset];
            if (!preset) {
                return;
            }

            nomField.value = preset.nom;
            codeField.value = preset.code;
            codeLocked = true;
            setNiveaux(preset.niveaux);
        });

        // Reinitialise entierement le formulaire : champs standards, boutons toggle
        // et liste des niveaux (non couverts par un simple type="reset").
        document.getElementById('btn-reset-cycle').addEventListener('click', () => {
            document.getElementById('form-cycle').reset();

            document.querySelectorAll('#form-cycle .btn-group-toggle .btn').forEach((label) => {
                label.classList.toggle('active', label.querySelector('input').checked);
            });

            codeLocked = false;
            setNiveaux(['']);
        });
    </script>
@endsection