@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Recrutez un nouvel enseignant
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">CPL Mon Avenir</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('professeur.index') }}">Enseignants</a>
                        </li>
                        <li class="breadcrumb-item">Recrutement</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>


    <div class="content">

        <!---- Copiez collez (il s'agit des alertes pour le retour d'actions)----->

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

        <!---- Copiez collez ----->

        <div class="block block-rounded">

            <div class="block-header">
                <h3 class="block-title">Entrez les informations du nouvel enseignant</h3>
            </div>

            <div class="block-content pb-4">
                <form action="{{ route('professeur.store') }}" method="post" id="form-professeur">
                    @csrf

                    <div class="block block-rounded block-bordered mb-4 pb-4">
                        <div class="block-header block-header-default">
                            <h3 class="block-title"><i class="fa fa-id-card mr-2 text-primary" aria-hidden="true"></i>Identité</h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nom">Nom</label>
                                        <input type="text" id="nom" name="nom"
                                            class="form-control form-control-alt @error('nom') is-invalid @enderror"
                                            value="{{ old('nom') }}" required autofocus>
                                        @error('nom')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="prenom">Prénoms</label>
                                        <input type="text" id="prenom" name="prenom"
                                            class="form-control form-control-alt @error('prenom') is-invalid @enderror"
                                            value="{{ old('prenom') }}" required>
                                        @error('prenom')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="sexe">Sexe</label>
                                        <select id="sexe" name="sexe"
                                            class="js-select2 form-control form-control-alt @error('sexe') is-invalid @enderror"
                                            style="width: 100%;" data-placeholder="Sélectionnez" required>
                                            <option></option>
                                            <option value="M" @selected(old('sexe') === 'M')>Masculin</option>
                                            <option value="F" @selected(old('sexe') === 'F')>Féminin</option>
                                        </select>
                                        @error('sexe')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label for="contact">Contact</label>
                                        <input type="tel" id="contact" name="contact"
                                            class="form-control form-control-alt @error('contact') is-invalid @enderror"
                                            value="{{ old('contact') }}" placeholder="90 00 00 00">
                                        @error('contact')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                @if ($cycles->count() > 1)
                                    <div class="col-md-4 mt-3">
                                        <div class="form-group mb-0">
                                            <label for="cycle_id">Cycle</label>
                                            <select id="cycle_id" name="cycle_id"
                                                class="js-select2 form-control form-control-alt @error('cycle_id') is-invalid @enderror"
                                                style="width: 100%;" data-placeholder="Sélectionnez un cycle" required>
                                                <option></option>
                                                @foreach ($cycles as $cycle)
                                                    <option value="{{ $cycle->id }}" @selected(old('cycle_id') == $cycle->id)>
                                                        {{ $cycle->nom }}</option>
                                                @endforeach
                                            </select>
                                            @error('cycle_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="block block-rounded block-bordered mb-4 pb-4">
                        <div class="block-header block-header-default">
                            <h3 class="block-title"><i class="fa fa-chalkboard-teacher mr-2 text-primary"
                                    aria-hidden="true"></i>Affectation</h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="classe_ids">Classes tutorées <span
                                                class="text-muted font-size-sm">(optionnel)</span></label>
                                        <select id="classe_ids" name="classe_ids[]" multiple="multiple"
                                            class="js-select2 form-control form-control-alt @error('classe_ids') is-invalid @enderror"
                                            style="width: 100%;" data-placeholder="Aucune classe pour le moment">
                                            @foreach ($classesGroupees as $groupe)
                                                <optgroup label="{{ $groupe['label'] }}">
                                                    @foreach ($groupe['classes'] as $classe)
                                                        <option value="{{ $classe->id }}"
                                                            @selected(in_array($classe->id, old('classe_ids', [])))>
                                                            {{ $classe->nom }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        @error('classe_ids')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        @error('classe_ids.*')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">L'enseignant sera défini comme titulaire de
                                            chaque classe choisie. Les classes déjà tutorées n'apparaissent pas dans
                                            cette liste.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center mb-0">
                        <button class="btn btn-success" type="submit">
                            <i class="fa fa-user-plus mr-1" aria-hidden="true"></i>Recruter
                        </button>
                        <a href="{{ route('professeur.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
