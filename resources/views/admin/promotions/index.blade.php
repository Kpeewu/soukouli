@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Gestion des Promotions (Niveaux)
                    @if($anneeCourante)
                        <span class="badge badge-primary ml-2">{{ $anneeCourante->annee }}</span>
                    @endif
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Promotions</a></li>
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

        <div class="alert alert-info">
            <i class="fa fa-info-circle mr-2"></i>
            <strong>Information:</strong> Les promotions sont créées automatiquement lors de la creation d'une nouvelle annee scolaire.
            Vous pouvez modifier le type de periode (trimestre ou semestre) pour les cycles qui le supportent.
        </div>

        @foreach($cycles as $cycle)
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-graduation-cap mr-2"></i>{{ $cycle->nom }}
                        @if($cycle->supports_semestre)
                            <span class="badge badge-info ml-2">Supporte les semestres</span>
                        @endif
                    </h3>
                </div>
                <div class="block-content">
                    @if($cycle->promotions->isEmpty())
                        <p class="text-muted text-center py-3">Aucune promotion pour ce cycle</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 60px;">Ordre</th>
                                        <th>Niveau</th>
                                        <th class="text-center">Type de periode</th>
                                        <th class="text-center">Periodes</th>
                                        <th class="text-center">Classes</th>
                                        <th class="text-center">Eleves</th>
                                        <th class="text-center">Examen officiel</th>
                                        @if($cycle->supports_semestre)
                                            <th class="text-center" style="width: 200px;">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cycle->promotions as $index => $promotion)
                                        <tr>
                                            <td class="text-center text-primary font-w700">{{ $index + 1 }}</td>
                                            <td class="font-w600">{{ $promotion->nom }}</td>
                                            <td class="text-center">
                                                @if($promotion->type_periode === 'semestre')
                                                    <span class="badge badge-info">Semestre</span>
                                                @else
                                                    <span class="badge badge-secondary">Trimestre</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-primary">{{ $promotion->trimestres->count() }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-secondary">{{ $promotion->classes->count() }}</span>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $totalEleves = $promotion->classes->sum(function($classe) {
                                                        return $classe->eleves->count();
                                                    });
                                                @endphp
                                                <span class="badge badge-secondary">{{ $totalEleves }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($promotion->a_examen_officiel && $promotion->examenOfficiel)
                                                    <span class="badge badge-warning">{{ $promotion->examenOfficiel->code }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            @if($cycle->supports_semestre)
                                                <td class="text-center">
                                                    <form action="{{ route('promotions.updatePeriode', $promotion) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="type_periode" class="form-control form-control-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                                            <option value="trimestre" {{ $promotion->type_periode === 'trimestre' ? 'selected' : '' }}>Trimestre</option>
                                                            <option value="semestre" {{ $promotion->type_periode === 'semestre' ? 'selected' : '' }}>Semestre</option>
                                                        </select>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
