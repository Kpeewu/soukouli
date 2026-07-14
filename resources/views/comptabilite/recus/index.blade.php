@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-receipt mr-2"></i>Liste des reçus
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Reçus</a></li>
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

        {{-- Filtres --}}
        <div class="block block-rounded">
            <div class="block-content block-content-full">
                <form method="GET" action="{{ route('recus.index') }}" class="form-inline flex-wrap">
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
                            <option value="valide" {{ request('statut') == 'valide' ? 'selected' : '' }}>Valides</option>
                            <option value="annule" {{ request('statut') == 'annule' ? 'selected' : '' }}>Annules</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function updateNiveaux() {
                document.getElementById('niveau').value = '';
                document.getElementById('classe_id').value = '';
            }

            function updateClasse() {
                document.getElementById('classe_id').value = '';
            }
        </script>

        {{-- Liste des recus --}}
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Liste des reçus ({{ $recus->count() }})</h3>
            </div>
            <div class="block-content block-content-full">
                <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
                    <thead>
                        <tr>
                            <th>N° Recu</th>
                            <th>Date</th>
                            <th>Eleve</th>
                            <th>Classe</th>
                            <th>Type de frais</th>
                            <th class="text-center">Montant</th>
                            <th class="text-center">Comptable</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recus as $recu)
                            @php
                                $classe = $recu->paiement->eleve->classes->first();
                            @endphp
                            <tr class="{{ $recu->annule ? 'table-danger' : '' }}">
                                <td><strong>{{ $recu->numero }}</strong></td>
                                <td data-sort="{{ $recu->date_emission->format('Y-m-d H:i:s') }}">{{ $recu->date_emission->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('comptabilite.eleve.fiche', $recu->paiement->eleve) }}">
                                        {{ $recu->paiement->eleve->nom }} {{ $recu->paiement->eleve->prenom }}
                                    </a>
                                </td>
                                <td>{{ $classe ? $classe->nom : '-' }}</td>
                                <td>{{ $recu->paiement->configurationFrais->typeFrais->nom ?? $recu->paiement->motif ?? '-' }}</td>
                                <td class="text-center font-w600" data-sort="{{ $recu->paiement->montant }}">{{ number_format($recu->paiement->montant, 0, ',', ' ') }} FCFA</td>
                                <td class="text-center">{{ $recu->comptable->username }}</td>
                                <td class="text-center">
                                    @if($recu->annule)
                                        <span class="badge badge-danger">Annule</span>
                                    @else
                                        <span class="badge badge-success">Valide</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle"
                                            id="dropdown-recu-{{ $recu->id }}" data-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-bars mr-1"></i>Actions
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right font-size-sm"
                                            aria-labelledby="dropdown-recu-{{ $recu->id }}">
                                            <li class="nav-main-item">
                                                <a class="nav-main-link" href="{{ route('recus.show', $recu) }}">
                                                    <span class="nav-main-link-name"><i class="fa fa-eye mr-2"></i>Voir le reçu</span>
                                                </a>
                                            </li>
                                            @if(!$recu->annule)
                                                <li class="nav-main-item">
                                                    <a class="nav-main-link" href="{{ route('recus.pdf', $recu) }}" target="_blank">
                                                        <span class="nav-main-link-name"><i class="fa fa-file-pdf mr-2"></i>Telecharger le PDF</span>
                                                    </a>
                                                </li>
                                                @if($canAnnuler)
                                                    <li class="nav-main-item">
                                                        <a class="nav-main-link text-danger" href="#" data-toggle="modal" data-target="#annulerModal{{ $recu->id }}">
                                                            <span class="nav-main-link-name"><i class="fa fa-times mr-2"></i>Annuler le reçu</span>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    @if($canAnnuler && !$recu->annule)
                                        {{-- Modal annulation --}}
                                        <div class="modal fade" id="annulerModal{{ $recu->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('recus.annuler', $recu) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Annuler le recu {{ $recu->numero }}</h5>
                                                            <button type="button" class="close" data-dismiss="modal">
                                                                <span>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="motif_annulation">Motif d'annulation <span class="text-danger">*</span></label>
                                                                <textarea class="form-control" name="motif_annulation" rows="3" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                                            <button type="submit" class="btn btn-danger">Confirmer l'annulation</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
