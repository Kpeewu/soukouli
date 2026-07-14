@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Saisie des Resultats - {{ $session->examenOfficiel->nom ?? 'Examen' }}
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Examens</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('sessions.index') }}">Sessions</a></li>
                        <li class="breadcrumb-item">Resultats</li>
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
                    Resultats - {{ $session->anneeScolaire->annee ?? '' }}
                    <small class="text-muted">({{ $inscriptions->count() }} candidats)</small>
                </h3>
                <div>
                    <a href="{{ route('sessions.show', $session) }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left mr-1"></i> Retour
                    </a>
                </div>
            </div>

            <div class="block-content">
                <form action="{{ route('resultats.enregistrer', $session) }}" method="POST">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center" style="width: 50px;">N°</th>
                                    <th>Candidat</th>
                                    <th class="text-center">N° Inscription</th>
                                    <th class="text-center" style="width: 120px;">Moyenne /20</th>
                                    <th class="text-center" style="width: 120px;">Mention</th>
                                    <th class="text-center" style="width: 150px;">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inscriptions as $index => $inscription)
                                    <tr>
                                        <td class="text-center text-primary font-w700">{{ $index + 1 }}</td>
                                        <td class="font-w600">
                                            {{ $inscription->eleve->nom ?? '' }} {{ $inscription->eleve->prenom ?? '' }}
                                            <br><small class="text-muted">{{ $inscription->eleve->matricule ?? '' }}</small>
                                        </td>
                                        <td class="text-center">{{ $inscription->numero_inscription ?? '-' }}</td>
                                        <td class="text-center">
                                            <input type="number" step="0.01" min="0" max="20" class="form-control form-control-sm text-center moyenne-input" name="resultats[{{ $inscription->id }}][moyenne]" value="{{ old('resultats.'.$inscription->id.'.moyenne', $inscription->moyenne_obtenue) }}" data-row="{{ $inscription->id }}">
                                        </td>
                                        <td class="text-center">
                                            <select class="form-control form-control-sm mention-select" name="resultats[{{ $inscription->id }}][mention]" data-row="{{ $inscription->id }}">
                                                <option value="">-</option>
                                                <option value="Insuffisant" {{ old('resultats.'.$inscription->id.'.mention', $inscription->mention) == 'Insuffisant' ? 'selected' : '' }}>Insuffisant</option>
                                                <option value="Passable" {{ old('resultats.'.$inscription->id.'.mention', $inscription->mention) == 'Passable' ? 'selected' : '' }}>Passable</option>
                                                <option value="AB" {{ old('resultats.'.$inscription->id.'.mention', $inscription->mention) == 'AB' ? 'selected' : '' }}>Assez Bien</option>
                                                <option value="B" {{ old('resultats.'.$inscription->id.'.mention', $inscription->mention) == 'B' ? 'selected' : '' }}>Bien</option>
                                                <option value="TB" {{ old('resultats.'.$inscription->id.'.mention', $inscription->mention) == 'TB' ? 'selected' : '' }}>Tres Bien</option>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <select class="form-control form-control-sm statut-select" name="resultats[{{ $inscription->id }}][statut]" data-row="{{ $inscription->id }}">
                                                <option value="inscrit" {{ old('resultats.'.$inscription->id.'.statut', $inscription->statut) == 'inscrit' ? 'selected' : '' }}>En attente</option>
                                                <option value="admis" {{ old('resultats.'.$inscription->id.'.statut', $inscription->statut) == 'admis' ? 'selected' : '' }}>Admis</option>
                                                <option value="ajourne" {{ old('resultats.'.$inscription->id.'.statut', $inscription->statut) == 'ajourne' ? 'selected' : '' }}>Ajourne</option>
                                                <option value="absent" {{ old('resultats.'.$inscription->id.'.statut', $inscription->statut) == 'absent' ? 'selected' : '' }}>Absent</option>
                                            </select>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center h4 p-3" colspan="6">Aucun candidat inscrit</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($inscriptions->isNotEmpty())
                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save mr-1"></i> Enregistrer les resultats
                            </button>
                            <button type="button" class="btn btn-warning" id="auto-statut">
                                <i class="fa fa-magic mr-1"></i> Attribuer statuts auto (>=10 = Admis)
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <script>
        // Attribue le statut et la mention d'une ligne d'apres sa moyenne
        function updateStatutEtMention(input) {
            var rowId = input.dataset.row;
            var moyenne = parseFloat(input.value);
            var statutSelect = document.querySelector('.statut-select[data-row="' + rowId + '"]');
            var mentionSelect = document.querySelector('.mention-select[data-row="' + rowId + '"]');

            if (isNaN(moyenne)) {
                mentionSelect.value = '';
                return;
            }

            if (moyenne >= 10) {
                statutSelect.value = 'admis';
                if (moyenne >= 16) {
                    mentionSelect.value = 'TB';
                } else if (moyenne >= 14) {
                    mentionSelect.value = 'B';
                } else if (moyenne >= 12) {
                    mentionSelect.value = 'AB';
                } else {
                    mentionSelect.value = 'Passable';
                }
            } else {
                statutSelect.value = 'ajourne';
                mentionSelect.value = 'Insuffisant';
            }
        }

        // Attribution en bloc des statuts/mentions sur toutes les lignes
        document.getElementById('auto-statut').addEventListener('click', function() {
            document.querySelectorAll('.moyenne-input').forEach(updateStatutEtMention);
        });

        // Attribution automatique des qu'une moyenne est saisie ou modifiee
        document.querySelectorAll('.moyenne-input').forEach(function(input) {
            input.addEventListener('change', function() {
                updateStatutEtMention(this);
            });
        });
    </script>
@endsection
