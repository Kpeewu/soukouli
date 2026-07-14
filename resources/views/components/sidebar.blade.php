<nav id="sidebar" aria-label="Main Navigation">
    <!-- Side Header -->
    <div class="content-header bg-white-5">
        <!-- Logo -->
        <a class="font-w600 text-dual" href="{{ route('dashboard') }}">
            <span class="smini-visible">
                <i class="fa fa-circle-notch text-primary"></i>
            </span>
            <span class="smini-hide font-size-h5 tracking-wider">
                    {{ $schoolSettings['school_name'] ?? 'Soukouli' }}
            </span>
        </a>
        <!-- END Logo -->

        <!-- Extra -->
        <div>
            <!-- Close Sidebar, Visible only on mobile screens -->
            <a class="d-lg-none btn btn-sm btn-dual ml-1" data-toggle="layout" data-action="sidebar_close"
                href="javascript:void(0)">
                <i class="fa fa-fw fa-times"></i>
            </a>
            <!-- END Close Sidebar -->
        </div>
        <!-- END Extra -->
    </div>
    <!-- END Side Header -->

    <!-- Sidebar Scrolling -->
    <div class="js-sidebar-scroll">
        <!-- Side Navigation -->
        <div class="content-side">
            <ul class="nav-main">
                <li class="nav-main-item">
                    <a class="nav-main-link" href="{{ route('dashboard') }}">
                        <i class="nav-main-link-icon si si-speedometer"></i>
                        <span class="nav-main-link-name">Tableau de bord</span>
                    </a>
                </li>

                @if(isset($isProfesseur) && $isProfesseur)
                    {{-- ============================================= --}}
                    {{-- MENU PROFESSEUR - Acces limite aux notes --}}
                    {{-- ============================================= --}}

                    <li class="nav-main-heading">Mes Cours</li>

                    @if(isset($professeurCours) && $professeurCours->count() > 0)
                        @php
                            // Classes groupees par cycle (ordre du cycle), puis par classe a l'interieur.
                            $coursParCycle = $professeurCours
                                ->groupBy(fn ($cours) => $cours->classe->promotion->cycle_id ?? 0)
                                ->sortBy(fn ($coursDuCycle) => optional(optional($coursDuCycle->first()->classe->promotion)->cycle)->ordre ?? 999);
                        @endphp
                        <li class="nav-main-item">
                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                aria-expanded="false" href="#">
                                <i class="nav-main-link-icon si si-puzzle"></i>
                                <span class="nav-main-link-name">Mes classes</span>
                            </a>
                            <ul class="nav-main-submenu">
                                @foreach($coursParCycle as $coursDuCycle)
                                    @php $cycle = $coursDuCycle->first()->classe->promotion->cycle ?? null; @endphp
                                    <li class="nav-main-item">
                                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                            aria-haspopup="true" aria-expanded="false" href="#">
                                            <span class="nav-main-link-name">{{ $cycle->nom ?? 'Autre' }}</span>
                                        </a>
                                        <ul class="nav-main-submenu">
                                            @foreach($coursDuCycle->groupBy('classe_id') as $classeId => $coursDansClasse)
                                                @php $classe = $coursDansClasse->first()->classe; @endphp
                                                <li class="nav-main-item">
                                                    <a class="nav-main-link" href="{{ route('classe.index', $classe) }}">
                                                        <span class="nav-main-link-name">{{ $classe->nom }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif

                    <li class="nav-main-heading">Saisie des Notes</li>

                    <li class="nav-main-item">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="false" href="#">
                            <i class="nav-main-link-icon si si-note"></i>
                            <span class="nav-main-link-name">Interrogations</span>
                        </a>
                        <ul class="nav-main-submenu">
                            <li class="nav-main-item">
                                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                    aria-expanded="false" href="#">
                                    <i class="nav-main-link-icon si si-pencil"></i>
                                    <span class="nav-main-link-name">Nouvelle interrogation</span>
                                </a>
                                <ul class="nav-main-submenu">
                                    @foreach ($sidebarPromotions as $promotion)
                                        @foreach ($promotion->classes as $classe)
                                            <li class="nav-main-item">
                                                <a class="nav-main-link" href="{{ route('interrogation.cours', $classe) }}">
                                                    <span class="nav-main-link-name">{{ $classe->nom }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    @endforeach
                                </ul>
                            </li>
                            <li class="nav-main-item">
                                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                    aria-expanded="false" href="#">
                                    <i class="nav-main-link-icon si si-eye"></i>
                                    <span class="nav-main-link-name">Voir les interrogations</span>
                                </a>
                                <ul class="nav-main-submenu">
                                    @foreach ($sidebarPromotions as $promotion)
                                        @foreach ($promotion->classes as $classe)
                                            <li class="nav-main-item">
                                                <a class="nav-main-link" href="{{ route('view_interrogation_cours', $classe) }}">
                                                    <span class="nav-main-link-name">{{ $classe->nom }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    @endforeach
                                </ul>
                            </li>
                        </ul>
                    </li>

                    {{-- Devoirs/compositions : creation reservee au secretaire, le professeur ne
                    peut que consulter et saisir les notes de ceux deja crees pour ses cours. --}}
                    <li class="nav-main-item">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="false" href="#">
                            <i class="nav-main-link-icon si si-note"></i>
                            <span class="nav-main-link-name">Devoirs / Compositions</span>
                        </a>
                        <ul class="nav-main-submenu">
                            @foreach ($sidebarPromotions as $promotion)
                                <li class="nav-main-item">
                                    <a class="nav-main-link" href="{{ route('view_evaluation_matieres', $promotion) }}">
                                        <span class="nav-main-link-name">{{ $promotion->nom }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>

                @elseif(auth()->user() && auth()->user()->isSurveillant())
                    {{-- ============================================= --}}
                    {{-- MENU SURVEILLANT - Acces limite a l'assiduite --}}
                    {{-- ============================================= --}}

                    <li class="nav-main-heading">Nos classes</li>

                    <li class="nav-main-item">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="false" href="#">
                            <i class="nav-main-link-icon fa fa-chalkboard-teacher"></i>
                            <span class="nav-main-link-name">Classes</span>
                        </a>

                        <ul class="nav-main-submenu">
                            @foreach ($sidebarCycles as $cycle)
                                @php $promotionsDuCycle = $sidebarPromotions->where('cycle_id', $cycle->id); @endphp
                                @if($promotionsDuCycle->count() > 0)
                                    <li class="nav-main-item">
                                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                            aria-haspopup="true" aria-expanded="false" href="#">
                                            <span class="nav-main-link-name">{{ $cycle->nom }}</span>
                                        </a>
                                        <ul class="nav-main-submenu">
                                            @foreach ($promotionsDuCycle as $promotion)
                                                <li class="nav-main-item">
                                                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                                        aria-haspopup="true" aria-expanded="false" href="#">
                                                        <span class="nav-main-link-name">{{ $promotion->nom }}</span>
                                                    </a>
                                                    <ul class="nav-main-submenu">
                                                        @foreach ($promotion->classes as $classe)
                                                            <li class="nav-main-item">
                                                                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                                                    aria-haspopup="true" aria-expanded="false" href="#">
                                                                    <span class="nav-main-link-name">{{ $classe->nom }}</span>
                                                                </a>
                                                                <ul class="nav-main-submenu">
                                                                    <li class="nav-main-item">
                                                                        <a class="nav-main-link"
                                                                            href="{{ route('classe.index', $classe) }}">
                                                                            <span class="nav-main-link-name">Liste des eleves</span>
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </li>

                @else
                    {{-- ============================================= --}}
                    {{-- MENU ADMIN / DIRECTEUR - Acces complet --}}
                    {{-- ============================================= --}}

                    @if((isset($isAdmin) && $isAdmin) || (auth()->user() && (auth()->user()->isDirecteur() || auth()->user()->hasRole('secretaire_general'))))
                        {{-- Menu Administration pour Admin --}}
                        <li class="nav-main-heading">Administration Systeme</li>

                        <li class="nav-main-item">
                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                aria-expanded="false" href="#">
                                <i class="nav-main-link-icon fa fa-cogs"></i>
                                <span class="nav-main-link-name">Gestion Systeme</span>
                            </a>
                            <ul class="nav-main-submenu">
                                @if((isset($isAdmin) && $isAdmin) || (auth()->user() && auth()->user()->hasRole('directeur_general')))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link" href="{{ route('cycles.index') }}">
                                            <i class="nav-main-link-icon fa fa-sitemap"></i>
                                            <span class="nav-main-link-name">Cycles</span>
                                        </a>
                                    </li>
                                @endif
                                @if(auth()->user() && auth()->user()->isDirecteur())
                                    <li class="nav-main-item">
                                        <a class="nav-main-link" href="{{ route('promotions.index') }}">
                                            <i class="nav-main-link-icon fa fa-layer-group"></i>
                                            <span class="nav-main-link-name">Promotions</span>
                                        </a>
                                    </li>
                                @endif
                                @if(isset($isAdmin) && $isAdmin)
                                    <li class="nav-main-item">
                                        <a class="nav-main-link" href="{{ route('users.index') }}">
                                            <i class="nav-main-link-icon fa fa-users-cog"></i>
                                            <span class="nav-main-link-name">Utilisateurs</span>
                                        </a>
                                    </li>
                                @endif
                                @if((isset($isAdmin) && $isAdmin) || (auth()->user() && auth()->user()->hasRole('directeur_general')))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link" href="{{ route('annees-scolaires.index') }}">
                                            <i class="nav-main-link-icon fa fa-calendar-alt"></i>
                                            <span class="nav-main-link-name">Générer année suivante</span>
                                        </a>
                                    </li>
                                @endif
                                @if(auth()->user() && auth()->user()->hasRole(['directeur_general', 'secretaire_general']))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link" href="{{ route('settings.index') }}">
                                            <i class="nav-main-link-icon fa fa-cog"></i>
                                            <span class="nav-main-link-name">Parametres</span>
                                        </a>
                                    </li>
                                @endif
                                @if(isset($isAdmin) && $isAdmin)
                                    <li class="nav-main-item">
                                        <a class="nav-main-link" href="{{ route('logs.index') }}">
                                            <i class="nav-main-link-icon fa fa-bug"></i>
                                            <span class="nav-main-link-name">Logs &amp; Erreurs</span>
                                        </a>
                                    </li>
                                    <li class="nav-main-item">
                                        <a class="nav-main-link" href="{{ route('crons.index') }}">
                                            <i class="nav-main-link-icon fa fa-clock"></i>
                                            <span class="nav-main-link-name">Tâches planifiées</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if(auth()->user() && auth()->user()->isSecretaire())
                        <li class="nav-main-item">
                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                aria-expanded="false" href="#">
                                <i class="nav-main-link-icon fa fa-file-alt"></i>
                                <span class="nav-main-link-name">Configuration bulletins</span>
                            </a>
                            <ul class="nav-main-submenu">
                                <li class="nav-main-item">
                                    <a class="nav-main-link" href="{{ route('bulletin-config.index') }}">
                                        <i class="nav-main-link-icon fa fa-sort-amount-down"></i>
                                        <span class="nav-main-link-name">Ordre des matières</span>
                                    </a>
                                </li>
                                @if(auth()->user()->hasRole('secretaire_general'))
                                    <li class="nav-main-item">
                                        <a class="nav-main-link" href="{{ route('bulletin-config.header') }}">
                                            <i class="nav-main-link-icon fa fa-object-group"></i>
                                            <span class="nav-main-link-name">Disposition en-tête</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if(auth()->user() && auth()->user()->isDirecteur())
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="{{ route('passage.index') }}">
                                <i class="nav-main-link-icon fa fa-arrow-up"></i>
                                <span class="nav-main-link-name">Passage année supérieure</span>
                            </a>
                        </li>
                    @endif

                @unless(isset($isAdmin) && $isAdmin)
                    {{-- Menu Examens Officiels --}}
                    <li class="nav-main-heading">Examens Officiels</li>

                    <li class="nav-main-item">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="false" href="#">
                            <i class="nav-main-link-icon fa fa-graduation-cap"></i>
                            <span class="nav-main-link-name">Examens</span>
                        </a>
                        <ul class="nav-main-submenu">
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="{{ route('sessions.index') }}">
                                    <i class="nav-main-link-icon fa fa-calendar-check"></i>
                                    <span class="nav-main-link-name">Sessions d'examen</span>
                                </a>
                            </li>
                            @if(auth()->user() && auth()->user()->hasRole('directeur_general'))
                                <li class="nav-main-item">
                                    <a class="nav-main-link" href="{{ route('examens-officiels.index') }}">
                                        <i class="nav-main-link-icon fa fa-certificate"></i>
                                        <span class="nav-main-link-name">Examens Officiels</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>

                    <!----- Matieres et Cours ------>

                    <li class="nav-main-heading">Matieres et Cours</li>

                    <!---- Matieres ---->

                    <li class="nav-main-item">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="false" href="#">
                            <i class="nav-main-link-icon si si-layers"></i>
                            <span class="nav-main-link-name">Matieres</span>
                        </a>
                        <ul class="nav-main-submenu">
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="{{ route('matiere.index') }}">
                                    <i class="nav-main-link-icon fa fa-book"></i>
                                    <span class="nav-main-link-name">Liste des matières</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!------ Cours ------>

                    <li class="nav-main-item">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="false" href="#">
                            <i class="nav-main-link-icon si si-puzzle"></i>
                            <span class="nav-main-link-name">Cours</span>
                        </a>

                        <ul class="nav-main-submenu">
                            @foreach ($sidebarCycles as $cycle)
                                @php $promotionsDuCycle = $sidebarPromotions->where('cycle_id', $cycle->id); @endphp
                                @if($promotionsDuCycle->count() > 0)
                                    <li class="nav-main-item">
                                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                            aria-haspopup="true" aria-expanded="false" href="#">
                                            <span class="nav-main-link-name">{{ $cycle->nom }}</span>
                                        </a>
                                        <ul class="nav-main-submenu">
                                            @foreach ($promotionsDuCycle as $promotion)
                                                <li class="nav-main-item">
                                                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                                        aria-haspopup="true" aria-expanded="false" href="#">
                                                        <span class="nav-main-link-name">{{ $promotion->nom }}</span>
                                                    </a>
                                                    <ul class="nav-main-submenu">
                                                        @foreach ($promotion->classes as $classe)
                                                            <li class="nav-main-item">
                                                                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                                                    aria-haspopup="true" aria-expanded="false" href="#">
                                                                    <span class="nav-main-link-name">{{ $classe->nom }}</span>
                                                                </a>
                                                                <ul class="nav-main-submenu">
                                                                    <li class="nav-main-item">
                                                                        <a class="nav-main-link"
                                                                            href="{{ route('cours.index', $classe) }}">
                                                                            <span class="nav-main-link-name">Liste des cours</span>
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endif
                            @endforeach
                        </ul>

                    </li>



                    <!----- Evaluations ------>


                    <li class="nav-main-heading">Evaluations</li>

                    <li class="nav-main-item">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="false" href="#">
                            <i class="nav-main-link-icon si si-note"></i>
                            <span class="nav-main-link-name">Evaluations</span>
                        </a>
                        <ul class="nav-main-submenu">


                            <!---- Devoirs (creation reservee aux secretaires de cycle) ---->

                            @if(auth()->user() && auth()->user()->getSecretaireCycle())
                                <li class="nav-main-item">
                                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                        aria-expanded="false" href="#">
                                        <i class="nav-main-link-icon si si-pencil"></i>
                                        <span class="nav-main-link-name">Nouveau devoir/composition</span>
                                    </a>
                                    <ul class="nav-main-submenu">
                                        @foreach ($sidebarPromotions as $promotion)
                                            <li class="nav-main-item">
                                                <a class="nav-main-link"
                                                    href="{{ route('evaluation_matieres', $promotion) }}">
                                                    <span class="nav-main-link-name">{{ $promotion->nom }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif



                            <!---- Interrogations (creation reservee aux secretaires de cycle) ---->

                            @if(auth()->user() && auth()->user()->getSecretaireCycle())
                                <li class="nav-main-item">
                                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                        aria-expanded="false" href="#">
                                        <i class="nav-main-link-icon si si-pencil"></i>
                                        <span class="nav-main-link-name">Nouvelle interrogation</span>
                                    </a>
                                    <ul class="nav-main-submenu">
                                        @foreach ($sidebarPromotions as $promotion)
                                            <li class="nav-main-item">
                                                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                                    aria-haspopup="true" aria-expanded="false" href="#">
                                                    <span class="nav-main-link-name">{{ $promotion->nom }}</span>
                                                </a>
                                                <ul class="nav-main-submenu">
                                                    @foreach ($promotion->classes as $classe)
                                                        <li class="nav-main-item">
                                                            <a class="nav-main-link nav-main-link-submenu"
                                                                data-toggle="submenu" aria-haspopup="true"
                                                                aria-expanded="false" href="#">
                                                                <span class="nav-main-link-name">{{ $classe->nom }}</span>
                                                            </a>
                                                            <ul class="nav-main-submenu">
                                                                <li class="nav-main-item">
                                                                    <a class="nav-main-link"
                                                                        href="{{ route('interrogation.cours', $classe) }}">
                                                                        <span class="nav-main-link-name">Ajouter interrogation</span>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif


                            <!---- Voir Devoirs ---->

                            <li class="nav-main-item">
                                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                    aria-expanded="false" href="#">
                                    <i class="nav-main-link-icon si si-eye"></i>
                                    <span class="nav-main-link-name">Voir les devoirs/compositions</span>
                                </a>
                                <ul class="nav-main-submenu">
                                    @foreach ($sidebarPromotions as $promotion)
                                        <li class="nav-main-item">
                                            <a class="nav-main-link"
                                                href="{{ route('view_evaluation_matieres', $promotion) }}">
                                                <span class="nav-main-link-name">{{ $promotion->nom }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>




                            <!---- Voir Interrogations ---->

                            <li class="nav-main-item">
                                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                    aria-expanded="false" href="#">
                                    <i class="nav-main-link-icon si si-eye"></i>
                                    <span class="nav-main-link-name">Voir les interrogations</span>
                                </a>
                                <ul class="nav-main-submenu">
                                    @foreach ($sidebarPromotions as $promotion)
                                        <li class="nav-main-item">
                                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                                aria-haspopup="true" aria-expanded="false" href="#">
                                                <span class="nav-main-link-name">{{ $promotion->nom }}</span>
                                            </a>
                                            <ul class="nav-main-submenu">
                                                @foreach ($promotion->classes as $classe)
                                                    <li class="nav-main-item">
                                                        <a class="nav-main-link nav-main-link-submenu"
                                                            data-toggle="submenu" aria-haspopup="true"
                                                            aria-expanded="false" href="#">
                                                            <span class="nav-main-link-name">{{ $classe->nom }}</span>
                                                        </a>
                                                        <ul class="nav-main-submenu">
                                                            <li class="nav-main-item">
                                                                <a class="nav-main-link"
                                                                    href="{{ route('view_interrogation_cours', $classe) }}">
                                                                    <span class="nav-main-link-name">Voir interrogations</span>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>


                        </ul>
                    </li>





                    <!----- Classes ------>

                    <li class="nav-main-heading">Nos classes</li>

                    <li class="nav-main-item">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="false" href="#">
                            <i class="nav-main-link-icon fa fa-chalkboard-teacher"></i>
                            <span class="nav-main-link-name">Classes</span>
                        </a>

                        <ul class="nav-main-submenu">
                            @foreach ($sidebarCycles as $cycle)
                                @php $promotionsDuCycle = $sidebarPromotions->where('cycle_id', $cycle->id); @endphp
                                @if($promotionsDuCycle->count() > 0)
                                    <li class="nav-main-item">
                                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                            aria-haspopup="true" aria-expanded="false" href="#">
                                            <span class="nav-main-link-name">{{ $cycle->nom }}</span>
                                        </a>
                                        <ul class="nav-main-submenu">
                                            @foreach ($promotionsDuCycle as $promotion)
                                                <li class="nav-main-item">
                                                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                                        aria-haspopup="true" aria-expanded="false" href="#">
                                                        <span class="nav-main-link-name">{{ $promotion->nom }}</span>
                                                    </a>
                                                    <ul class="nav-main-submenu">

                                                        <!---- Ajouter un groupe (reserve au directeur du cycle) ---->

                                                        @if(auth()->user() && auth()->user()->isDirecteur() && !auth()->user()->hasRole('directeur_general'))
                                                            <li class="nav-main-item">
                                                                <a class="nav-main-link" href="{{ route('classe.list', $promotion) }}">
                                                                    <span class="nav-main-link-name">Ajouter un groupe</span>
                                                                </a>
                                                            </li>
                                                        @endif

                                                        <!----- Gestion des groupes ------>

                                                        @foreach ($promotion->classes as $classe)
                                                            <li class="nav-main-item">
                                                                <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu"
                                                                    aria-haspopup="true" aria-expanded="false" href="#">
                                                                    <span class="nav-main-link-name">{{ $classe->nom }}</span>
                                                                </a>
                                                                <ul class="nav-main-submenu">
                                                                    <li class="nav-main-item">
                                                                        <a class="nav-main-link"
                                                                            href="{{ route('classe.index', $classe) }}">
                                                                            <span class="nav-main-link-name">Liste des eleves</span>
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </li>
                                                        @endforeach

                                                    </ul>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endif
                            @endforeach
                        </ul>

                    </li>

                    <!----- Inscription (reservee aux secretaires) ------>

                    @if(auth()->user() && auth()->user()->isSecretaire())
                        <li class="nav-main-heading">Inscription</li>

                        <li class="nav-main-item">
                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                aria-expanded="false" href="#">
                                <i class="nav-main-link-icon fa fa-user-plus"></i>
                                <span class="nav-main-link-name">Admission</span>
                            </a>
                            <ul class="nav-main-submenu">
                                <li class="nav-main-item">
                                    <a class="nav-main-link" href="{{ route('eleve.create') }}">
                                        <span class="nav-main-link-name">Inscrire un eleve</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif



                    <li class="nav-main-heading">Equipe</li>

                    <li class="nav-main-item">
                        <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                            aria-expanded="false" href="#">
                            <i class="nav-main-link-icon fa fa-users"></i>
                            <span class="nav-main-link-name">Equipe enseignante</span>
                        </a>
                        <ul class="nav-main-submenu">
                            <li class="nav-main-item">
                                <a class="nav-main-link" href="{{ route('professeur.index') }}">
                                    <span class="nav-main-link-name">Gerer les enseignants</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endunless

                    {{-- ============================================= --}}
                    {{-- MENU COMPTABILITE --}}
                    {{-- ============================================= --}}

                @unless(isset($isAdmin) && $isAdmin)
                    <li class="nav-main-heading">Comptabilite</li>

                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ route('comptabilite.dashboard') }}">
                            <i class="nav-main-link-icon fa fa-chart-line"></i>
                            <span class="nav-main-link-name">Tableau de bord</span>
                        </a>
                    </li>

                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ route('comptabilite.eleves') }}">
                            <i class="nav-main-link-icon fa fa-users"></i>
                            <span class="nav-main-link-name">Paiements eleves</span>
                        </a>
                    </li>

                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ route('recus.index') }}">
                            <i class="nav-main-link-icon fa fa-receipt"></i>
                            <span class="nav-main-link-name">Recus</span>
                        </a>
                    </li>

                    <li class="nav-main-item">
                        <a class="nav-main-link" href="{{ route('comptabilite.rapports') }}">
                            <i class="nav-main-link-icon fa fa-file-invoice-dollar"></i>
                            <span class="nav-main-link-name">Rapports</span>
                        </a>
                    </li>
                @endunless

                    @if(auth()->user() && auth()->user()->hasRole(['directeur_general', 'comptable_general']))
                        <li class="nav-main-item">
                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true"
                                aria-expanded="false" href="#">
                                <i class="nav-main-link-icon fa fa-cogs"></i>
                                <span class="nav-main-link-name">Configuration frais</span>
                            </a>
                            <ul class="nav-main-submenu">
                                <li class="nav-main-item">
                                    <a class="nav-main-link" href="{{ route('types-frais.index') }}">
                                        <span class="nav-main-link-name">Types de frais</span>
                                    </a>
                                </li>
                                <li class="nav-main-item">
                                    <a class="nav-main-link" href="{{ route('configurations-frais.index') }}">
                                        <span class="nav-main-link-name">Tarifs par niveau</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                @endif

            </ul>
        </div>
        <!-- END Side Navigation -->
    </div>
    <!-- END Sidebar Scrolling -->

    <!-- Sidebar Footer / Version -->
    {{-- #sidebar is position:fixed and its nav content (.js-sidebar-scroll) is the
         scrolling element, so a plain flow element here would land after the whole
         (long, role-based) menu and only be reachable by scrolling past it. Pin it
         to the bottom of the fixed sidebar viewport instead. --}}
    <div class="content-header bg-white-5 justify-content-center" id="sidebar-version-footer">
        <button type="button" class="btn btn-sm btn-dual" data-toggle="modal" data-target="#changelog-modal">
            <span class="smini-visible"><i class="fa fa-fw fa-code-branch"></i></span>
            <span class="smini-hide badge badge-secondary">
                v{{ $schoolSettings['system_version'] ?? '1.0' }}
            </span>
        </button>
    </div>
    <!-- END Sidebar Footer / Version -->
    <style>
        #sidebar .js-sidebar-scroll {
            height: calc(100% - 8rem);
        }

        #sidebar-version-footer {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
        }
    </style>
</nav>
