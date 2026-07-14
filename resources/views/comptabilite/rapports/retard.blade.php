@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-exclamation-triangle text-warning mr-2"></i>Eleves en retard de paiement
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('comptabilite.rapports') }}">Rapports</a></li>
                        <li class="breadcrumb-item">En retard</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        {{-- Filtres --}}
        <div class="block block-rounded">
            <div class="block-content block-content-full">
                <form method="GET" action="{{ route('comptabilite.retard') }}" class="form-inline">
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
                </form>
            </div>
        </div>

        {{-- Liste --}}
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    Eleves avec solde impaye ({{ $elevesEnRetard->count() }})
                </h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom & Prenom</th>
                                <th>Classe</th>
                                <th class="text-center">Solde du</th>
                                <th class="text-center">Statut</th>
                                <th class="text-center" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($elevesEnRetard as $item)
                                <tr>
                                    <td>{{ $item['eleve']->matricule }}</td>
                                    <td><strong>{{ $item['eleve']->nom }}</strong> {{ $item['eleve']->prenom }}</td>
                                    <td>{{ $item['classe'] ? $item['classe']->nom : '-' }}</td>
                                    <td class="text-center font-w700 text-danger">
                                        {{ number_format($item['solde'], 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="text-center">
                                        @if($item['statut'] == 'partiel')
                                            <span class="badge badge-warning">Partiel</span>
                                        @else
                                            <span class="badge badge-danger">Impaye</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('comptabilite.eleve.fiche', $item['eleve']) }}" class="btn btn-sm btn-info" title="Voir fiche">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @if($canCreatePaiement)
                                            <a href="{{ route('paiements.create', $item['eleve']) }}" class="btn btn-sm btn-success" title="Enregistrer paiement">
                                                <i class="fa fa-plus"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-success">
                                        <i class="fa fa-check-circle mr-2"></i>Aucun eleve en retard de paiement
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
