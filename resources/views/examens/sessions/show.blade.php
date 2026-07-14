@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Session {{ $session->examenOfficiel->nom }} - {{ $session->anneeScolaire->annee }}</h1>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="row">
            <div class="col-md-4">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">Informations</h3>
                    </div>
                    <div class="block-content">
                        <p><strong>Examen:</strong> {{ $session->examenOfficiel->nom }}</p>
                        <p><strong>Cycle:</strong> {{ $session->examenOfficiel->cycle->nom }}</p>
                        <p><strong>Niveau requis:</strong> {{ $session->examenOfficiel->niveau_requis }}</p>
                        <p><strong>Annee:</strong> {{ $session->anneeScolaire->annee }}</p>
                        <p><strong>Dates:</strong>
                            @if($session->date_debut)
                                {{ $session->date_debut->format('d/m/Y') }}
                                @if($session->date_fin) - {{ $session->date_fin->format('d/m/Y') }} @endif
                            @else
                                Non definies
                            @endif
                        </p>
                        <p><strong>Statut:</strong>
                            @php
                                $badgeClass = match($session->statut) {
                                    'programme' => 'warning',
                                    'en_cours' => 'primary',
                                    'termine' => 'success',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge badge-{{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $session->statut)) }}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">Statistiques</h3>
                    </div>
                    <div class="block-content text-center">
                        <h2 class="text-primary">{{ $session->inscriptions->count() }}</h2>
                        <p>Candidats inscrits</p>
                        @if($session->statut === 'termine')
                            <h2 class="text-success">{{ $session->inscriptions->where('statut', 'admis')->count() }}</h2>
                            <p>Admis</p>
                            <h2 class="text-danger">{{ $session->inscriptions->where('statut', 'ajourne')->count() }}</h2>
                            <p>Ajournes</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="block block-rounded pb-4">
                    <div class="block-header">
                        <h3 class="block-title">Actions</h3>
                    </div>
                    <div class="block-content">
                        @unless(auth()->user()->isDirecteur())
                            <a href="{{ route('inscriptions.create', $session) }}" class="btn btn-success btn-block mb-2">
                                <i class="fa fa-user-plus"></i> Inscrire des eleves
                            </a>
                        @endunless
                        <a href="{{ route('inscriptions.index', $session) }}" class="btn btn-primary btn-block mb-2">
                            <i class="fa fa-users"></i> Liste des inscrits
                        </a>
                        @unless(auth()->user()->isDirecteur())
                            <a href="{{ route('resultats.saisie', $session) }}" class="btn btn-info btn-block mb-2">
                                <i class="fa fa-check-circle"></i> Saisir les resultats
                            </a>
                        @endunless
                        <a href="{{ route('resultats.liste', $session) }}" class="btn btn-primary btn-block mb-2">
                            <i class="fa fa-poll"></i> Voir les resultats
                        </a>
                        <a href="{{ route('sessions.index') }}" class="btn btn-secondary btn-block">
                            <i class="fa fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
