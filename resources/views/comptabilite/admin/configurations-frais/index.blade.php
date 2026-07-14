@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-cogs mr-2"></i>Configuration des frais
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item">Configuration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Tarifs</a></li>
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

        @if($anneeCourante)
            <div class="alert alert-info">
                <i class="fa fa-info-circle mr-2"></i>
                Annee scolaire courante: <strong>{{ $anneeCourante->annee }}</strong>
            </div>
        @endif

        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Configurations des frais par cycle/niveau</h3>
                <a href="{{ route('configurations-frais.create') }}" class="btn btn-success">
                    <i class="fa fa-plus mr-2"></i>Ajouter une configuration
                </a>
            </div>
            <div class="block-content">
                @foreach($cycles as $cycle)
                    @php
                        $cycleConfigs = $configurations->where('cycle_id', $cycle->id);
                    @endphp
                    @if($cycleConfigs->count() > 0)
                        <h5 class="mt-3">{{ $cycle->nom }}</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Type de frais</th>
                                        <th>Niveau</th>
                                        <th class="text-center">Montant</th>
                                        <th class="text-center">Tranches</th>
                                        <th class="text-center">Actif</th>
                                        <th class="text-center" style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cycleConfigs as $config)
                                        <tr>
                                            <td><strong>{{ $config->typeFrais->nom }}</strong></td>
                                            <td>{{ $config->niveau ?? 'Tous les niveaux' }}</td>
                                            <td class="text-center font-w700">{{ number_format($config->montant, 0, ',', ' ') }} FCFA</td>
                                            <td class="text-center">
                                                @if($config->tranches->count() > 0)
                                                    <span class="badge badge-info">{{ $config->tranches->count() }} tranche(s)</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($config->actif)
                                                    <span class="badge badge-success">Actif</span>
                                                @else
                                                    <span class="badge badge-danger">Inactif</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('configurations-frais.edit', $config) }}" class="btn btn-sm btn-success" title="Modifier">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('configurations-frais.destroy', $config) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette configuration ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endforeach

                @if($configurations->count() == 0)
                    <p class="text-center text-muted py-4">Aucune configuration de frais</p>
                @endif
            </div>
        </div>
    </div>
@endsection
