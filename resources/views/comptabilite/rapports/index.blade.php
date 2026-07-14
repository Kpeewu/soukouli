@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-chart-bar mr-2"></i>Rapports financiers
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Rapports</a></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        {{-- Filtres --}}
        <div class="block block-rounded">
            <div class="block-content block-content-full">
                <form method="GET" action="{{ route('comptabilite.rapports') }}" class="form-inline">
                    <div class="mr-3">
                        <label class="mr-2">Cycle:</label>
                        <select name="cycle_id" class="form-control" onchange="this.form.submit()">
                            <option value="">Tous les cycles</option>
                            @foreach($cycles as $c)
                                <option value="{{ $c->id }}" {{ $cycle && $cycle->id == $c->id ? 'selected' : '' }}>
                                    {{ $c->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('comptabilite.retard') }}" class="btn btn-warning ml-auto">
                        <i class="fa fa-exclamation-triangle mr-2"></i>Eleves en retard
                    </a>
                </form>
            </div>
        </div>

        @if($rapport)
            {{-- Resume global --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full text-center">
                            <div class="font-size-h2 font-w700 text-primary">
                                {{ number_format($rapport['total_attendu'], 0, ',', ' ') }}
                            </div>
                            <div class="text-muted">Total attendu (FCFA)</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full text-center">
                            <div class="font-size-h2 font-w700 text-success">
                                {{ number_format($rapport['total_recu'], 0, ',', ' ') }}
                            </div>
                            <div class="text-muted">Total encaisse (FCFA)</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full text-center">
                            <div class="font-size-h2 font-w700 text-danger">
                                {{ number_format($rapport['solde_restant'], 0, ',', ' ') }}
                            </div>
                            <div class="text-muted">Reste a recouvrer (FCFA)</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full text-center">
                            <div class="font-size-h2 font-w700 text-info">
                                {{ $rapport['taux_recouvrement'] }}%
                            </div>
                            <div class="text-muted">Taux de recouvrement</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Par type de frais --}}
                <div class="col-lg-6">
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Par type de frais</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th class="text-right">Attendu</th>
                                        <th class="text-right">Recu</th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rapport['par_type_frais'] as $item)
                                        <tr>
                                            <td>{{ $item['type_frais']->nom }}</td>
                                            <td class="text-right">{{ number_format($item['montant_attendu'], 0, ',', ' ') }}</td>
                                            <td class="text-right text-success">{{ number_format($item['montant_recu'], 0, ',', ' ') }}</td>
                                            <td class="text-right">
                                                @php
                                                    $pct = $item['montant_attendu'] > 0 ? round(($item['montant_recu'] / $item['montant_attendu']) * 100) : 0;
                                                @endphp
                                                <span class="badge {{ $pct >= 75 ? 'badge-success' : ($pct >= 50 ? 'badge-warning' : 'badge-danger') }}">
                                                    {{ $pct }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Par cycle --}}
                <div class="col-lg-6">
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Par cycle</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Cycle</th>
                                        <th class="text-right">Attendu</th>
                                        <th class="text-right">Recu</th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rapport['par_cycle'] as $item)
                                        <tr>
                                            <td>{{ $item['cycle']->nom }}</td>
                                            <td class="text-right">{{ number_format($item['montant_attendu'], 0, ',', ' ') }}</td>
                                            <td class="text-right text-success">{{ number_format($item['montant_recu'], 0, ',', ' ') }}</td>
                                            <td class="text-right">
                                                @php
                                                    $pct = $item['montant_attendu'] > 0 ? round(($item['montant_recu'] / $item['montant_attendu']) * 100) : 0;
                                                @endphp
                                                <span class="badge {{ $pct >= 75 ? 'badge-success' : ($pct >= 50 ? 'badge-warning' : 'badge-danger') }}">
                                                    {{ $pct }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info">
                Aucune annee scolaire courante definie.
            </div>
        @endif
    </div>
@endsection
