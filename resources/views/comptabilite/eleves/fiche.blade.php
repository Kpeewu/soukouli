@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-user mr-2"></i>Fiche comptable - {{ $eleve->nom }} {{ $eleve->prenom }}
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('comptabilite.eleves') }}">Eleves</a></li>
                        <li class="breadcrumb-item">{{ $eleve->matricule }}</li>
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
            {{-- Informations eleve --}}
            <div class="col-lg-4">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Informations</h3>
                    </div>
                    <div class="block-content pb-3">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Matricule:</strong></td>
                                <td>{{ $eleve->matricule }}</td>
                            </tr>
                            <tr>
                                <td><strong>Nom:</strong></td>
                                <td>{{ $eleve->nom }} {{ $eleve->prenom }}</td>
                            </tr>
                            <tr>
                                <td><strong>Classe:</strong></td>
                                <td>{{ $classe ? $classe->nom : '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Cycle:</strong></td>
                                <td>{{ $classe && $classe->promotion && $classe->promotion->cycle ? $classe->promotion->cycle->nom : '-' }}</td>
                            </tr>
                        </table>
                        <hr>
                        <div class="text-center py-3">
                            <div class="font-size-h3 font-w700 {{ $eleve->getSoldeRestant() > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($eleve->getSoldeRestant(), 0, ',', ' ') }} FCFA
                            </div>
                            <div class="text-muted">Solde restant</div>
                        </div>
                        @if($canCreatePaiement && $eleve->getSoldeRestant() > 0)
                            <a href="{{ route('paiements.create', $eleve) }}" class="btn btn-success btn-block">
                                <i class="fa fa-plus mr-2"></i>Enregistrer un paiement
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Frais et tranches --}}
            <div class="col-lg-8">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Frais de scolarite</h3>
                    </div>
                    <div class="block-content">
                        @forelse($fraisAvecStatut as $frais)
                            <div class="mb-4 p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">{{ $frais['type_frais']->nom }}</h5>
                                    @if($frais['statut'] == 'solde')
                                        <span class="badge badge-success">Solde</span>
                                    @elseif($frais['statut'] == 'partiel')
                                        <span class="badge badge-warning">Partiel</span>
                                    @else
                                        <span class="badge badge-danger">Impaye</span>
                                    @endif
                                </div>
                                <div class="row text-center mb-2">
                                    <div class="col-4">
                                        <div class="font-w600">{{ number_format($frais['montant_total'], 0, ',', ' ') }}</div>
                                        <small class="text-muted">Total</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="font-w600 text-success">{{ number_format($frais['montant_paye'], 0, ',', ' ') }}</div>
                                        <small class="text-muted">Paye</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="font-w600 text-danger">{{ number_format($frais['solde'], 0, ',', ' ') }}</div>
                                        <small class="text-muted">Reste</small>
                                    </div>
                                </div>

                                @if(count($frais['tranches']) > 0)
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tranche</th>
                                                <th class="text-center">Montant</th>
                                                <th class="text-center">Date limite</th>
                                                <th class="text-center">Paye</th>
                                                <th class="text-center">Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($frais['tranches'] as $tranche)
                                                <tr>
                                                    <td>{{ $tranche['tranche']->nom }}</td>
                                                    <td class="text-center">{{ number_format($tranche['tranche']->montant, 0, ',', ' ') }}</td>
                                                    <td class="text-center">{{ $tranche['tranche']->date_limite->format('d/m/Y') }}</td>
                                                    <td class="text-center text-success">{{ number_format($tranche['montant_paye'], 0, ',', ' ') }}</td>
                                                    <td class="text-center">
                                                        @if($tranche['solde'] <= 0)
                                                            <span class="badge badge-success">OK</span>
                                                        @elseif($tranche['en_retard'])
                                                            <span class="badge badge-danger">En retard</span>
                                                        @else
                                                            <span class="badge badge-warning">A payer</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        @empty
                            <p class="text-center text-muted">Aucun frais configure pour cet eleve</p>
                        @endforelse
                    </div>
                </div>

                {{-- Historique des paiements --}}
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Historique des paiements</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type de frais</th>
                                        <th>Tranche</th>
                                        <th class="text-center">Montant</th>
                                        <th class="text-center">Mode</th>
                                        <th class="text-center">Recu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($eleve->paiements->sortByDesc('created_at') as $paiement)
                                        <tr class="{{ $paiement->annule ? 'table-danger' : '' }}">
                                            <td>{{ $paiement->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{{ $paiement->configurationFrais->typeFrais->nom ?? $paiement->motif }}</td>
                                            <td>{{ $paiement->tranche ? $paiement->tranche->nom : '-' }}</td>
                                            <td class="text-center font-w600">
                                                {{ number_format($paiement->montant, 0, ',', ' ') }}
                                                @if($paiement->annule)
                                                    <span class="badge badge-danger ml-1">Annule</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ ucfirst($paiement->mode_paiement ?? $paiement->methode) }}</td>
                                            <td class="text-center">
                                                @if($paiement->recu)
                                                    <a href="{{ route('recus.pdf', $paiement->recu) }}" class="btn btn-sm btn-info" target="_blank">
                                                        <i class="fa fa-file-pdf"></i>
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">Aucun paiement</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
