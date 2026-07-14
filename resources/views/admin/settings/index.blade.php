@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-cog mr-2"></i>Paramètres de l'etablissement
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Paramètres</a></li>
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

        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                {{-- Colonne gauche: Informations generales et Contact --}}
                <div class="col-lg-8">
                    {{-- Informations generales --}}
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">
                                <i class="fa fa-school mr-2"></i>Informations générales
                            </h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="school_name">Nom de l'établissement <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('school_name') is-invalid @enderror"
                                               id="school_name" name="school_name"
                                               value="{{ old('school_name', $settings['school_name'] ?? '') }}"
                                               placeholder="Ex: Soukouli" required>
                                        @error('school_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Nom court utilisé dans l'interface</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="school_type">Type d'établissement <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('school_type') is-invalid @enderror"
                                               id="school_type" name="school_type"
                                               value="{{ old('school_type', $settings['school_type'] ?? '') }}"
                                               placeholder="Ex: COMPLEXE SCOLAIRE" required>
                                        @error('school_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="school_full_name">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('school_full_name') is-invalid @enderror"
                                       id="school_full_name" name="school_full_name"
                                       value="{{ old('school_full_name', $settings['school_full_name'] ?? '') }}"
                                       placeholder="Ex: Complexe Scolaire Privé Soukouli" required>
                                @error('school_full_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Nom complet utilisé sur les documents officiels</small>
                            </div>

                            <div class="form-group">
                                <label for="school_motto">Devise / Slogan</label>
                                <input type="text" class="form-control @error('school_motto') is-invalid @enderror"
                                       id="school_motto" name="school_motto"
                                       value="{{ old('school_motto', $settings['school_motto'] ?? '') }}"
                                       placeholder="Ex: Travail - Discipline - Succès">
                                @error('school_motto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Contact --}}
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">
                                <i class="fa fa-map-marker-alt mr-2"></i>Coordonnees
                            </h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="school_bp">Boite postale</label>
                                        <input type="text" class="form-control @error('school_bp') is-invalid @enderror"
                                               id="school_bp" name="school_bp"
                                               value="{{ old('school_bp', $settings['school_bp'] ?? '') }}"
                                               placeholder="Ex: BP: 68">
                                        @error('school_bp')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="school_city">Ville</label>
                                        <input type="text" class="form-control @error('school_city') is-invalid @enderror"
                                               id="school_city" name="school_city"
                                               value="{{ old('school_city', $settings['school_city'] ?? '') }}"
                                               placeholder="Ex: SOKODE">
                                        @error('school_city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="school_country">Pays</label>
                                        <input type="text" class="form-control @error('school_country') is-invalid @enderror"
                                               id="school_country" name="school_country"
                                               value="{{ old('school_country', $settings['school_country'] ?? '') }}"
                                               placeholder="Ex: TOGO">
                                        @error('school_country')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="school_address">Adresse complete</label>
                                <textarea class="form-control @error('school_address') is-invalid @enderror"
                                          id="school_address" name="school_address" rows="2"
                                          placeholder="Adresse physique de l'etablissement">{{ old('school_address', $settings['school_address'] ?? '') }}</textarea>
                                @error('school_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="school_phone">Telephone</label>
                                        <input type="tel" class="form-control @error('school_phone') is-invalid @enderror"
                                               id="school_phone" name="school_phone"
                                               value="{{ old('school_phone', $settings['school_phone'] ?? '') }}"
                                               placeholder="Ex: +228 90 00 00 00">
                                        @error('school_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="school_email">Email</label>
                                        <input type="email" class="form-control @error('school_email') is-invalid @enderror"
                                               id="school_email" name="school_email"
                                               value="{{ old('school_email', $settings['school_email'] ?? '') }}"
                                               placeholder="Ex: contact@soukouli.tg">
                                        @error('school_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Colonne droite: Affichage (logo et images) --}}
                <div class="col-lg-4">
                    {{-- Logo --}}
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">
                                <i class="fa fa-image mr-2"></i>Logo
                            </h3>
                        </div>
                        <div class="block-content text-center">
                            <div class="mb-3">
                                <img src="{{ $settings['school_logo_url'] ?? asset('assets/images/logo.png') }}"
                                     alt="Logo actuel" class="img-fluid" style="max-height: 150px;">
                            </div>
                            <div class="form-group">
                                <div class="js-dropzone rounded p-3" data-input="school_logo"
                                     style="border: 2px dashed #d5dadf;">
                                    <label for="school_logo" class="btn btn-outline-primary btn-block mb-1">
                                        <i class="fa fa-upload mr-1"></i> Changer le logo
                                    </label>
                                    <input type="file" class="d-none @error('school_logo') is-invalid @enderror"
                                           id="school_logo" name="school_logo"
                                           accept="image/png,image/jpeg,image/gif,image/svg+xml"
                                           data-max-size="2097152" data-preview="logo-preview"
                                           data-preview-extra="preview-logo">
                                    <small class="text-muted d-block">ou glissez-déposez une image ici</small>
                                </div>
                                @error('school_logo')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="d-flex align-items-center justify-content-between mt-2 d-none"
                                     id="school_logo-info">
                                    <small class="text-truncate mr-2" id="school_logo-filename"></small>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                            onclick="clearFileInput('school_logo')">
                                        <i class="fa fa-times mr-1"></i>Retirer
                                    </button>
                                </div>
                                <div class="text-danger small mt-1 d-none" id="school_logo-error"></div>
                                <small class="form-text text-muted">PNG, JPG, GIF ou SVG. Max 2MB. Redimensionne automatiquement a 300x300px max.</small>
                            </div>
                            <img id="logo-preview" src="#" alt="Apercu" class="img-fluid d-none mt-2" style="max-height: 100px;">
                        </div>
                    </div>

                    {{-- Image de fond connexion --}}
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">
                                <i class="fa fa-sign-in-alt mr-2"></i>Page de connexion
                            </h3>
                        </div>
                        <div class="block-content text-center">
                            <div class="mb-3">
                                <img src="{{ $settings['login_background_url'] ?? asset('assets/images/background.png') }}"
                                     alt="Image de fond actuelle" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                            <div class="form-group">
                                <div class="js-dropzone rounded p-3" data-input="login_background"
                                     style="border: 2px dashed #d5dadf;">
                                    <label for="login_background" class="btn btn-outline-primary btn-block mb-1">
                                        <i class="fa fa-upload mr-1"></i> Changer l'image de fond
                                    </label>
                                    <input type="file" class="d-none @error('login_background') is-invalid @enderror"
                                           id="login_background" name="login_background"
                                           accept="image/png,image/jpeg" data-max-size="4194304"
                                           data-preview="bg-preview">
                                    <small class="text-muted d-block">ou glissez-déposez une image ici</small>
                                </div>
                                @error('login_background')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="d-flex align-items-center justify-content-between mt-2 d-none"
                                     id="login_background-info">
                                    <small class="text-truncate mr-2" id="login_background-filename"></small>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                            onclick="clearFileInput('login_background')">
                                        <i class="fa fa-times mr-1"></i>Retirer
                                    </button>
                                </div>
                                <div class="text-danger small mt-1 d-none" id="login_background-error"></div>
                                <small class="form-text text-muted">PNG ou JPG. Max 4MB. Redimensionne automatiquement a 1920x1080px max.</small>
                            </div>
                            <img id="bg-preview" src="#" alt="Apercu" class="img-fluid d-none mt-2 rounded" style="max-height: 100px;">
                        </div>
                    </div>

                    {{-- Apercu --}}
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">
                                <i class="fa fa-eye mr-2"></i>Apercu
                            </h3>
                        </div>
                        <div class="block-content pb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <img id="preview-logo"
                                     src="{{ $settings['school_logo_url'] ?? asset('assets/images/logo2.png') }}"
                                     alt="Logo" style="max-height: 60px;" class="mb-2">
                                <h5 class="mb-1" id="preview-name">{{ $settings['school_name'] ?? 'Mon Avenir' }}</h5>
                                <small class="text-muted" id="preview-motto">{{ $settings['school_motto'] ?? '' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Boutons d'action --}}
            <div class="block block-rounded">
                <div class="block-content block-content-full">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-danger"
                                onclick="if(confirm('Reinitialiser tous les parametres aux valeurs par defaut ?')) { document.getElementById('reset-form').submit(); }">
                            <i class="fa fa-undo mr-1"></i> Reinitialiser
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save mr-1"></i> Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <form id="reset-form" action="{{ route('settings.reset') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>

    <script>
        function formatFileSize(bytes) {
            return (bytes / 1024).toFixed(0) + ' Ko';
        }

        function clearFileInput(inputId) {
            const input = document.getElementById(inputId);
            input.value = '';
            handleFileChange(input);
        }

        function handleFileChange(input) {
            const file = input.files && input.files[0];
            const info = document.getElementById(input.id + '-info');
            const filename = document.getElementById(input.id + '-filename');
            const errorBox = document.getElementById(input.id + '-error');
            const preview = document.getElementById(input.dataset.preview);
            const previewExtra = input.dataset.previewExtra ? document.getElementById(input.dataset.previewExtra) :
                null;

            if (!file) {
                errorBox.classList.add('d-none');
                info.classList.add('d-none');
                preview.classList.add('d-none');
                return;
            }

            const maxSize = parseInt(input.dataset.maxSize, 10);
            if (maxSize && file.size > maxSize) {
                errorBox.textContent = 'Fichier trop volumineux (' + formatFileSize(file.size) + '). Maximum : ' +
                    formatFileSize(maxSize) + '.';
                errorBox.classList.remove('d-none');
                info.classList.add('d-none');
                input.value = '';
                return;
            }

            errorBox.classList.add('d-none');
            info.classList.remove('d-none');
            filename.textContent = file.name + ' (' + formatFileSize(file.size) + ')';

            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                if (previewExtra) {
                    previewExtra.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        }

        document.querySelectorAll('.js-dropzone').forEach(function(zone) {
            const input = document.getElementById(zone.dataset.input);

            input.addEventListener('change', function() {
                handleFileChange(input);
            });

            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                zone.classList.add('bg-body-light');
            });

            zone.addEventListener('dragleave', function() {
                zone.classList.remove('bg-body-light');
            });

            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                zone.classList.remove('bg-body-light');
                if (e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    handleFileChange(input);
                }
            });
        });

        // Mise a jour de l'apercu en temps reel
        document.getElementById('school_name').addEventListener('input', function() {
            document.getElementById('preview-name').textContent = this.value || 'Mon Avenir';
        });

        document.getElementById('school_motto').addEventListener('input', function() {
            document.getElementById('preview-motto').textContent = this.value;
        });
    </script>
@endsection
