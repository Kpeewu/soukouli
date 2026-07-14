@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Gestion des Cycles
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Cycles</a></li>
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

        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Liste des Cycles</h3>
                <a href="{{ route('cycles.create') }}" class="btn btn-success">Ajouter un cycle</a>
            </div>

            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Ordre</th>
                                <th class="text-center">Nom</th>
                                <th class="text-center">Niveaux</th>
                                <th class="text-center">Cycle suivant</th>
                                <th class="text-center">Semestres</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cycles as $cycle)
                                <tr>
                                    <td class="text-center text-primary" style="font-weight: 700;">{{ $cycle->ordre }}</td>
                                    <td>
                                        <strong>{{ $cycle->nom }}</strong>
                                    </td>
                                    <td class="text-center">
                                        @if($cycle->niveaux && count($cycle->niveaux) > 0)
                                            @foreach($cycle->niveaux as $niveau)
                                                <span class="badge badge-secondary mr-1 mb-1">{{ $niveau }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">Non definis</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($cycle->cycleSuivant)
                                            <span class="badge badge-info">
                                                <i class="fa fa-arrow-right mr-1"></i>{{ $cycle->cycleSuivant->nom }}
                                            </span>
                                        @else
                                            <span class="badge badge-warning">Fin de scolarite</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($cycle->supports_semestre)
                                            <span class="badge badge-success">Oui</span>
                                        @else
                                            <span class="badge badge-secondary">Non</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-around">
                                            <a href="{{ route('cycles.edit', $cycle) }}" class="btn btn-sm btn-success" title="Modifier">
                                                <i class="fa fa-edit" aria-hidden="true"></i>
                                            </a>
                                            <form action="{{ route('cycles.destroy', $cycle) }}" method="post" onsubmit="return confirm('Voulez-vous supprimer ce cycle ?')">
                                                @csrf
                                                @method('delete')
                                                <button class="btn btn-sm btn-danger" title="Supprimer"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center h4 p-3" colspan="6">Aucun cycle</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3">
                    <h5><i class="fa fa-info-circle mr-2"></i>Comment fonctionne le passage entre cycles</h5>
                    <p class="mb-0">
                        Quand un eleve termine le dernier niveau d'un cycle (ex: Terminale pour le Lycee),
                        il est automatiquement dirige vers le <strong>premier niveau</strong> du <strong>cycle suivant</strong> defini.
                        <br>Si aucun cycle suivant n'est defini, l'eleve est considere comme diplome (fin de scolarite).
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
