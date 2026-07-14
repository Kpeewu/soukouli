@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Examens Officiels</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Examens Officiels</a></li>
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
                <h3 class="block-title">Liste des Examens Officiels</h3>
                <a href="{{ route('examens-officiels.create') }}" class="btn btn-success">Ajouter un examen</a>
            </div>

            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width: 50px;">N°</th>
                                <th class="text-center">Nom</th>
                                <th class="text-center">Code</th>
                                <th class="text-center">Cycle</th>
                                <th class="text-center">Niveau requis</th>
                                <th class="text-center">Description</th>
                                <th class="text-center" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($examens as $index => $examen)
                                <tr>
                                    <td class="text-center text-primary" style="font-weight: 700;">{{ $index + 1 }}</td>
                                    <td class="text-center font-w600">{{ $examen->nom }}</td>
                                    <td class="text-center"><span class="badge badge-primary">{{ $examen->code }}</span></td>
                                    <td class="text-center">{{ $examen->cycle->nom ?? '-' }}</td>
                                    <td class="text-center"><span class="badge badge-info">{{ $examen->niveau_requis }}</span></td>
                                    <td class="text-center">{{ Str::limit($examen->description, 50) }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-around">
                                            <a href="{{ route('examens-officiels.edit', $examen) }}" class="btn btn-sm btn-success">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form action="{{ route('examens-officiels.destroy', $examen) }}" method="post" onsubmit="return confirm('Voulez-vous supprimer cet examen ?')">
                                                @csrf
                                                @method('delete')
                                                <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center h4 p-3" colspan="7">Aucun examen officiel</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
