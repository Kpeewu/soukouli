@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-clock mr-2"></i>Tâches planifiées
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Tâches planifiées</a></li>
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

        @php
            $monthNames = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
            ];
            $currentMonth = now()->month;
        @endphp

        @foreach ($tasks as $task)
            @php
                if (! $task['enabled']) {
                    $statusText = "Désactivé — ne s'exécutera pas automatiquement.";
                    $statusBadge = 'secondary';
                } elseif ($task['month'] === $currentMonth) {
                    $statusText = 'Actif ce mois-ci — s\'exécute automatiquement selon sa fréquence.';
                    $statusBadge = 'success';
                } else {
                    $statusText = "Activé, mais ne s'exécutera qu'en {$monthNames[$task['month']]}.";
                    $statusBadge = 'info';
                }
            @endphp
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">
                        {{ $task['label'] }}
                        <span class="badge badge-{{ $statusBadge }} ml-2">{{ $task['enabled'] ? 'Actif' : 'Inactif' }}</span>
                    </h3>
                </div>
                <div class="block-content">
                    <p>{{ $task['description'] }}</p>
                    <p class="text-muted">{{ $statusText }}</p>

                    <form action="{{ route('crons.config', $task['key']) }}" method="post" class="form-inline mb-3">
                        @csrf
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="enabled-{{ $task['key'] }}"
                                   name="enabled" value="1" @checked($task['enabled'])>
                            <label class="custom-control-label" for="enabled-{{ $task['key'] }}">Activé</label>
                        </div>
                        <label for="month-{{ $task['key'] }}" class="mr-2">Mois de déclenchement</label>
                        <select name="month" id="month-{{ $task['key'] }}" class="form-control mr-3">
                            @foreach ($monthNames as $num => $name)
                                <option value="{{ $num }}" @selected($task['month'] === $num)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save mr-1"></i>Enregistrer
                        </button>
                    </form>

                    <div class="mb-3">
                        @if ($task['manual_method'] === 'post')
                            <form action="{{ route($task['manual_route']) }}" method="post" class="d-inline"
                                  onsubmit="return confirm('Voulez-vous vraiment exécuter cette tâche maintenant ?')">
                                @csrf
                                <button type="submit" class="btn btn-warning">
                                    <i class="fa fa-play mr-1"></i>Exécuter maintenant
                                </button>
                            </form>
                        @else
                            <a href="{{ route($task['manual_route']) }}" class="btn btn-warning">
                                <i class="fa fa-arrow-right mr-1"></i>Gérer manuellement
                            </a>
                        @endif
                    </div>

                    <h4 class="font-size-base">
                        Journal d'exécution
                        <small id="cron-log-{{ $task['key'] }}-meta" class="text-muted font-weight-normal">
                            {{ $task['log']['modified_at'] ? 'Dernière exécution : ' . $task['log']['modified_at'] : 'Jamais exécuté' }}
                        </small>
                    </h4>
                    <pre id="cron-log-{{ $task['key'] }}" class="bg-body-light p-3 mb-0"
                         style="max-height: 300px; overflow-y: auto; white-space: pre-wrap; word-break: break-word;"
                         data-log-url="{{ route('crons.log', $task['key']) }}">{{ $task['log']['content'] ?: '(aucune sortie)' }}</pre>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        document.querySelectorAll('[data-log-url]').forEach(function (pre) {
            setInterval(function () {
                fetch(pre.dataset.logUrl, { headers: { 'Accept': 'application/json' } })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        pre.textContent = data.content || '(aucune sortie)';
                        var meta = document.getElementById(pre.id + '-meta');
                        if (meta) {
                            meta.textContent = data.modified_at ? 'Dernière exécution : ' + data.modified_at : 'Jamais exécuté';
                        }
                    })
                    .catch(function () {});
            }, 5000);
        });
    </script>
@endsection
