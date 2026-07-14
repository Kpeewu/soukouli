@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Ordre des matières — {{ $promotion->nom }}</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('bulletin-config.index') }}">Configuration des bulletins</a></li>
                        <li class="breadcrumb-item">{{ $promotion->nom }}</li>
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
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-sort mr-2 text-primary" aria-hidden="true"></i>
                    {{ $promotion->cycle->nom }} — {{ $promotion->nom }}
                </h3>
            </div>
            <div class="block-content">
                @if ($matieres->isEmpty())
                    <div class="alert alert-warning mb-0">
                        <i class="fa fa-exclamation-triangle mr-2"></i>
                        Aucune matière n'est rattachée à ce niveau pour l'année scolaire courante.
                        Rattachez d'abord des matières à ce niveau depuis
                        <a href="{{ route('matiere.index') }}">la gestion des matières</a>.
                    </div>
                @else
                    <small class="form-text text-muted mb-3 d-block">
                        Glissez-déposez la poignée <i class="fa fa-grip-vertical"></i> pour définir l'ordre
                        d'affichage des matières dans le tableau de notes des bulletins PDF de ce niveau.
                        Cet ordre sera automatiquement repris les années suivantes.
                    </small>

                    <form action="{{ route('bulletin-config.update', $promotion) }}" method="POST" id="form-matieres-ordre">
                        @csrf
                        <ul class="list-group mb-3" id="sortable-matieres">
                            @foreach ($matieres as $index => $matiere)
                                <li class="list-group-item d-flex align-items-center matiere-item">
                                    <span class="mr-3 text-muted" style="cursor: grab;"><i class="fa fa-grip-vertical"></i></span>
                                    <span class="matiere-ordre badge badge-primary mr-2">{{ $index + 1 }}</span>
                                    <input type="hidden" name="matieres_ordre[]" value="{{ $matiere->id }}">
                                    <span>{{ $matiere->intitule }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa fa-save mr-1"></i>Enregistrer l'ordre
                        </button>
                        <a href="{{ route('bulletin-config.index') }}" class="btn btn-alt-secondary btn-sm">Annuler</a>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Sortable.js pour le drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sortableList = document.getElementById('sortable-matieres');
            if (sortableList) {
                new Sortable(sortableList, {
                    animation: 150,
                    handle: '.fa-grip-vertical',
                    ghostClass: 'bg-light',
                    onEnd: function() {
                        updateMatieresOrdre();
                    }
                });
            }
        });

        function updateMatieresOrdre() {
            document.querySelectorAll('#sortable-matieres .matiere-item').forEach((item, index) => {
                item.querySelector('.matiere-ordre').textContent = index + 1;
            });
        }
    </script>
@endsection
