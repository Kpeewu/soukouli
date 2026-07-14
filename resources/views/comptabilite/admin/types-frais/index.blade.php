@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-tags mr-2"></i>Types de frais
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Comptabilite</li>
                        <li class="breadcrumb-item">Configuration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Types de frais</a></li>
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
                <h3 class="block-title">Liste des types de frais</h3>
                <a href="{{ route('types-frais.create') }}" class="btn btn-success">
                    <i class="fa fa-plus mr-2"></i>Ajouter
                </a>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th class="text-center">Obligatoire</th>
                                <th class="text-center">Actif</th>
                                <th class="text-center">Configurations</th>
                                <th class="text-center" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($typesFrais as $type)
                                <tr>
                                    <td><span class="badge badge-primary">{{ $type->code }}</span></td>
                                    <td><strong>{{ $type->nom }}</strong></td>
                                    <td>{{ $type->description ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($type->obligatoire)
                                            <span class="badge badge-success">Oui</span>
                                        @else
                                            <span class="badge badge-secondary">Non</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($type->actif)
                                            <span class="badge badge-success">Actif</span>
                                        @else
                                            <span class="badge badge-danger">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $type->configurations_count }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('types-frais.edit', $type) }}" class="btn btn-sm btn-success" title="Modifier">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        @if($type->configurations_count == 0)
                                            <form action="{{ route('types-frais.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce type de frais ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Aucun type de frais</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
