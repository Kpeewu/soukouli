@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Mon Profil</h1>
            </div>
        </div>
    </div>

    <div class="content">
        @if ($notification = Session::get('notification'))
            <div class="alert alert-{{ $notification['type'] === 'success' ? 'success' : 'danger' }} alert-dismissable"
                role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <p class="mb-0">{{ $notification['message'] }}</p>
            </div>
        @endif

        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">{{ $user->username }}</h3>
            </div>
            <div class="block-content">
                <form action="{{ route('profile.update') }}" method="POST" id="form-profile">
                    @csrf
                    @method('PUT')

                    <div class="block block-rounded block-bordered mb-4 pb-4">
                        <div class="block-header block-header-default">
                            <h3 class="block-title"><i class="fa fa-address-card mr-2 text-primary"
                                    aria-hidden="true"></i>Identité</h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="civilite">Civilité</label>
                                        <select class="form-control @error('civilite') is-invalid @enderror"
                                            id="civilite" name="civilite">
                                            <option value="">—</option>
                                            <option value="M" @selected(old('civilite', $user->civilite) == 'M')>M.
                                            </option>
                                            <option value="Mme" @selected(old('civilite', $user->civilite) == 'Mme')>
                                                Mme</option>
                                        </select>
                                        @error('civilite')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nom">Nom</label>
                                        <input type="text" class="form-control @error('nom') is-invalid @enderror"
                                            id="nom" name="nom" value="{{ old('nom', $user->nom) }}">
                                        @error('nom')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="prenom">Prénom</label>
                                        <input type="text" class="form-control @error('prenom') is-invalid @enderror"
                                            id="prenom" name="prenom" value="{{ old('prenom', $user->prenom) }}">
                                        @error('prenom')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-0">
                                        <label for="telephone">Téléphone</label>
                                        <input type="text"
                                            class="form-control @error('telephone') is-invalid @enderror"
                                            id="telephone" name="telephone"
                                            value="{{ old('telephone', $user->telephone) }}">
                                        @error('telephone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded block-bordered mb-4 pb-4">
                        <div class="block-header block-header-default">
                            <h3 class="block-title"><i class="fa fa-lock mr-2 text-primary" aria-hidden="true"></i>
                                Mot de passe</h3>
                        </div>
                        <div class="block-content">
                            <small class="form-text text-muted mb-2">Laissez ces champs vides pour conserver le mot
                                de passe actuel.</small>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="current_password">Mot de passe actuel</label>
                                        <div class="input-group">
                                            <input type="password"
                                                class="form-control @error('current_password') is-invalid @enderror"
                                                id="current_password" name="current_password">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-alt-secondary js-toggle-password"
                                                    data-target="current_password" tabindex="-1">
                                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                        @error('current_password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="password">Nouveau mot de passe</label>
                                        <div class="input-group">
                                            <input type="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                id="password" name="password">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-alt-secondary js-toggle-password"
                                                    data-target="password" tabindex="-1">
                                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">8 caractères minimum.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="password_confirmation">Confirmer le mot de passe</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password_confirmation"
                                                name="password_confirmation">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-alt-secondary js-toggle-password"
                                                    data-target="password_confirmation" tabindex="-1">
                                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.js-toggle-password').forEach((button) => {
            button.addEventListener('click', () => {
                const field = document.getElementById(button.dataset.target);
                const icon = button.querySelector('i');
                const isHidden = field.type === 'password';
                field.type = isHidden ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !isHidden);
                icon.classList.toggle('fa-eye-slash', isHidden);
            });
        });
    </script>
@endsection
