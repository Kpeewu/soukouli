@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-receipt mr-2"></i>Recu {{ $recu->numero }}
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('recus.index') }}">Recus</a></li>
                        <li class="breadcrumb-item">{{ $recu->numero }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="block block-rounded {{ $recu->annule ? 'block-bordered border-danger' : '' }}">
                    @if($recu->annule)
                        <div class="block-header bg-danger">
                            <h3 class="block-title text-white">RECU ANNULE</h3>
                        </div>
                    @endif
                    <div class="block-content pb-4">
                        <div class="text-center mb-4">
                            <h4>COMPLEXE SCOLAIRE CPL MON AVENIR</h4>
                            <h5>RECU DE PAIEMENT</h5>
                            <p class="font-size-h4 font-w700">N° {{ $recu->numero }}</p>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Date d'emission:</strong> {{ $recu->date_emission->format('d/m/Y H:i') }}</p>
                                <p><strong>Comptable:</strong> {{ $recu->comptable->username }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Eleve:</strong> {{ $recu->paiement->eleve->nom }} {{ $recu->paiement->eleve->prenom }}</p>
                                <p><strong>Matricule:</strong> {{ $recu->paiement->eleve->matricule }}</p>
                                @php $classe = $recu->paiement->eleve->getClasseActuelle(); @endphp
                                <p><strong>Classe:</strong> {{ $classe ? $classe->nom : '-' }}</p>
                            </div>
                        </div>

                        <hr>

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Designation</th>
                                    <th class="text-right">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        {{ $recu->paiement->configurationFrais->typeFrais->nom ?? $recu->paiement->motif }}
                                        @if($recu->paiement->tranche)
                                            - {{ $recu->paiement->tranche->nom }}
                                        @endif
                                    </td>
                                    <td class="text-right font-w700">{{ number_format($recu->paiement->montant, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>TOTAL</th>
                                    <th class="text-right">{{ number_format($recu->paiement->montant, 0, ',', ' ') }} FCFA</th>
                                </tr>
                            </tfoot>
                        </table>

                        <p><strong>Mode de paiement:</strong> {{ ucfirst($recu->paiement->mode_paiement ?? $recu->paiement->methode) }}</p>
                        @if($recu->paiement->reference)
                            <p><strong>Reference:</strong> {{ $recu->paiement->reference }}</p>
                        @endif
                        @if($recu->paiement->notes)
                            <p><strong>Notes:</strong> {{ $recu->paiement->notes }}</p>
                        @endif

                        @if($recu->annule)
                            <hr>
                            <div class="alert alert-danger">
                                <strong>Motif d'annulation:</strong> {{ $recu->motif_annulation }}
                            </div>
                        @endif

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('recus.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left mr-2"></i>Retour
                            </a>
                            @if(!$recu->annule)
                                <a href="{{ route('recus.pdf', $recu) }}" class="btn btn-primary" target="_blank">
                                    <i class="fa fa-file-pdf mr-2"></i>Telecharger PDF
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
