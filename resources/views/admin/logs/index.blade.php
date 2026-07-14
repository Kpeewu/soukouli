@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-bug mr-2"></i>Logs &amp; Erreurs
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Logs &amp; Erreurs</a></li>
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
                <h3 class="block-title">Fichier de log</h3>
                <div>
                    @if($currentFile)
                        <a href="{{ route('logs.download', ['file' => $currentFile]) }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-download mr-1"></i>Télécharger
                        </a>
                        <form action="{{ route('logs.clear') }}" method="post" class="d-inline"
                              onsubmit="return confirm('Voulez-vous vraiment vider le fichier « {{ $currentFile }} » ? Cette action est irréversible.')">
                            @csrf
                            @method('delete')
                            <input type="hidden" name="file" value="{{ $currentFile }}">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fa fa-eraser mr-1"></i>Vider ce fichier
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="block-content">
                <form action="{{ route('logs.index') }}" method="get" class="row align-items-end">
                    <div class="col-md-4 form-group">
                        <label for="file">Fichier</label>
                        <select name="file" id="file" class="form-control" onchange="this.form.submit()">
                            @forelse ($files as $file)
                                <option value="{{ $file['name'] }}" @selected($file['name'] === $currentFile)>
                                    {{ $file['name'] }} ({{ $file['size_human'] }} — {{ $file['modified_at'] }})
                                </option>
                            @empty
                                <option value="">Aucun fichier de log trouvé</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="level">Niveau</label>
                        <select name="level" id="level" class="form-control">
                            <option value="">Tous les niveaux</option>
                            @foreach (['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'] as $level)
                                <option value="{{ $level }}" @selected($filters['level'] === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="q">Recherche</label>
                        <input type="text" name="q" id="q" class="form-control" placeholder="Rechercher dans les messages..."
                               value="{{ $filters['q'] }}">
                    </div>
                    <div class="col-md-1 form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Entrées ({{ $entries->count() }})</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
                        <thead>
                            <tr>
                                <th style="width: 160px;">Date/Heure</th>
                                <th class="text-center" style="width: 110px;">Niveau</th>
                                <th>Message</th>
                                <th class="text-center" style="width: 160px;">Utilisateur</th>
                                <th class="text-center" style="width: 90px;">Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $index => $entry)
                                @php
                                    $badge = match ($entry['level']) {
                                        'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'danger',
                                        'WARNING' => 'warning',
                                        'INFO', 'NOTICE' => 'info',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $entry['datetime'] }}</td>
                                    <td class="text-center"><span class="badge badge-{{ $badge }}">{{ $entry['level'] }}</span></td>
                                    <td>{{ Str::limit($entry['message'], 140) }}</td>
                                    <td class="text-center">{{ $entry['user'] ?? 'Système' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal"
                                                data-target="#log-modal-{{ $index }}">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center h4 p-3" colspan="5">Aucune entrée de log trouvée</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modaux de detail, en dehors du tableau pour ne pas perturber DataTables --}}
    @foreach ($entries as $index => $entry)
        <div class="modal fade" id="log-modal-{{ $index }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $entry['datetime'] }} — {{ $entry['level'] }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <pre class="bg-body-light p-3 mb-0" style="white-space: pre-wrap; word-break: break-word;">{{ $entry['raw'] }}</pre>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
