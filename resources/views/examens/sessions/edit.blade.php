@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Modifier la Session d'Examen</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Examens</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('sessions.index') }}">Sessions</a></li>
                        <li class="breadcrumb-item">Modifier</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Modifier: {{ $session->examenOfficiel->nom ?? 'Session' }}</h3>
            </div>
            <div class="block-content">
                <form action="{{ route('sessions.update', $session) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="examen_officiel_id">Examen officiel</label>
                                <select class="form-control @error('examen_officiel_id') is-invalid @enderror" id="examen_officiel_id" name="examen_officiel_id" required>
                                    <option value="">Selectionner un examen</option>
                                    @foreach($examens as $examen)
                                        <option value="{{ $examen->id }}" {{ old('examen_officiel_id', $session->examen_officiel_id) == $examen->id ? 'selected' : '' }}>
                                            {{ $examen->nom }} ({{ $examen->cycle->nom ?? '' }} - {{ $examen->niveau_requis }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('examen_officiel_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="annee_scolaire_id">Annee scolaire</label>
                                <select class="form-control @error('annee_scolaire_id') is-invalid @enderror" id="annee_scolaire_id" name="annee_scolaire_id" required>
                                    @foreach($anneesScolaires as $annee)
                                        <option value="{{ $annee->id }}" {{ old('annee_scolaire_id', $session->annee_scolaire_id) == $annee->id ? 'selected' : '' }}>
                                            {{ $annee->annee }} {{ $annee->courant ? '(Courante)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('annee_scolaire_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_debut">Date de debut</label>
                                <input type="text" class="js-flatpickr form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut', $session->date_debut ? $session->date_debut->format('d/m/Y') : '') }}" placeholder="JJ/MM/AAAA" data-date-format="d/m/Y">
                                @error('date_debut')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_fin">Date de fin</label>
                                <input type="text" class="js-flatpickr form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin', $session->date_fin ? $session->date_fin->format('d/m/Y') : '') }}" placeholder="JJ/MM/AAAA" data-date-format="d/m/Y">
                                @error('date_fin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="statut">Statut</label>
                                <select class="form-control @error('statut') is-invalid @enderror" id="statut" name="statut">
                                    <option value="programme" {{ old('statut', $session->statut) == 'programme' ? 'selected' : '' }}>Programme</option>
                                    <option value="en_cours" {{ old('statut', $session->statut) == 'en_cours' ? 'selected' : '' }}>En cours</option>
                                    <option value="termine" {{ old('statut', $session->statut) == 'termine' ? 'selected' : '' }}>Termine</option>
                                </select>
                                @error('statut')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Mettre a jour</button>
                        <a href="{{ route('sessions.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
