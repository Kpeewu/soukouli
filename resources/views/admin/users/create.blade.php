@extends('layouts.dashboard')

@php
    $roleLabels = [
        'admin' => 'Administrateur',
        'professeur' => 'Professeur',
        'directeur_general' => 'Directeur — Tous les cycles',
        'comptable_general' => 'Comptable — Tous les cycles',
        'secretaire_general' => 'Secrétaire — Tous les cycles',
    ];

    $roleGroups = [
        'Administration' => ['admin'],
        'Enseignement' => ['professeur'],
        'Direction de cycle' => ['directeur_general'],
        'Comptabilité' => ['comptable_general'],
        'Secrétariat' => ['secretaire_general'],
    ];

    // Genere dynamiquement les roles directeur/comptable/secretaire pour chaque cycle
    // (y compris les cycles crees apres le lancement, ex: BTS), au lieu de coder en dur
    // seulement les 4 cycles de base.
    foreach ($cycles as $cycle) {
        $suffix = strtolower($cycle->code);

        $roleLabels["directeur_{$suffix}"] = "Directeur — {$cycle->nom}";
        $roleGroups['Direction de cycle'][] = "directeur_{$suffix}";

        $roleLabels["comptable_{$suffix}"] = "Comptable — {$cycle->nom}";
        $roleGroups['Comptabilité'][] = "comptable_{$suffix}";

        $roleLabels["secretaire_{$suffix}"] = "Secrétaire — {$cycle->nom}";
        $roleGroups['Secrétariat'][] = "secretaire_{$suffix}";
    }

    $categorized = collect($roleGroups)->flatten();
    $roleGroups['Autres'] = $roles->pluck('name')->diff($categorized)->values()->all();
@endphp

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Ajouter un Utilisateur</h1>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Nouvel Utilisateur</h3>
            </div>
            <div class="block-content">
                <form action="{{ route('users.store') }}" method="POST" id="form-user">
                    @csrf

                    <div class="block block-rounded block-bordered mb-4 pb-4">
                        <div class="block-header block-header-default">
                            <h3 class="block-title"><i class="fa fa-id-badge mr-2 text-primary"
                                    aria-hidden="true"></i>Compte</h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Nom d'utilisateur</label>
                                        <input type="text"
                                            class="form-control @error('username') is-invalid @enderror"
                                            id="username" name="username" value="{{ old('username') }}" required>
                                        @error('username')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="role"><i class="fa fa-user-tag mr-1" aria-hidden="true"></i>Rôle</label>
                                        <select class="js-select2 form-control @error('role') is-invalid @enderror"
                                            id="role" name="role" style="width: 100%;"
                                            data-placeholder="Sélectionnez un rôle..." required>
                                            <option></option>
                                            @foreach ($roleGroups as $groupLabel => $roleNames)
                                                @php $groupRoles = $roles->whereIn('name', $roleNames); @endphp
                                                @if ($groupRoles->isNotEmpty())
                                                    <optgroup label="{{ $groupLabel }}">
                                                        @foreach ($groupRoles as $role)
                                                            <option value="{{ $role->name }}"
                                                                @selected(old('role') == $role->name)>
                                                                {{ $roleLabels[$role->name] ?? $role->name }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('role')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                            <option value="M" @selected(old('civilite') == 'M')>M.</option>
                                            <option value="Mme" @selected(old('civilite') == 'Mme')>Mme</option>
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
                                            id="nom" name="nom" value="{{ old('nom') }}">
                                        @error('nom')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="prenom">Prénom</label>
                                        <input type="text" class="form-control @error('prenom') is-invalid @enderror"
                                            id="prenom" name="prenom" value="{{ old('prenom') }}">
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
                                            id="telephone" name="telephone" value="{{ old('telephone') }}">
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
                            <div class="block-options">
                                <button type="button" class="btn btn-sm btn-alt-primary" id="btn-generate-password">
                                    <i class="fa fa-magic mr-1" aria-hidden="true"></i>Générer un mot de passe
                                </button>
                            </div>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Mot de passe</label>
                                        <div class="input-group">
                                            <input type="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                id="password" name="password" required>
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
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="password_confirmation">Confirmer le mot de passe</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password_confirmation"
                                                name="password_confirmation" required>
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
                        <button type="submit" class="btn btn-success">Créer l'utilisateur</button>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">Annuler</a>
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

        document.getElementById('btn-generate-password').addEventListener('click', () => {
            const charset = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
            const values = new Uint32Array(12);
            crypto.getRandomValues(values);
            const generated = Array.from(values, (n) => charset[n % charset.length]).join('');

            const passwordField = document.getElementById('password');
            const confirmField = document.getElementById('password_confirmation');
            passwordField.value = generated;
            confirmField.value = generated;
            passwordField.type = 'text';
            confirmField.type = 'text';

            document.querySelectorAll('.js-toggle-password').forEach((button) => {
                button.querySelector('i').classList.remove('fa-eye');
                button.querySelector('i').classList.add('fa-eye-slash');
            });
        });
    </script>
@endsection
