@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Inscriptions - {{ $session->examenOfficiel->nom ?? 'Examen' }}
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Examens</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('sessions.index') }}">Sessions</a></li>
                        <li class="breadcrumb-item">Inscriptions</li>
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
                <h3 class="block-title">
                    Candidats inscrits
                    <small class="text-muted">({{ $inscriptions->count() }} eleves)</small>
                </h3>
                <div>
                    @unless(auth()->user()->isDirecteur())
                        <a href="{{ route('inscriptions.create', ['session' => $session]) }}" class="btn btn-success">
                            <i class="fa fa-user-plus mr-1"></i> Inscrire des eleves
                        </a>
                    @endunless
                    <a href="{{ route('sessions.show', $session) }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left mr-1"></i> Retour
                    </a>
                </div>
            </div>

            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">N°</th>
                                <th>Eleve</th>
                                <th class="text-center">Matricule</th>
                                <th class="text-center">N° Inscription</th>
                                <th class="text-center">Centre d'examen</th>
                                <th class="text-center">Statut</th>
                                @unless(auth()->user()->isDirecteur())
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                @endunless
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inscriptions as $index => $inscription)
                                <tr>
                                    <td class="text-center text-primary font-w700">{{ $index + 1 }}</td>
                                    <td class="font-w600">
                                        {{ $inscription->eleve->nom ?? '' }} {{ $inscription->eleve->prenom ?? '' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary">{{ $inscription->eleve->matricule ?? '-' }}</span>
                                    </td>
                                    <td class="text-center">{{ $inscription->numero_inscription ?? '-' }}</td>
                                    <td class="text-center">{{ $inscription->centre_examen ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($inscription->statut === 'inscrit')
                                            <span class="badge badge-secondary">Inscrit</span>
                                        @elseif($inscription->statut === 'admis')
                                            <span class="badge badge-success">Admis</span>
                                        @elseif($inscription->statut === 'ajourne')
                                            <span class="badge badge-danger">Ajourne</span>
                                        @else
                                            <span class="badge badge-warning">Absent</span>
                                        @endif
                                    </td>
                                    @unless(auth()->user()->isDirecteur())
                                        <td class="text-center">
                                            <form action="{{ route('inscriptions.destroy', $inscription) }}" method="post" style="display: inline;" onsubmit="return confirm('Retirer cet eleve de la session ?')">
                                                @csrf
                                                @method('delete')
                                                <button class="btn btn-sm btn-danger" title="Retirer"><i class="fa fa-times"></i></button>
                                            </form>
                                        </td>
                                    @endunless
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center h4 p-3" colspan="7">Aucun eleve inscrit a cette session</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
