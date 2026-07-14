@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2 text-center text-sm-left">
                <div class="flex-sm-fill">
                    <div class="d-flex justify-content-between">
                        <h1 class="h2 font-w700 mb-2">
                            CPL Mon Avenir
                        </h1>
                        <h2 class="font-w700 mb-2">
                            Annee Scolaire: <span class="text-primary">{{ $anneeCourante->annee }}</span>
                        </h2>
                    </div>
                    <h2 class="h6 font-w500 text-muted mb-0">
                        Bienvenue <a class="font-w600" href="javascript:void(0)">{{ Auth::user()->username }}</a>,
                        @if($viewType === 'admin')
                            vous avez acces a l'ensemble du systeme.
                        @elseif($viewType === 'directeur')
                            @if($cycle)
                                vous gerez le cycle <strong>{{ $cycle->nom }}</strong>.
                            @elseif($isDirecteurGeneral)
                                vous gerez <strong>l'ensemble des cycles</strong>.
                            @else
                                vous gerez le cycle <strong>Non defini</strong>.
                            @endif
                        @elseif($viewType === 'professeur')
                            voici un apercu de vos cours et eleves.
                        @elseif($viewType === 'comptable')
                            voici un resume de l'activite financiere.
                        @elseif($viewType === 'secretaire')
                            @if($cycle)
                                vous gerez le secretariat du cycle <strong>{{ $cycle->nom }}</strong>.
                            @elseif($isSecretaireGeneral)
                                vous gerez le secretariat de <strong>l'ensemble des cycles</strong>.
                            @else
                                vous gerez le secretariat du cycle <strong>Non defini</strong>.
                            @endif
                        @else
                            bienvenue sur la plateforme.
                        @endif
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        {{-- ======================== ADMIN DASHBOARD ======================== --}}
        @if($viewType === 'admin')
            {{-- Statistiques globales --}}
            <div class="row row-deck">
                <div class="col-sm-6 col-xl-3">
                    <div class="block block-rounded d-flex flex-column">
                        <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                            <dl class="mb-0">
                                <dt class="font-size-h2 font-w700">{{ $cycles->count() }}</dt>
                                <dd class="text-muted mb-0">Cycles</dd>
                            </dl>
                            <div class="item item-rounded bg-body">
                                <i class="fa fa-sitemap font-size-h3 text-primary"></i>
                            </div>
                        </div>
                        <div class="block-content block-content-full block-content-sm bg-body-light font-size-sm">
                            <a class="font-w500 d-flex align-items-center" href="{{ route('cycles.index') }}">
                                Gerer les cycles
                                <i class="fa fa-arrow-alt-circle-right ml-1 opacity-25 font-size-base"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="block block-rounded d-flex flex-column">
                        <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                            <dl class="mb-0">
                                <dt class="font-size-h2 font-w700">{{ $totalUsers }}</dt>
                                <dd class="text-muted mb-0">Utilisateurs</dd>
                            </dl>
                            <div class="item item-rounded bg-body">
                                <i class="fa fa-users-cog font-size-h3 text-success"></i>
                            </div>
                        </div>
                        <div class="block-content block-content-full block-content-sm bg-body-light font-size-sm">
                            <a class="font-w500 d-flex align-items-center" href="{{ route('users.index') }}">
                                Gerer les utilisateurs
                                <i class="fa fa-arrow-alt-circle-right ml-1 opacity-25 font-size-base"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="block block-rounded d-flex flex-column">
                        <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                            <dl class="mb-0">
                                <dt class="font-size-h2 font-w700">{{ number_format($totalEleves) }}</dt>
                                <dd class="text-muted mb-0">Eleves inscrits</dd>
                            </dl>
                            <div class="item item-rounded bg-body">
                                <i class="fa fa-user-graduate font-size-h3 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="block block-rounded d-flex flex-column">
                        <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                            <dl class="mb-0">
                                <dt class="font-size-h2 font-w700">{{ $totalProfesseurs }}</dt>
                                <dd class="text-muted mb-0">Professeurs</dd>
                            </dl>
                            <div class="item item-rounded bg-body">
                                <i class="fa fa-chalkboard-teacher font-size-h3 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Eleves par cycle --}}
            <div class="row">
                <div class="col-lg-8">
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Repartition des eleves par cycle</h3>
                        </div>
                        <div class="block-content">
                            <div class="row text-center">
                                @foreach($elevesParCycle as $cycleName => $count)
                                    <div class="col-md-3 col-6">
                                        <div class="py-3">
                                            <div class="font-size-h1 font-w700 text-primary">{{ $count }}</div>
                                            <div class="font-size-sm text-muted">{{ $cycleName }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Dernieres inscriptions</h3>
                        </div>
                        <div class="block-content">
                            <ul class="list-group list-group-flush">
                                @forelse($dernieresInscriptions as $eleve)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>{{ $eleve->nom }} {{ $eleve->prenom }}</span>
                                        <span class="badge badge-primary">{{ $eleve->created_at->diffForHumans() }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">Aucune inscription recente</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Acces rapides Admin --}}
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Acces rapides</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-3">
                            <a href="{{ route('cycles.index') }}" class="btn btn-outline-primary btn-block py-3">
                                <i class="fa fa-sitemap fa-2x mb-2"></i><br>
                                Gerer les cycles
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <a href="{{ route('users.index') }}" class="btn btn-outline-success btn-block py-3">
                                <i class="fa fa-users-cog fa-2x mb-2"></i><br>
                                Gerer les utilisateurs
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <a href="{{ route('annees-scolaires.index') }}" class="btn btn-outline-info btn-block py-3">
                                <i class="fa fa-calendar-alt fa-2x mb-2"></i><br>
                                Generer annee suivante
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <a href="{{ route('logs.index') }}" class="btn btn-outline-warning btn-block py-3">
                                <i class="fa fa-bug fa-2x mb-2"></i><br>
                                Logs &amp; erreurs
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        {{-- ======================== DIRECTEUR DASHBOARD ======================== --}}
        @elseif($viewType === 'directeur')
            @if($cycle || $isDirecteurGeneral)
                @if($isDirecteurGeneral)
                    {{-- Filtre par cycle --}}
                    <div class="block block-rounded">
                        <div class="block-content block-content-full">
                            <form method="GET" action="{{ route('dashboard') }}" class="form-inline">
                                <label class="mr-2">Filtrer par cycle:</label>
                                <select name="cycle_id" class="form-control mr-2" onchange="this.form.submit()">
                                    <option value="">Tous les cycles</option>
                                    @foreach($cycles as $c)
                                        <option value="{{ $c->id }}" {{ $cycle && $cycle->id == $c->id ? 'selected' : '' }}>
                                            {{ $c->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Statistiques du cycle --}}
                <div class="row row-deck">
                    <div class="col-sm-6 col-xl-4">
                        <div class="block block-rounded d-flex flex-column">
                            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                <dl class="mb-0">
                                    <dt class="font-size-h2 font-w700">{{ $totalEleves }}</dt>
                                    <dd class="text-muted mb-0">Eleves - {{ $cycle->nom ?? 'Tous les cycles' }}</dd>
                                </dl>
                                <div class="item item-rounded bg-body">
                                    <i class="fa fa-user-graduate font-size-h3 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="block block-rounded d-flex flex-column">
                            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                <dl class="mb-0">
                                    <dt class="font-size-h2 font-w700">{{ $totalProfesseurs }}</dt>
                                    <dd class="text-muted mb-0">Professeurs</dd>
                                </dl>
                                <div class="item item-rounded bg-body">
                                    <i class="fa fa-chalkboard-teacher font-size-h3 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="block block-rounded d-flex flex-column">
                            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                <dl class="mb-0">
                                    <dt class="font-size-h2 font-w700">{{ $totalClasses }}</dt>
                                    <dd class="text-muted mb-0">Classes</dd>
                                </dl>
                                <div class="item item-rounded bg-body">
                                    <i class="fa fa-school font-size-h3 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Donnees financieres du cycle --}}
                @if($financeStats)
                    <div class="row row-deck">
                        <div class="col-sm-6 col-xl-3">
                            <div class="block block-rounded d-flex flex-column">
                                <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                    <dl class="mb-0">
                                        <dt class="font-size-h4 font-w700">{{ number_format($financeStats['total_frais_attendu'], 0, ',', ' ') }}</dt>
                                        <dd class="text-muted mb-0">Frais attendus (FCFA)</dd>
                                    </dl>
                                    <div class="item item-rounded bg-body">
                                        <i class="fa fa-money-bill font-size-h3 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="block block-rounded d-flex flex-column">
                                <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                    <dl class="mb-0">
                                        <dt class="font-size-h4 font-w700">{{ number_format($financeStats['total_paiements'], 0, ',', ' ') }}</dt>
                                        <dd class="text-muted mb-0">Total encaisse (FCFA)</dd>
                                    </dl>
                                    <div class="item item-rounded bg-body">
                                        <i class="fa fa-check-circle font-size-h3 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="block block-rounded d-flex flex-column">
                                <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                    <dl class="mb-0">
                                        <dt class="font-size-h4 font-w700">{{ $financeStats['taux_recouvrement'] }}%</dt>
                                        <dd class="text-muted mb-0">Taux recouvrement</dd>
                                    </dl>
                                    <div class="item item-rounded bg-body">
                                        <i class="fa fa-percentage font-size-h3 text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="block block-rounded d-flex flex-column">
                                <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                    <dl class="mb-0">
                                        <dt class="font-size-h4 font-w700">{{ number_format($financeStats['paiements_aujourd_hui'], 0, ',', ' ') }}</dt>
                                        <dd class="text-muted mb-0">Encaisse aujourd'hui</dd>
                                    </dl>
                                    <div class="item item-rounded bg-body">
                                        <i class="fa fa-calendar-day font-size-h3 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="col-lg-8">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">Statut des paiements - {{ $cycle->nom ?? 'Tous les cycles' }}</h3>
                            </div>
                            <div class="block-content">
                                @if($financeStats)
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="py-3">
                                                <div class="font-size-h1 font-w700 text-success">{{ $financeStats['eleves_soldes'] }}</div>
                                                <div class="font-size-sm text-muted">Soldes</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="py-3">
                                                <div class="font-size-h1 font-w700 text-warning">{{ $financeStats['eleves_partiels'] }}</div>
                                                <div class="font-size-sm text-muted">Partiels</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="py-3">
                                                <div class="font-size-h1 font-w700 text-danger">{{ $financeStats['eleves_impayes'] }}</div>
                                                <div class="font-size-sm text-muted">Impayes</div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted mb-0">Aucune donnee financiere disponible.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">Dernieres inscriptions</h3>
                            </div>
                            <div class="block-content">
                                <ul class="list-group list-group-flush">
                                    @forelse($dernieresInscriptions as $eleve)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>{{ $eleve->nom }} {{ $eleve->prenom }}</span>
                                            <span class="badge badge-primary">{{ $eleve->created_at->diffForHumans() }}</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">Aucune inscription recente</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle mr-2"></i>
                    Aucun cycle n'est associe a votre role de directeur. Veuillez contacter l'administrateur.
                </div>
            @endif

        {{-- ======================== PROFESSEUR DASHBOARD ======================== --}}
        @elseif($viewType === 'professeur')
            @if($professeur)
                {{-- Statistiques du professeur --}}
                <div class="row row-deck">
                    <div class="col-sm-6 col-xl-4">
                        <div class="block block-rounded d-flex flex-column">
                            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                <dl class="mb-0">
                                    <dt class="font-size-h2 font-w700">{{ $cours->count() }}</dt>
                                    <dd class="text-muted mb-0">Cours assignes</dd>
                                </dl>
                                <div class="item item-rounded bg-body">
                                    <i class="fa fa-book font-size-h3 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="block block-rounded d-flex flex-column">
                            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                <dl class="mb-0">
                                    <dt class="font-size-h2 font-w700">{{ $classes->count() }}</dt>
                                    <dd class="text-muted mb-0">Classes</dd>
                                </dl>
                                <div class="item item-rounded bg-body">
                                    <i class="fa fa-school font-size-h3 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="block block-rounded d-flex flex-column">
                            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                <dl class="mb-0">
                                    <dt class="font-size-h2 font-w700">{{ $totalEleves }}</dt>
                                    <dd class="text-muted mb-0">Eleves total</dd>
                                </dl>
                                <div class="item item-rounded bg-body">
                                    <i class="fa fa-users font-size-h3 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Liste des cours --}}
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Mes cours</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Matiere</th>
                                        <th>Classe</th>
                                        <th class="text-center">Coefficient</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cours as $c)
                                        <tr>
                                            <td>{{ $c->matiere->intitule }}</td>
                                            <td>{{ $c->classe->nom }}</td>
                                            <td class="text-center">{{ $c->coefficient }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('cours.show', $c) }}" class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i> Voir
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Aucun cours assigne</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Acces rapides Professeur --}}
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Acces rapides</h3>
                    </div>
                    <div class="block-content">
                        <div class="row">
                            @foreach($classes as $classe)
                                <div class="col-md-4 col-6 mb-3">
                                    <a href="{{ route('classe.index', $classe) }}" class="btn btn-outline-primary btn-block py-3">
                                        <i class="fa fa-users fa-2x mb-2"></i><br>
                                        {{ $classe->nom }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle mr-2"></i>
                    Votre compte utilisateur n'est pas lie a un profil professeur. Veuillez contacter l'administrateur.
                </div>
            @endif

        {{-- ======================== COMPTABLE DASHBOARD ======================== --}}
        @elseif($viewType === 'comptable')
            {{-- Statistiques financieres --}}
            <div class="row row-deck">
                <div class="col-sm-6 col-xl-3">
                    <div class="block block-rounded d-flex flex-column">
                        <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                            <dl class="mb-0">
                                <dt class="font-size-h2 font-w700">{{ number_format($paiementsJour, 0, ',', ' ') }}</dt>
                                <dd class="text-muted mb-0">FCFA aujourd'hui</dd>
                            </dl>
                            <div class="item item-rounded bg-body">
                                <i class="fa fa-calendar-day font-size-h3 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="block block-rounded d-flex flex-column">
                        <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                            <dl class="mb-0">
                                <dt class="font-size-h2 font-w700">{{ number_format($paiementsSemaine, 0, ',', ' ') }}</dt>
                                <dd class="text-muted mb-0">FCFA cette semaine</dd>
                            </dl>
                            <div class="item item-rounded bg-body">
                                <i class="fa fa-calendar-week font-size-h3 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="block block-rounded d-flex flex-column">
                        <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                            <dl class="mb-0">
                                <dt class="font-size-h2 font-w700">{{ number_format($paiementsMois, 0, ',', ' ') }}</dt>
                                <dd class="text-muted mb-0">FCFA ce mois</dd>
                            </dl>
                            <div class="item item-rounded bg-body">
                                <i class="fa fa-calendar-alt font-size-h3 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="block block-rounded d-flex flex-column">
                        <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                            <dl class="mb-0">
                                <dt class="font-size-h2 font-w700">{{ $nbPaiementsJour }}</dt>
                                <dd class="text-muted mb-0">Paiements aujourd'hui</dd>
                            </dl>
                            <div class="item item-rounded bg-body">
                                <i class="fa fa-receipt font-size-h3 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Derniers paiements --}}
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Derniers paiements</h3>
                    <a href="{{ route('comptabilite.dashboard') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-chart-line mr-1"></i> Dashboard complet
                    </a>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Eleve</th>
                                    <th>Type de frais</th>
                                    <th class="text-center">Montant</th>
                                    <th class="text-center">Recu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($derniersPaiements as $paiement)
                                    <tr>
                                        <td>{{ $paiement->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('comptabilite.eleve.fiche', $paiement->eleve) }}">
                                                {{ $paiement->eleve->nom }} {{ $paiement->eleve->prenom }}
                                            </a>
                                        </td>
                                        <td>{{ $paiement->configurationFrais->typeFrais->nom ?? '-' }}</td>
                                        <td class="text-center font-w600">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                                        <td class="text-center">
                                            @if($paiement->recu)
                                                <a href="{{ route('recus.pdf', $paiement->recu) }}" class="btn btn-sm btn-info" target="_blank">
                                                    <i class="fa fa-file-pdf"></i>
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Aucun paiement enregistre</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Acces rapides Comptable --}}
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Acces rapides</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-3">
                            <a href="{{ route('comptabilite.eleves') }}" class="btn btn-outline-primary btn-block py-3">
                                <i class="fa fa-users fa-2x mb-2"></i><br>
                                Paiements eleves
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <a href="{{ route('recus.index') }}" class="btn btn-outline-success btn-block py-3">
                                <i class="fa fa-receipt fa-2x mb-2"></i><br>
                                Recus
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <a href="{{ route('comptabilite.rapports') }}" class="btn btn-outline-info btn-block py-3">
                                <i class="fa fa-chart-bar fa-2x mb-2"></i><br>
                                Rapports
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <a href="{{ route('comptabilite.retard') }}" class="btn btn-outline-warning btn-block py-3">
                                <i class="fa fa-exclamation-triangle fa-2x mb-2"></i><br>
                                Eleves en retard
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        {{-- ======================== SECRETAIRE DASHBOARD ======================== --}}
        @elseif($viewType === 'secretaire')
            @if($cycle || $isSecretaireGeneral)
                {{-- Statistiques du cycle --}}
                <div class="row row-deck">
                    <div class="col-sm-6 col-xl-6">
                        <div class="block block-rounded d-flex flex-column">
                            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                <dl class="mb-0">
                                    <dt class="font-size-h2 font-w700">{{ $totalEleves }}</dt>
                                    <dd class="text-muted mb-0">Eleves - {{ $cycle->nom ?? 'Tous les cycles' }}</dd>
                                </dl>
                                <div class="item item-rounded bg-body">
                                    <i class="fa fa-user-graduate font-size-h3 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-6">
                        <div class="block block-rounded d-flex flex-column">
                            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                                <dl class="mb-0">
                                    <dt class="font-size-h2 font-w700">{{ $totalClasses }}</dt>
                                    <dd class="text-muted mb-0">Classes</dd>
                                </dl>
                                <div class="item item-rounded bg-body">
                                    <i class="fa fa-school font-size-h3 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">Acces rapides</h3>
                            </div>
                            <div class="block-content">
                                <div class="row">
                                    <div class="col-md-4 col-6 mb-3">
                                        <a href="{{ route('eleve.create') }}" class="btn btn-outline-primary btn-block py-3">
                                            <i class="fa fa-user-plus fa-2x mb-2"></i><br>
                                            Ajouter un eleve
                                        </a>
                                    </div>
                                    <div class="col-md-4 col-6 mb-3">
                                        <a href="{{ route('sessions.index') }}" class="btn btn-outline-info btn-block py-3">
                                            <i class="fa fa-calendar-check fa-2x mb-2"></i><br>
                                            Sessions d'examen
                                        </a>
                                    </div>
                                    <div class="col-md-4 col-6 mb-3">
                                        <a href="{{ route('bulletin-config.index') }}" class="btn btn-outline-success btn-block py-3">
                                            <i class="fa fa-file-alt fa-2x mb-2"></i><br>
                                            Configuration bulletins
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">Dernieres inscriptions</h3>
                            </div>
                            <div class="block-content">
                                <ul class="list-group list-group-flush">
                                    @forelse($dernieresInscriptions as $eleve)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>{{ $eleve->nom }} {{ $eleve->prenom }}</span>
                                            <span class="badge badge-primary">{{ $eleve->created_at->diffForHumans() }}</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">Aucune inscription recente</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle mr-2"></i>
                    Aucun cycle n'est associe a votre role de secretaire. Veuillez contacter l'administrateur.
                </div>
            @endif

        {{-- ======================== DEFAULT DASHBOARD ======================== --}}
        @else
            <div class="row row-deck">
                <div class="col-12">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full text-center py-5">
                            <i class="fa fa-user-circle fa-4x text-muted mb-3"></i>
                            <h3>Bienvenue sur CPL Mon Avenir</h3>
                            <p class="text-muted">
                                Votre compte n'a pas de role specifique assigne.<br>
                                Veuillez contacter l'administrateur pour obtenir les acces necessaires.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
