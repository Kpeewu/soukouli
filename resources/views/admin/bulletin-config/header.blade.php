@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">Disposition de l'en-tête du bulletin</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="{{ route('bulletin-config.index') }}">Configuration des bulletins</a></li>
                        <li class="breadcrumb-item">Disposition en-tête</li>
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

        @error('positions')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <div class="alert alert-info">
            <i class="fa fa-info-circle mr-2"></i>
            <strong>Information :</strong> glissez-déposez librement les éléments dans la zone grisée pour
            définir leur position dans l'en-tête du bulletin. Le reste du document (tableau de notes, moyennes,
            signatures) est affiché tel qu'il apparaîtra réellement, à titre d'aperçu, mais n'est pas modifiable
            ici. Cette disposition s'applique à <strong>tous les bulletins</strong> de l'établissement.
        </div>

        <div class="block block-rounded">
            <div class="block-content pb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <button type="button" id="btn-save-layout" class="btn btn-primary">
                            <i class="fa fa-save mr-1"></i> Enregistrer la disposition
                        </button>
                        <button type="button" class="btn btn-outline-danger"
                                onclick="if(confirm('Reinitialiser la disposition de l\'en-tete aux valeurs par defaut ?')) { document.getElementById('reset-form').submit(); }">
                            <i class="fa fa-undo mr-1"></i> Réinitialiser
                        </button>
                    </div>
                    <button type="button" id="btn-preview-pdf" class="btn btn-alt-secondary">
                        <i class="fa fa-file-pdf mr-1"></i> Aperçu PDF
                    </button>
                </div>

                <div style="overflow-x:auto;">
                    <div id="bulletin-preview" style="position:relative; width:{{ $canvasWidth }}px; border:1px solid #ccc; background:#fff; margin:0 auto;">
                        <div id="zone-entete" style="position:relative; height:{{ $headerHeight }}px; border-bottom:2px dashed #d5dadf; background:repeating-linear-gradient(45deg,#fafafa,#fafafa 10px,#fff 10px,#fff 20px);">
                            <div id="guide-v" style="position:absolute; top:0; bottom:0; width:0; border-left:1px solid #ff3b8d; display:none; pointer-events:none; z-index:20;"></div>
                            <div id="guide-h" style="position:absolute; left:0; right:0; height:0; border-top:1px solid #ff3b8d; display:none; pointer-events:none; z-index:20;"></div>
                            @foreach ($positions as $bloc => $pos)
                                <div class="bloc-entete" data-bloc="{{ $bloc }}"
                                     title="{{ $labels[$bloc] ?? $bloc }}"
                                     style="position:absolute; left:{{ $pos['x'] }}px; top:{{ $pos['y'] }}px; cursor:move; border:1px dashed transparent; padding:2px;">
                                    @include('pdf.bulletin.blocks.' . $bloc, ['eleve' => $eleve, 'classe' => $classe, 'trimestre' => $trimestre, 'school' => $school, 'logo' => $logo])
                                </div>
                            @endforeach
                        </div>

                        <div style="padding:8px;">
                            @include('pdf.bulletin.corps', [
                                'lignes' => $lignes,
                                'moyennes_trimestres' => $moyennes_trimestres,
                                'moyenne_lettre' => $moyenne_lettre,
                                'classe' => $classe,
                                'trimestre' => $trimestre,
                                'moyenne_annuelle' => $moyenne_annuelle,
                                'assiduite' => $assiduite,
                                'directeur' => $directeur,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="save-form" action="{{ route('bulletin-config.header.update') }}" method="POST" class="d-none">
            @csrf
            <div id="save-form-inputs"></div>
        </form>

        <form id="reset-form" action="{{ route('bulletin-config.header.reset') }}" method="POST" class="d-none">
            @csrf
        </form>

        <form id="preview-form" action="{{ route('bulletin-config.header.preview') }}" method="POST" target="_blank" class="d-none">
            @csrf
            <div id="preview-form-inputs"></div>
        </form>
    </div>

    <script>
        (function() {
            // Glisser-depose en JS natif (Pointer Events) : le layout charge jQuery
            // apres le contenu de la page, donc une dependance a jQuery/jQuery UI
            // ici echouerait silencieusement (jQuery pas encore defini a ce stade).
            var zone = document.getElementById('zone-entete');
            var guideV = document.getElementById('guide-v');
            var guideH = document.getElementById('guide-h');
            var SNAP_THRESHOLD = 6;

            // Calcule, pour le bloc en cours de deplacement, le point d'accroche le
            // plus proche (bord/centre du canevas, ou bord/centre d'un autre bloc)
            // sur chaque axe, et renvoie la position eventuellement "aimantee".
            function computeSnap(el, rawLeft, rawTop) {
                var w = el.offsetWidth, h = el.offsetHeight;
                var zoneW = zone.clientWidth, zoneH = zone.clientHeight;

                var xTargets = [0, zoneW / 2, zoneW];
                var yTargets = [0, zoneH / 2, zoneH];

                document.querySelectorAll('.bloc-entete').forEach(function(other) {
                    if (other === el) return;
                    var ol = parseInt(other.style.left, 10) || 0;
                    var ot = parseInt(other.style.top, 10) || 0;
                    var ow = other.offsetWidth, oh = other.offsetHeight;
                    xTargets.push(ol, ol + ow / 2, ol + ow);
                    yTargets.push(ot, ot + oh / 2, ot + oh);
                });

                var xEdges = { left: rawLeft, center: rawLeft + w / 2, right: rawLeft + w };
                var yEdges = { top: rawTop, center: rawTop + h / 2, bottom: rawTop + h };

                var snappedLeft = rawLeft, guideX = null, bestXDist = SNAP_THRESHOLD + 1;
                ['left', 'center', 'right'].forEach(function(edge) {
                    xTargets.forEach(function(target) {
                        var dist = Math.abs(xEdges[edge] - target);
                        if (dist < bestXDist) {
                            bestXDist = dist;
                            guideX = target;
                            snappedLeft = target - (edge === 'left' ? 0 : edge === 'center' ? w / 2 : w);
                        }
                    });
                });

                var snappedTop = rawTop, guideY = null, bestYDist = SNAP_THRESHOLD + 1;
                ['top', 'center', 'bottom'].forEach(function(edge) {
                    yTargets.forEach(function(target) {
                        var dist = Math.abs(yEdges[edge] - target);
                        if (dist < bestYDist) {
                            bestYDist = dist;
                            guideY = target;
                            snappedTop = target - (edge === 'top' ? 0 : edge === 'center' ? h / 2 : h);
                        }
                    });
                });

                return {
                    left: guideX !== null ? snappedLeft : rawLeft,
                    top: guideY !== null ? snappedTop : rawTop,
                    guideX: guideX,
                    guideY: guideY
                };
            }

            function showGuides(snap) {
                if (snap.guideX !== null) {
                    guideV.style.left = snap.guideX + 'px';
                    guideV.style.display = 'block';
                } else {
                    guideV.style.display = 'none';
                }
                if (snap.guideY !== null) {
                    guideH.style.top = snap.guideY + 'px';
                    guideH.style.display = 'block';
                } else {
                    guideH.style.display = 'none';
                }
            }

            function hideGuides() {
                guideV.style.display = 'none';
                guideH.style.display = 'none';
            }

            function makeDraggable(el) {
                el.addEventListener('pointerdown', function(e) {
                    e.preventDefault();
                    el.setPointerCapture(e.pointerId);

                    var startX = e.clientX;
                    var startY = e.clientY;
                    var initialLeft = parseInt(el.style.left, 10) || 0;
                    var initialTop = parseInt(el.style.top, 10) || 0;
                    var maxLeft = zone.clientWidth - el.offsetWidth;
                    var maxTop = zone.clientHeight - el.offsetHeight;

                    el.style.borderColor = '#4184f3';
                    el.style.zIndex = 10;

                    function onMove(e) {
                        var newLeft = initialLeft + (e.clientX - startX);
                        var newTop = initialTop + (e.clientY - startY);
                        newLeft = Math.max(0, Math.min(newLeft, Math.max(0, maxLeft)));
                        newTop = Math.max(0, Math.min(newTop, Math.max(0, maxTop)));

                        var snap = computeSnap(el, newLeft, newTop);
                        newLeft = Math.max(0, Math.min(snap.left, Math.max(0, maxLeft)));
                        newTop = Math.max(0, Math.min(snap.top, Math.max(0, maxTop)));
                        showGuides(snap);

                        el.style.left = newLeft + 'px';
                        el.style.top = newTop + 'px';
                    }

                    function onUp() {
                        el.style.borderColor = 'transparent';
                        el.style.zIndex = '';
                        hideGuides();
                        el.removeEventListener('pointermove', onMove);
                        el.removeEventListener('pointerup', onUp);
                    }

                    el.addEventListener('pointermove', onMove);
                    el.addEventListener('pointerup', onUp);
                });
            }

            document.querySelectorAll('.bloc-entete').forEach(makeDraggable);

            function collectPositions() {
                var positions = {};
                document.querySelectorAll('.bloc-entete').forEach(function(item) {
                    positions[item.dataset.bloc] = {
                        x: parseInt(item.style.left, 10) || 0,
                        y: parseInt(item.style.top, 10) || 0
                    };
                });
                return positions;
            }

            function fillHiddenInputs(containerId, positions) {
                var container = document.getElementById(containerId);
                container.innerHTML = '';
                Object.keys(positions).forEach(function(bloc) {
                    ['x', 'y'].forEach(function(axis) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'positions[' + bloc + '][' + axis + ']';
                        input.value = positions[bloc][axis];
                        container.appendChild(input);
                    });
                });
            }

            document.getElementById('btn-save-layout').addEventListener('click', function() {
                fillHiddenInputs('save-form-inputs', collectPositions());
                document.getElementById('save-form').submit();
            });

            document.getElementById('btn-preview-pdf').addEventListener('click', function() {
                fillHiddenInputs('preview-form-inputs', collectPositions());
                document.getElementById('preview-form').submit();
            });
        })();
    </script>
@endsection
