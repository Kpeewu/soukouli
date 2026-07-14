@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Sessions d'Examens Officiels</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Examens</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Sessions</a></li>
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
                <h3 class="block-title">Sessions d'Examens</h3>
                <a href="{{ route('sessions.create') }}" class="btn btn-success">Nouvelle session</a>
            </div>

            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center">Examen</th>
                                <th class="text-center">Cycle</th>
                                <th class="text-center">Annee</th>
                                <th class="text-center">Dates</th>
                                <th class="text-center">Statut</th>
                                <th class="text-center">Inscrits</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sessions as $session)
                                <tr>
                                    <td class="text-center font-weight-bold">{{ $session->examenOfficiel->nom }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $session->examenOfficiel->cycle->nom }}</span>
                                    </td>
                                    <td class="text-center">{{ $session->anneeScolaire->annee }}</td>
                                    <td class="text-center">
                                        @if($session->date_debut)
                                            {{ $session->date_debut->format('d/m/Y') }}
                                            @if($session->date_fin)
                                                - {{ $session->date_fin->format('d/m/Y') }}
                                            @endif
                                        @else
                                            Non definies
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $badgeClass = match($session->statut) {
                                                'programme' => 'warning',
                                                'en_cours' => 'primary',
                                                'termine' => 'success',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $session->statut)) }}</span>
                                    </td>
                                    <td class="text-center">{{ $session->inscriptions->count() }}</td>
                                    <td class="text-center">
                                        @if(auth()->user()->isDirecteur())
                                            <a href="{{ route('sessions.show', $session) }}" class="btn btn-sm btn-primary">
                                                <i class="fa fa-eye mr-1"></i>Voir
                                            </a>
                                        @else
                                            <a href="{{ route('sessions.show', $session) }}" class="btn btn-sm btn-primary">
                                                <i class="fa fa-cog mr-1"></i>Gérer
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center h4 p-3" colspan="7">Aucune session d'examen</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
