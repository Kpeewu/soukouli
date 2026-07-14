@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-arrow-up mr-2"></i>Passage en année supérieure
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Passage année supérieure</a></li>
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

        <div class="block block-rounded pb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-calendar-alt mr-2"></i>Année scolaire</h3>
            </div>
            <div class="block-content">
                <div class="d-flex align-items-center">
                    <span class="badge badge-secondary" style="font-size: 1rem;">
                        {{ $currentAnneeScolaire->annee ?? 'Aucune' }}
                    </span>
                    <i class="fa fa-long-arrow-alt-right mx-3 text-muted"></i>
                    <span class="badge {{ $nextYearExists ? 'badge-success' : 'badge-danger' }}" style="font-size: 1rem;">
                        {{ $nextLabel }}
                    </span>
                </div>
                @unless($nextYearExists)
                    <p class="text-danger mt-3 mb-0">
                        L'année scolaire suivante ({{ $nextLabel }}) n'existe pas encore. Le passage en année
                        supérieure ne peut pas être déclenché tant qu'elle n'a pas été générée (par un
                        administrateur ou le directeur général).
                    </p>
                @endunless
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-sitemap mr-2"></i>Périmètre</h3>
            </div>
            <div class="block-content">
                @if($isGeneral)
                    <div class="form-group" style="max-width: 400px;">
                        <label for="cycle_id">Cycle concerné</label>
                        <select id="cycle_id" class="form-control">
                            <option value="">Tous les cycles</option>
                            @foreach($cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <p class="mb-0">
                        Cycle : <strong id="passage-fixed-cycle">{{ $cycles->first()->nom ?? 'Aucun cycle assigné' }}</strong>
                    </p>
                @endif
            </div>
        </div>

        @if($nextYearExists && (!$isGeneral ? $cycles->isNotEmpty() : true))
            <div class="block block-rounded pb-4">
                <div class="block-content text-center">
                    <button type="button" id="passage-start-btn" class="btn btn-success btn-lg" data-toggle="modal"
                            data-target="#passageModal">
                        <i class="fa fa-play mr-1"></i> Déclencher le passage
                    </button>
                </div>
            </div>
        @endif
    </div>

    <div class="modal fade" id="passageModal" tabindex="-1" role="dialog" aria-labelledby="passageModalLabel"
         aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white" id="passageModalLabel">
                        <i class="fa fa-arrow-up mr-2"></i>Passage en année supérieure
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Fermer">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="passage-modal-body-confirm">
                        <p>Périmètre : <strong id="passage-scope-label"></strong></p>
                        <p class="font-w600 mb-2">Cette action va, pour chaque classe du périmètre choisi :</p>
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fa fa-check-circle text-success mr-3"></i>
                                Promouvoir au niveau supérieur les élèves ayant une moyenne annuelle ≥ 10/20
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fa fa-redo text-warning mr-3"></i>
                                Faire redoubler (même niveau) les élèves ayant une moyenne &lt; 10/20
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fa fa-graduation-cap text-primary mr-3"></i>
                                Inscrire automatiquement au cycle suivant les élèves terminant le dernier niveau
                                d'un cycle (ex. CM2 → 6ème)
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fa fa-certificate text-info mr-3"></i>
                                Compter comme diplômés les élèves terminant un cycle sans cycle suivant (ex.
                                Terminale)
                            </li>
                        </ul>
                        <div class="alert alert-warning mb-0">
                            <i class="fa fa-exclamation-triangle mr-2"></i>
                            Les classes sont traitées une par une avec la progression affichée en temps réel.
                            L'opération est sûre à relancer : les élèves déjà traités ne sont pas dupliqués.
                        </div>
                    </div>

                    <div id="passage-modal-body-progress" class="d-none">
                        <div class="progress mb-3" style="height: 24px;">
                            <div id="passage-progress-bar"
                                 class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                 role="progressbar" style="width: 0%;">0%</div>
                        </div>
                        <div id="passage-progress-log" class="border rounded p-2 bg-body-light small"
                             style="max-height: 250px; overflow-y: auto;"></div>
                    </div>

                    <div id="passage-modal-body-recap" class="d-none">
                        <div id="passage-recap-content"></div>
                    </div>
                </div>
                <div class="modal-footer" id="passage-modal-footer-confirm">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="button" id="passage-confirm-btn" class="btn btn-success">
                        <i class="fa fa-play mr-1"></i> Confirmer et démarrer
                    </button>
                </div>
                <div class="modal-footer d-none" id="passage-modal-footer-recap">
                    <button type="button" id="passage-close-btn" class="btn btn-primary" data-dismiss="modal">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            var cycleSelect = document.getElementById('cycle_id');
            var startBtn = document.getElementById('passage-start-btn');
            var confirmBtn = document.getElementById('passage-confirm-btn');
            var bodyConfirm = document.getElementById('passage-modal-body-confirm');
            var bodyProgress = document.getElementById('passage-modal-body-progress');
            var bodyRecap = document.getElementById('passage-modal-body-recap');
            var footerConfirm = document.getElementById('passage-modal-footer-confirm');
            var footerRecap = document.getElementById('passage-modal-footer-recap');
            var progressBar = document.getElementById('passage-progress-bar');
            var progressLog = document.getElementById('passage-progress-log');
            var recapContent = document.getElementById('passage-recap-content');
            var scopeLabel = document.getElementById('passage-scope-label');
            var planUrl = @json(route('passage.plan'));

            function getCycleId() {
                return cycleSelect ? cycleSelect.value : '';
            }

            function getCycleLabel() {
                if (cycleSelect) {
                    return cycleSelect.options[cycleSelect.selectedIndex].text;
                }
                var fixed = document.getElementById('passage-fixed-cycle');
                return fixed ? fixed.textContent.trim() : 'Tous les cycles';
            }

            function resetModal() {
                bodyConfirm.classList.remove('d-none');
                bodyProgress.classList.add('d-none');
                bodyRecap.classList.add('d-none');
                footerConfirm.classList.remove('d-none');
                footerRecap.classList.add('d-none');
                confirmBtn.disabled = false;
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                progressLog.innerHTML = '';
                recapContent.innerHTML = '';
            }

            function appendLog(text, cls) {
                var line = document.createElement('div');
                line.className = cls || 'text-muted';
                line.textContent = text;
                progressLog.appendChild(line);
                progressLog.scrollTop = progressLog.scrollHeight;
            }

            function renderRecap(parCycle, totals) {
                var html = '';
                Object.keys(parCycle).forEach(function (cycleNom) {
                    var classesDuCycle = parCycle[cycleNom];
                    var sousTotal = { passes: 0, redoublants: 0, diplomes: 0, erreurs: 0 };
                    var rows = '';
                    Object.keys(classesDuCycle).forEach(function (classeNom) {
                        var s = classesDuCycle[classeNom];
                        sousTotal.passes += s.passes;
                        sousTotal.redoublants += s.redoublants;
                        sousTotal.diplomes += s.diplomes;
                        sousTotal.erreurs += s.erreurs;
                        rows += '<tr><td>' + classeNom + '</td><td class="text-center">' + s.passes +
                            '</td><td class="text-center">' + s.redoublants + '</td><td class="text-center">' +
                            s.diplomes + '</td><td class="text-center">' + s.erreurs + '</td></tr>';
                    });
                    html += '<h6 class="font-w600 mt-3">' + cycleNom + '</h6>' +
                        '<div class="table-responsive"><table class="table table-sm table-bordered">' +
                        '<thead><tr><th>Classe</th><th class="text-center">Passés</th>' +
                        '<th class="text-center">Redoublants</th><th class="text-center">Diplômés</th>' +
                        '<th class="text-center">Erreurs</th></tr></thead><tbody>' + rows +
                        '<tr class="font-w600 bg-body-light"><td>Sous-total</td><td class="text-center">' +
                        sousTotal.passes + '</td><td class="text-center">' + sousTotal.redoublants +
                        '</td><td class="text-center">' + sousTotal.diplomes + '</td><td class="text-center">' +
                        sousTotal.erreurs + '</td></tr></tbody></table></div>';
                });
                html += '<hr><div class="d-flex justify-content-between font-w700 font-size-h6">' +
                    '<span>Total général</span><span>' + totals.passes + ' passé(s) · ' +
                    totals.redoublants + ' redoublant(s) · ' + totals.diplomes + ' diplômé(s)' +
                    (totals.erreurs ? ' · ' + totals.erreurs + ' erreur(s)' : '') + '</span></div>';
                recapContent.innerHTML = html;
            }

            if (startBtn) {
                startBtn.addEventListener('click', function () {
                    scopeLabel.textContent = getCycleLabel();
                    resetModal();
                });
            }

            confirmBtn.addEventListener('click', async function () {
                confirmBtn.disabled = true;
                bodyConfirm.classList.add('d-none');
                bodyProgress.classList.remove('d-none');
                footerConfirm.classList.add('d-none');

                var cycleId = getCycleId();
                var url = planUrl + (cycleId ? ('?cycle_id=' + encodeURIComponent(cycleId)) : '');

                var planResponse;
                try {
                    var res = await fetch(url, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' },
                    });
                    planResponse = await res.json();
                } catch (e) {
                    alert("Erreur réseau lors de la préparation du passage.");
                    resetModal();
                    return;
                }

                if (!planResponse.success) {
                    alert(planResponse.message);
                    resetModal();
                    return;
                }

                var classes = planResponse.classes;
                if (classes.length === 0) {
                    appendLog('Aucune classe à traiter pour ce périmètre.');
                }

                var totals = { passes: 0, redoublants: 0, diplomes: 0, erreurs: 0 };
                var parCycle = {};

                for (var i = 0; i < classes.length; i++) {
                    var c = classes[i];
                    appendLog('Traitement de la classe : ' + c.nom + ' (' + c.promotion + ' — ' + c.cycle + ')...');

                    var result;
                    try {
                        var execRes = await fetch('/passage/classe/' + c.id + '/executer', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });
                        result = await execRes.json();
                    } catch (e) {
                        appendLog('Erreur réseau pour la classe ' + c.nom + '.', 'text-danger');
                        continue;
                    }

                    if (!result.success) {
                        appendLog('Erreur : ' + result.message, 'text-danger');
                        continue;
                    }

                    appendLog(
                        'Classe traitée : ' + c.nom + ' (' + c.cycle + ') — ' + result.nb_passes +
                        ' passé(s), ' + result.nb_redoublants + ' redoublant(s), ' + result.nb_diplomes +
                        ' diplômé(s)' + (result.nb_erreurs ? ', ' + result.nb_erreurs + ' erreur(s)' : '') + '.',
                        result.nb_erreurs ? 'text-warning' : 'text-success'
                    );

                    totals.passes += result.nb_passes;
                    totals.redoublants += result.nb_redoublants;
                    totals.diplomes += result.nb_diplomes;
                    totals.erreurs += result.nb_erreurs;

                    if (!parCycle[c.cycle]) {
                        parCycle[c.cycle] = {};
                    }
                    parCycle[c.cycle][c.nom] = {
                        passes: result.nb_passes,
                        redoublants: result.nb_redoublants,
                        diplomes: result.nb_diplomes,
                        erreurs: result.nb_erreurs,
                    };

                    var pct = Math.round(((i + 1) / classes.length) * 100);
                    progressBar.style.width = pct + '%';
                    progressBar.textContent = pct + '%';
                }

                renderRecap(parCycle, totals);
                bodyProgress.classList.add('d-none');
                bodyRecap.classList.remove('d-none');
                footerRecap.classList.remove('d-none');
            });

            var closeBtn = document.getElementById('passage-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    location.reload();
                });
            }
        });
    </script>
@endsection
