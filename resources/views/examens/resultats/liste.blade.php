@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Resultats {{ $session->examenOfficiel->nom ?? 'Examen' }} - {{ $session->anneeScolaire->annee ?? '' }}
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Examens</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('sessions.index') }}">Sessions</a></li>
                        <li class="breadcrumb-item">Liste des resultats</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="block block-rounded text-center">
                    <div class="block-content py-4">
                        <div class="font-size-h1 font-w700 text-primary">{{ $total }}</div>
                        <div class="font-size-sm text-muted">Total candidats</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded text-center">
                    <div class="block-content py-4">
                        <div class="font-size-h1 font-w700 text-success">{{ $admis }}</div>
                        <div class="font-size-sm text-muted">Admis</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded text-center">
                    <div class="block-content py-4">
                        <div class="font-size-h1 font-w700 text-danger">{{ $ajournes }}</div>
                        <div class="font-size-sm text-muted">Ajournes</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded text-center">
                    <div class="block-content py-4">
                        <div class="font-size-h1 font-w700 text-info">{{ $tauxReussite }}%</div>
                        <div class="font-size-sm text-muted">Taux de reussite</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Liste des resultats</h3>
                <div>
                    <button type="button" class="btn btn-secondary" onclick="window.print()">
                        <i class="fa fa-print mr-1"></i> Imprimer
                    </button>
                    @unless(auth()->user()->isDirecteur())
                        <a href="{{ route('resultats.saisie', $session) }}" class="btn btn-warning">
                            <i class="fa fa-edit mr-1"></i> Modifier
                        </a>
                    @endunless
                    <a href="{{ route('sessions.show', $session) }}" class="btn btn-info">
                        <i class="fa fa-arrow-left mr-1"></i> Retour
                    </a>
                </div>
            </div>

            <div class="block-content">
                <ul class="nav nav-tabs nav-tabs-alt" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tous-tab" data-toggle="tab" href="#tous" role="tab">
                            Tous <span class="badge badge-secondary">{{ $total }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="admis-tab" data-toggle="tab" href="#admis-list" role="tab">
                            Admis <span class="badge badge-success">{{ $admis }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="ajournes-tab" data-toggle="tab" href="#ajournes-list" role="tab">
                            Ajournes <span class="badge badge-danger">{{ $ajournes }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tous" role="tabpanel">
                        @include('examens.resultats._table', ['inscriptions' => $inscriptions, 'showStatut' => true])
                    </div>
                    <div class="tab-pane fade" id="admis-list" role="tabpanel">
                        @include('examens.resultats._table', ['inscriptions' => $inscriptions->where('statut', 'admis'), 'showStatut' => false])
                    </div>
                    <div class="tab-pane fade" id="ajournes-list" role="tabpanel">
                        @include('examens.resultats._table', ['inscriptions' => $inscriptions->where('statut', 'ajourne'), 'showStatut' => false])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .bg-body-light, .block-header .btn, .nav-tabs { display: none !important; }
            .block { border: none !important; box-shadow: none !important; }
            .tab-pane { display: block !important; opacity: 1 !important; }
        }
    </style>
@endsection
