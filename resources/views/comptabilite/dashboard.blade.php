@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-chart-line mr-2"></i>Tableau de bord Comptabilite
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Dashboard</a></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        {{-- Filtre par cycle --}}
        <div class="block block-rounded">
            <div class="block-content block-content-full">
                <form method="GET" action="{{ route('comptabilite.dashboard') }}" class="form-inline">
                    <label class="mr-2">Filtrer par cycle:</label>
                    <select name="cycle_id" class="form-control mr-2" onchange="this.form.submit()">
                        <option value="">Tous les cycles</option>
                        @foreach($cycles as $c)
                            <option value="{{ $c->id }}" {{ $cycle && $cycle->id == $c->id ? 'selected' : '' }}>
                                {{ $c->nom }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- Statistiques --}}
        <div class="row">
            <div class="col-md-6 col-xl-3">
                <div class="block block-rounded block-link-shadow">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <div class="font-size-h3 font-w600">{{ number_format($stats['total_eleves']) }}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">Eleves</div>
                        </div>
                        <div class="ml-3">
                            <i class="fa fa-users fa-3x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="block block-rounded block-link-shadow">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <div class="font-size-h3 font-w600">{{ number_format($stats['total_frais_attendu'], 0, ',', ' ') }}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">Frais attendus (FCFA)</div>
                        </div>
                        <div class="ml-3">
                            <i class="fa fa-money-bill fa-3x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="block block-rounded block-link-shadow">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <div class="font-size-h3 font-w600">{{ number_format($stats['total_paiements'], 0, ',', ' ') }}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">Total encaisse (FCFA)</div>
                        </div>
                        <div class="ml-3">
                            <i class="fa fa-check-circle fa-3x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="block block-rounded block-link-shadow">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <div class="font-size-h3 font-w600">{{ $stats['taux_recouvrement'] }}%</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">Taux recouvrement</div>
                        </div>
                        <div class="ml-3">
                            <i class="fa fa-percentage fa-3x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Paiements recents --}}
        <div class="row">
            <div class="col-lg-8">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Statut des eleves</h3>
                    </div>
                    <div class="block-content">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="py-3">
                                    <div class="font-size-h1 font-w700 text-success">{{ $stats['eleves_soldes'] }}</div>
                                    <div class="font-size-sm text-muted">Soldes</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="py-3">
                                    <div class="font-size-h1 font-w700 text-warning">{{ $stats['eleves_partiels'] }}</div>
                                    <div class="font-size-sm text-muted">Partiels</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="py-3">
                                    <div class="font-size-h1 font-w700 text-danger">{{ $stats['eleves_impayes'] }}</div>
                                    <div class="font-size-sm text-muted">Impayes</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Aujourd'hui</h3>
                    </div>
                    <div class="block-content text-center">
                        <div class="py-3">
                            <div class="font-size-h2 font-w700 text-primary">{{ number_format($stats['paiements_aujourd_hui'], 0, ',', ' ') }}</div>
                            <div class="font-size-sm text-muted">FCFA encaisses</div>
                        </div>
                        <hr>
                        <div class="py-3">
                            <div class="font-size-h4 font-w600">{{ number_format($stats['paiements_semaine'], 0, ',', ' ') }}</div>
                            <div class="font-size-sm text-muted">Cette semaine</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Derniers paiements --}}
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Derniers paiements</h3>
                <a href="{{ route('recus.index') }}" class="btn btn-sm btn-primary">Voir tous les recus</a>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Eleve</th>
                                <th>Type de frais</th>
                                <th class="text-center">Montant</th>
                                <th class="text-center">Recu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($derniersPaiements as $paiement)
                                <tr>
                                    <td>{{ $paiement->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('comptabilite.eleve.fiche', $paiement->eleve) }}">
                                            {{ $paiement->eleve->nom }} {{ $paiement->eleve->prenom }}
                                        </a>
                                    </td>
                                    <td>{{ $paiement->configurationFrais->typeFrais->nom ?? '-' }}</td>
                                    <td class="text-center font-w600">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                                    <td class="text-center">
                                        @if($paiement->recu)
                                            <a href="{{ route('recus.pdf', $paiement->recu) }}" class="btn btn-sm btn-info" target="_blank">
                                                <i class="fa fa-file-pdf"></i> {{ $paiement->recu->numero }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Aucun paiement</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
