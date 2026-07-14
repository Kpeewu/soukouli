@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-users mr-2"></i>Paiements des eleves
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Eleves</a></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        {{-- Filtres --}}
        <div class="block block-rounded">
            <div class="block-content block-content-full">
                <form method="GET" action="{{ route('comptabilite.eleves') }}" class="form-inline flex-wrap">
                    <div class="mr-3 mb-2">
                        <label class="mr-2">Cycle:</label>
                        <select name="cycle_id" id="cycle_id" class="form-control" onchange="updateNiveaux(); this.form.submit()">
                            <option value="">Tous</option>
                            @foreach($cycles as $c)
                                <option value="{{ $c->id }}" {{ $cycle && $cycle->id == $c->id ? 'selected' : '' }}>
                                    {{ $c->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mr-3 mb-2">
                        <label class="mr-2">Niveau:</label>
                        <select name="niveau" id="niveau" class="form-control" onchange="updateClasse(); this.form.submit()">
                            <option value="">Tous</option>
                            @if(isset($niveaux))
                                @foreach($niveaux as $niv)
                                    <option value="{{ $niv }}" {{ (isset($niveau) && $niveau == $niv) ? 'selected' : '' }}>
                                        {{ $niv }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mr-3 mb-2">
                        <label class="mr-2">Classe:</label>
                        <select name="classe_id" id="classe_id" class="form-control" onchange="this.form.submit()">
                            <option value="">Toutes</option>
                            @if(isset($classes))
                                @foreach($classes as $cl)
                                    <option value="{{ $cl->id }}" {{ (isset($classeId) && (int) $classeId === $cl->id) ? 'selected' : '' }}>
                                        {{ $cl->nom }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mr-3 mb-2">
                        <label class="mr-2">Statut:</label>
                        <select name="statut" class="form-control" onchange="this.form.submit()">
                            <option value="">Tous</option>
                            <option value="solde" {{ request('statut') == 'solde' ? 'selected' : '' }}>Soldes</option>
                            <option value="partiel" {{ request('statut') == 'partiel' ? 'selected' : '' }}>Partiels</option>
                            <option value="impaye" {{ request('statut') == 'impaye' ? 'selected' : '' }}>Impayes</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="mr-2">Tri:</label>
                        <select name="tri" class="form-control" onchange="this.form.submit()">
                            <option value="nom" {{ request('tri') == 'nom' ? 'selected' : '' }}>Par nom</option>
                            <option value="solde" {{ request('tri') == 'solde' ? 'selected' : '' }}>Par solde</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function updateNiveaux() {
                // Reset niveau et classe quand le cycle change
                document.getElementById('niveau').value = '';
                document.getElementById('classe_id').value = '';
            }

            function updateClasse() {
                // Reset classe quand le niveau change
                document.getElementById('classe_id').value = '';
            }
        </script>

        {{-- Liste des eleves --}}
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    Liste des eleves ({{ $eleves->count() }})
                </h3>
            </div>
            <div class="block-content block-content-full">
                <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom & Prenom</th>
                            <th>Classe</th>
                            <th class="text-center">Total frais</th>
                            <th class="text-center">Paye</th>
                            <th class="text-center">Solde</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eleves as $item)
                            <tr>
                                <td>{{ $item['eleve']->matricule }}</td>
                                <td>
                                    <strong>{{ $item['eleve']->nom }}</strong> {{ $item['eleve']->prenom }}
                                </td>
                                <td>{{ $item['classe'] ? $item['classe']->nom : '-' }}</td>
                                <td class="text-center" data-sort="{{ $item['total_frais'] }}">{{ number_format($item['total_frais'], 0, ',', ' ') }}</td>
                                <td class="text-center text-success" data-sort="{{ $item['total_paye'] }}">{{ number_format($item['total_paye'], 0, ',', ' ') }}</td>
                                <td class="text-center font-w600" data-sort="{{ $item['solde'] }}">{{ number_format($item['solde'], 0, ',', ' ') }}</td>
                                <td class="text-center">
                                    @if($item['statut'] == 'solde')
                                        <span class="badge badge-success">Solde</span>
                                    @elseif($item['statut'] == 'partiel')
                                        <span class="badge badge-warning">Partiel</span>
                                    @else
                                        <span class="badge badge-danger">Impaye</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle"
                                            id="dropdown-actions-{{ $item['eleve']->id }}" data-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-bars mr-1"></i>Actions
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right font-size-sm"
                                            aria-labelledby="dropdown-actions-{{ $item['eleve']->id }}">
                                            <li class="nav-main-item">
                                                <a class="nav-main-link" href="{{ route('comptabilite.eleve.fiche', $item['eleve']) }}">
                                                    <span class="nav-main-link-name"><i class="fa fa-eye mr-2"></i>Voir les paiements</span>
                                                </a>
                                            </li>
                                            @if($canCreatePaiement && $item['solde'] > 0)
                                                <li class="nav-main-item">
                                                    <a class="nav-main-link" href="{{ route('paiements.create', $item['eleve']) }}">
                                                        <span class="nav-main-link-name"><i class="fa fa-money-bill-wave mr-2"></i>Enregistrer un paiement</span>
                                                    </a>
                                                </li>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
