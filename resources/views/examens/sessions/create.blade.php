@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Nouvelle Session d'Examen</h1>
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

        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Creer une Session d'Examen - {{ $anneeCourante->annee ?? 'Année non définie' }}</h3>
            </div>
            <div class="block-content">
                <form action="{{ route('sessions.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="examen_officiel_id">Examen Officiel</label>
                                <select class="form-control @error('examen_officiel_id') is-invalid @enderror" id="examen_officiel_id" name="examen_officiel_id" required>
                                    <option value="">Selectionner un examen</option>
                                    @foreach($examens as $examen)
                                        <option value="{{ $examen->id }}" {{ old('examen_officiel_id') == $examen->id ? 'selected' : '' }}>
                                            {{ $examen->nom }} ({{ $examen->cycle->nom }} - {{ $examen->niveau_requis }})
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
                                <label for="annee_scolaire_id">Année Scolaire</label>
                                <select class="form-control @error('annee_scolaire_id') is-invalid @enderror" id="annee_scolaire_id" name="annee_scolaire_id" required>
                                    @foreach($anneesScolaires as $annee)
                                        <option value="{{ $annee->id }}" {{ (old('annee_scolaire_id') == $annee->id || ($anneeCourante && $anneeCourante->id == $annee->id)) ? 'selected' : '' }}>
                                            {{ $annee->annee }}
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
                                <label for="date_debut">Date de debut (optionnel)</label>
                                <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut') }}">
                                @error('date_debut')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_fin">Date de fin (optionnel)</label>
                                <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin') }}">
                                @error('date_fin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Creer la session</button>
                        <a href="{{ route('sessions.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
