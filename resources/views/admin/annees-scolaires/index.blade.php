@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    <i class="fa fa-calendar-alt mr-2"></i>Années Scolaires
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Années Scolaires</a></li>
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
                <h3 class="block-title"><i class="fa fa-star mr-2"></i>Année scolaire en cours</h3>
            </div>
            <div class="block-content">
                @if($currentAnneeScolaire)
                    <span class="badge badge-success" style="font-size: 1rem;">{{ $currentAnneeScolaire->annee }}</span>
                @else
                    <span class="text-danger">Aucune année scolaire courante définie.</span>
                @endif
            </div>
        </div>

        <div class="block block-rounded pb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-forward mr-2"></i>Générer l'année scolaire suivante</h3>
            </div>
            <div class="block-content">
                @if(!$currentAnneeScolaire)
                    <p class="text-danger mb-0">
                        Impossible de générer une nouvelle année : aucune année courante n'est définie.
                    </p>
                @elseif($nextYearExists)
                    <p class="text-muted mb-0">
                        L'année scolaire <strong>{{ $nextLabel }}</strong> existe déjà — rien à générer.
                        (Sécurité : cette page ne recréera jamais deux fois la même année, y compris si la
                        tâche automatique de nuit l'a déjà créée.)
                    </p>
                @else
                    <p class="mb-3">
                        Créer l'année scolaire <strong>{{ $nextLabel }}</strong> à partir de
                        {{ $currentAnneeScolaire->annee }} : promotions, classes, professeurs assignés,
                        frais et cours seront repris automatiquement pour chaque cycle.
                    </p>
                    <button type="button" class="btn btn-success" data-toggle="modal"
                            data-target="#genererAnneeModal">
                        <i class="fa fa-play mr-1"></i> Générer {{ $nextLabel }}
                    </button>

                    <div class="modal fade" id="genererAnneeModal" tabindex="-1" role="dialog"
                         aria-labelledby="genererAnneeModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <form action="{{ route('annees-scolaires.generer') }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-success">
                                        <h5 class="modal-title text-white" id="genererAnneeModalLabel">
                                            <i class="fa fa-calendar-plus mr-2"></i>Générer l'année scolaire
                                        </h5>
                                        <button type="button" class="close text-white" data-dismiss="modal"
                                                aria-label="Fermer">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex align-items-center justify-content-center mb-4">
                                            <span class="badge badge-secondary"
                                                  style="font-size: 1rem;">{{ $currentAnneeScolaire->annee }}</span>
                                            <i class="fa fa-long-arrow-alt-right mx-3 text-muted"></i>
                                            <span class="badge badge-success"
                                                  style="font-size: 1rem;">{{ $nextLabel }}</span>
                                        </div>

                                        <p class="font-w600 mb-2">Cette action va, pour chaque cycle :</p>
                                        <ul class="list-group mb-3">
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fa fa-sitemap text-primary mr-3"></i>
                                                Créer les promotions, trimestres et une classe par défaut
                                            </li>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fa fa-chalkboard-teacher text-primary mr-3"></i>
                                                Copier les affectations de professeurs (titulaires de classe
                                                et professeurs par cours)
                                            </li>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fa fa-money-bill-wave text-primary mr-3"></i>
                                                Copier les configurations de frais et leurs tranches
                                                (échéances décalées d'un an)
                                            </li>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fa fa-award text-primary mr-3"></i>
                                                Copier les sessions d'examen (dates décalées d'un an)
                                            </li>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fa fa-book text-primary mr-3"></i>
                                                Copier les matières et cours associés à chaque promotion
                                            </li>
                                        </ul>

                                        <div class="alert alert-warning d-flex align-items-center mb-0"
                                             role="alert">
                                            <i class="fa fa-exclamation-triangle mr-2"></i>
                                            <div>
                                                Le <strong>passage des élèves</strong> à la classe supérieure
                                                n'est pas inclus — cette opération se fait séparément.
                                                L'année créée ne deviendra pas automatiquement l'année
                                                courante : il faudra l'<strong>activer manuellement</strong>
                                                depuis cette page une fois le passage effectué.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-check mr-1"></i> Confirmer et générer
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="block block-rounded pb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-toggle-on mr-2"></i>Activer l'année scolaire suivante</h3>
            </div>
            <div class="block-content">
                @if(!$nextYearExists)
                    <p class="text-muted mb-0">
                        Aucune année scolaire suivante n'a encore été générée. Générez d'abord
                        {{ $nextLabel }} ci-dessus.
                    </p>
                @elseif($nextAnneeScolaire->courant)
                    <p class="text-muted mb-0">
                        L'année scolaire {{ $nextAnneeScolaire->annee }} est déjà l'année courante.
                    </p>
                @else
                    <p class="mb-3">
                        L'année scolaire <strong>{{ $nextAnneeScolaire->annee }}</strong> a été générée mais
                        n'est <strong>pas encore active</strong> : les nouvelles inscriptions, paiements et
                        consultations par défaut continuent de pointer vers
                        <strong>{{ $currentAnneeScolaire->annee }}</strong> tant qu'elle ne l'est pas.
                    </p>
                    <button type="button" class="btn btn-warning" data-toggle="modal"
                            data-target="#activerAnneeModal">
                        <i class="fa fa-toggle-on mr-1"></i> Activer {{ $nextAnneeScolaire->annee }}
                    </button>

                    <div class="modal fade" id="activerAnneeModal" tabindex="-1" role="dialog"
                         aria-labelledby="activerAnneeModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <form action="{{ route('annees-scolaires.activer', $nextAnneeScolaire) }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-warning">
                                        <h5 class="modal-title" id="activerAnneeModalLabel">
                                            <i class="fa fa-toggle-on mr-2"></i>Activer l'année scolaire
                                        </h5>
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Fermer">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex align-items-center justify-content-center mb-4">
                                            <span class="badge badge-secondary"
                                                  style="font-size: 1rem;">{{ $currentAnneeScolaire->annee }}</span>
                                            <i class="fa fa-long-arrow-alt-right mx-3 text-muted"></i>
                                            <span class="badge badge-warning"
                                                  style="font-size: 1rem;">{{ $nextAnneeScolaire->annee }}</span>
                                        </div>

                                        <div class="alert alert-warning d-flex align-items-center mb-0"
                                             role="alert">
                                            <i class="fa fa-exclamation-triangle mr-2"></i>
                                            <div>
                                                Vérifiez que le <strong>passage des élèves</strong> vers
                                                {{ $nextAnneeScolaire->annee }} a bien été effectué (menu
                                                « Passage année supérieure ») avant d'activer : une fois
                                                activée, cette année devient l'année par défaut pour tous
                                                les utilisateurs qui n'ont pas choisi une année manuellement.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fa fa-check mr-1"></i> Confirmer et activer
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="block block-rounded pb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-list mr-2"></i>Historique des années scolaires</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>Année</th>
                                <th class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($anneesScolaires as $annee)
                                <tr>
                                    <td>{{ $annee->annee }}</td>
                                    <td class="text-center">
                                        @if($annee->courant)
                                            <span class="badge badge-success">Courante</span>
                                        @elseif($nextAnneeScolaire && $annee->id === $nextAnneeScolaire->id)
                                            <span class="badge badge-info">En attente d'activation</span>
                                        @else
                                            <span class="badge badge-secondary">Archivée</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center h4 p-3">Aucune année scolaire</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
