@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Inscrire des Eleves - {{ $session->examenOfficiel->nom ?? 'Examen' }}
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Examens</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('sessions.index') }}">Sessions</a></li>
                        <li class="breadcrumb-item">Inscription</li>
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

        <div class="row">
            <div class="col-lg-4">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">Informations de la session</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-borderless">
                            <tr>
                                <td class="font-w600">Examen:</td>
                                <td>{{ $session->examenOfficiel->nom ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="font-w600">Cycle:</td>
                                <td>{{ $session->examenOfficiel->cycle->nom ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="font-w600">Niveau requis:</td>
                                <td><span class="badge badge-info">{{ $session->examenOfficiel->niveau_requis ?? '-' }}</span></td>
                            </tr>
                            <tr>
                                <td class="font-w600">Annee:</td>
                                <td>{{ $session->anneeScolaire->annee ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="font-w600">Deja inscrits:</td>
                                <td><span class="badge badge-primary">{{ $session->inscriptions->count() }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">Selectionner les eleves a inscrire</h3>
                    </div>
                    <div class="block-content">
                        <form action="{{ route('inscriptions.store', $session) }}" method="POST">
                            @csrf

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="centre_examen">Centre d'examen (commun)</label>
                                        <input type="text" class="form-control @error('centre_examen') is-invalid @enderror" id="centre_examen" name="centre_examen" value="{{ old('centre_examen') }}" placeholder="Ex: CEG Tokoin, Lycee de Lome...">
                                        @error('centre_examen')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="numero_serie">Numero de serie (debut)</label>
                                        <input type="text" class="form-control @error('numero_serie') is-invalid @enderror" id="numero_serie" name="numero_serie" value="{{ old('numero_serie') }}" placeholder="Ex: 001, A001...">
                                        @error('numero_serie')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Les numeros seront incrementes automatiquement</small>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center" style="width: 50px;">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="select-all">
                                                    <label class="custom-control-label" for="select-all"></label>
                                                </div>
                                            </th>
                                            <th>Nom & Prenom</th>
                                            <th class="text-center">Matricule</th>
                                            <th class="text-center">Classe</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($elevesEligibles as $eleve)
                                            <tr>
                                                <td class="text-center">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input eleve-checkbox" id="eleve-{{ $eleve->id }}" name="eleves[]" value="{{ $eleve->id }}" {{ in_array($eleve->id, old('eleves', [])) ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="eleve-{{ $eleve->id }}"></label>
                                                    </div>
                                                </td>
                                                <td class="font-w600">{{ $eleve->nom }} {{ $eleve->prenom }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-secondary">{{ $eleve->matricule }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @foreach($eleve->classes as $classe)
                                                        {{ $classe->nom }}{{ !$loop->last ? ', ' : '' }}
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td class="text-center p-3" colspan="4">
                                                    Aucun eleve eligible pour cet examen ou tous les eleves sont deja inscrits
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @error('eleves')
                                <div class="alert alert-danger mt-2">{{ $message }}</div>
                            @enderror

                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-success" {{ $elevesEligibles->isEmpty() ? 'disabled' : '' }}>
                                    <i class="fa fa-user-plus mr-1"></i> Inscrire les eleves selectionnes
                                </button>
                                <a href="{{ route('sessions.show', $session) }}" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.eleve-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = this.checked;
            }.bind(this));
        });
    </script>
@endsection
