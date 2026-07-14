@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Configuration des bulletins
                    @if ($anneeCourante)
                        <span class="badge badge-primary ml-2">{{ $anneeCourante->annee }}</span>
                    @endif
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item">Configuration des bulletins</li>
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

        <div class="alert alert-info">
            <i class="fa fa-info-circle mr-2"></i>
            <strong>Information :</strong> définissez, pour chaque niveau, l'ordre dans lequel les matières
            apparaissent dans le tableau de notes des bulletins PDF. Cet ordre est conservé automatiquement
            d'une année scolaire à l'autre.
        </div>

        @forelse ($cycles as $cycle)
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title"><i class="fa fa-graduation-cap mr-2"></i>{{ $cycle->nom }}</h3>
                </div>
                <div class="block-content">
                    @if ($cycle->promotions->isEmpty())
                        <p class="text-muted text-center py-3">Aucun niveau pour ce cycle</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Niveau</th>
                                        <th class="text-center">Matières</th>
                                        <th class="text-center" style="width: 220px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cycle->promotions as $promotion)
                                        <tr>
                                            <td class="font-w600">{{ $promotion->nom }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-secondary">{{ $promotion->matieres_count }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if ($promotion->matieres_count > 0)
                                                    <a href="{{ route('bulletin-config.edit', $promotion) }}" class="btn btn-sm btn-primary">
                                                        <i class="fa fa-sort mr-1"></i>Configurer l'affichage
                                                    </a>
                                                @else
                                                    <span class="badge badge-warning" title="Aucune matière rattachée à ce niveau">
                                                        <i class="fa fa-exclamation-triangle mr-1"></i>Aucune matière
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="block block-rounded">
                <div class="block-content text-center py-5">
                    <p class="text-muted mb-0">Aucune année scolaire courante définie, ou aucun cycle accessible.</p>
                </div>
            </div>
        @endforelse
    </div>
@endsection
