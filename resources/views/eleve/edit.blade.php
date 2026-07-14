@extends('layouts.dashboard')

<style>
    #imagePreview {
        max-height: 300px;
    }

    .eleve-avatar-frame {
        width: 110px;
        height: 110px;
        border-radius: 100px;
        overflow: hidden;
        border: 3px solid #edf0f2;
        flex-shrink: 0;
    }

    .eleve-avatar-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>

@php
    $pere = $eleve->pere ?? [];
    $mere = $eleve->mere ?? [];
    $tuteur = $eleve->contact_tuteur ?? [];
    $sante = $eleve->sante ?? [];
    $profilPath = public_path('/storage/' . $eleve->profil);
    $hasProfil = !empty($eleve->profil) && is_file($profilPath);
@endphp

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Modifier les information de l'élève
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">{{ $classe->promotion->nom }}</li>
                        <li class="breadcrumb-item">Modifier</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">{{ $eleve->nom }}
                                {{ $eleve->prenom }}</a></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content">

        <!---- Copiez collez (il s'agit des alertes pour le retour d'actions)----->

        @if ($notification = Session::get('notification'))
            @if ($notification['type'] === 'success')
                <div class="alert alert-success alert-dismissable" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <p class="mb-0">{{ $notification['message'] }}</p>
                </div>
            @endif
            @if ($notification['type'] === 'warning')
                <div class="alert alert-warning alert-dismissable" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <p class="mb-0">{{ $notification['message'] }}</p>
                </div>
            @endif
            @if ($notification['type'] === 'error')
                <div class="alert alert-danger alert-dismissable" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <p class="mb-0">{{ $notification['message'] }}</p>
                </div>
            @endif
        @endif

        <!---- Copiez collez ----->


        <div class="block block-rounded">
            <div class="block-content px-5">
                <div class="d-flex mx-0 px-0 justify-content-between align-items-center mb-5">
                    <h3 class="m-0">Informations de {{ $eleve->nom }} {{ $eleve->prenom }}</h3>

                    <a href="{{ route('classe.index', $classe) }}" class="btn btn-secondary"><i
                            class="fa fa-angle-left mr-1" aria-hidden="true"></i>Retour</a>
                </div>

                <p class="font-size-sm text-muted">
                    Modifiez les informations de {{ $eleve->nom }} {{ $eleve->prenom }}.
                </p>
            </div>
            <div>
                <!-- Simple Wizard 2 -->
                <div class="js-wizard-simple block block">
                    <!-- Step Tabs -->
                    <ul class="nav nav-tabs nav-tabs-alt nav-justified" role="tablist">
                        <li class="nav-item bg-light">
                            <a class="nav-link active" href="#wizard-simple2-step1" data-toggle="tab">1. Identité de
                                l'élève <i class="fa fa-user ml-2" aria-hidden="true"></i></a>
                        </li>
                        <li class="nav-item bg-light">
                            <a class="nav-link" href="#wizard-simple2-step2" data-toggle="tab">2. Responsables de
                                l'élève <i class="fa fa-user-friends ml-2" aria-hidden="true"></i></a>
                        </li>
                        <li class="nav-item bg-light">
                            <a class="nav-link" href="#wizard-simple2-step3" data-toggle="tab">3. Informations
                                médicales <i class="fa fa-first-aid ml-2" aria-hidden="true"></i></a>
                        </li>
                    </ul>
                    <!-- END Step Tabs -->

                    <!-- Form -->
                    <form action="{{ route('eleve.update', $eleve) }}" method="POST" enctype="multipart/form-data"
                        id="form-eleve">
                        @csrf
                        <!-- Steps Content -->
                        <div class="block-content block-content-full tab-content px-md-5" style="min-height: 303px;">
                            <!-- Step 1 -->
                            <div class="tab-pane fade show active" id="wizard-simple2-step1" role="tabpanel">

                                <div class="block block-rounded block-bordered mb-4">
                                    <div class="block-header block-header-default">
                                        <h3 class="block-title"><i class="fa fa-id-card mr-2 text-primary"
                                                aria-hidden="true"></i>Identité</h3>
                                    </div>
                                    <div class="block-content">

                                        <div class="row mx-0 px-0 align-items-center mb-4">
                                            <div class="eleve-avatar-frame">
                                                <img id="imagePreview"
                                                    src="{{ $hasProfil ? asset('storage/' . $eleve->profil) : asset('assets/media/avatars/avatar1.jpg') }}"
                                                    alt="Photo de {{ $eleve->nom }} {{ $eleve->prenom }}" />
                                            </div>
                                            <div class="col pl-4">
                                                <label for="eleve-profil">Photo passeport de l'élève</label>
                                                <input type="file" name="profil" accept="image/*"
                                                    class="form-control form-control-file form-control-alt"
                                                    id="eleve-profil" onchange="previewImage(event)" />
                                                <small class="form-text text-muted">Laissez vide pour conserver la
                                                    photo actuelle.</small>
                                                <input hidden type="number" id="photo_change" name="photo_change"
                                                    value="0" />
                                            </div>
                                        </div>

                                        <div class="row mx-0 px-0">

                                            <div class="form-group col-lg-4">
                                                <label for="eleve-nom">Nom de l'élève</label>
                                                <input class="form-control form-control-alt text-uppercase"
                                                    type="text" id="eleve-nom"
                                                    value="{{ old('nom', $eleve->nom) }}" name="nom" />
                                                <small class="form-text text-muted">Sera enregistré en
                                                    majuscules.</small>
                                            </div>

                                            <div class="form-group col-lg-5">
                                                <label for="eleve-prenom">Prénoms de l'élève</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="eleve-prenom" value="{{ old('prenom', $eleve->prenom) }}"
                                                    name="prenom" />
                                            </div>

                                            <div class="form-group col-lg-3">
                                                <label class="d-block">Sexe</label>
                                                @php $sexeEleve = old('sexe', $eleve->sexe); @endphp
                                                <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                                    <label
                                                        class="btn btn-alt-secondary flex-fill @if ($sexeEleve === 'M') active @endif">
                                                        <input type="radio" name="sexe" value="M" autocomplete="off"
                                                            @checked($sexeEleve === 'M')>
                                                        <i class="fa fa-mars mr-1" aria-hidden="true"></i>Masculin
                                                    </label>
                                                    <label
                                                        class="btn btn-alt-secondary flex-fill @if ($sexeEleve === 'F') active @endif">
                                                        <input type="radio" name="sexe" value="F" autocomplete="off"
                                                            @checked($sexeEleve === 'F')>
                                                        <i class="fa fa-venus mr-1" aria-hidden="true"></i>Féminin
                                                    </label>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row mx-0 px-0">

                                            <div class="form-group col-lg-4">
                                                <label for="eleve-date-naissance">Date de naissance</label>
                                                <input type="text" class="js-flatpickr form-control form-control-alt"
                                                    id="eleve-date-naissance" name="date_naissance"
                                                    value="{{ old('date_naissance', $eleve->date_naissance) }}"
                                                    placeholder="Y-m-d" />
                                            </div>

                                            <div class="form-group col-lg-8">
                                                <label for="eleve-lieu-naissance">Lieu de naissance</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="eleve-lieu-naissance" name="lieu_naissance"
                                                    value="{{ old('lieu_naissance', $eleve->lieu_naissance) }}">
                                            </div>

                                        </div>

                                    </div>
                                </div>

                                <div class="block block-rounded block-bordered mb-4">
                                    <div class="block-header block-header-default">
                                        <h3 class="block-title"><i class="fa fa-map-marker-alt mr-2 text-primary"
                                                aria-hidden="true"></i>Adresse &amp; scolarité</h3>
                                    </div>
                                    <div class="block-content">

                                        <div class="row mx-0 px-0">

                                            <div class="form-group col-lg-7">
                                                <label for="eleve-adresse">Adresse de l'élève</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="eleve-adresse" name="adresse"
                                                    value="{{ old('adresse', $eleve->adresse) }}">
                                            </div>

                                            <div class="form-group col-lg-5">
                                                <label for="eleve-classe">Classe</label>
                                                <select class="js-select2 form-control form-control-alt"
                                                    id="eleve-classe" name="classe_id" style="width: 100%;"
                                                    data-placeholder="Recherchez une classe...">
                                                    <option value="{{ $classe->id }}" selected>{{ $classe->nom }}
                                                    </option>
                                                    @foreach ($classes_disponibles as $autreClasse)
                                                        <option value="{{ $autreClasse->id }}">
                                                            {{ $autreClasse->nom }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="form-text text-muted">
                                                    En changeant de classe (même promotion), les notes de
                                                    devoir et de composition déjà saisies sont reportées vers
                                                    la nouvelle classe. Les notes d'interrogation ne sont pas
                                                    reportées.
                                                </small>
                                            </div>

                                        </div>

                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-alt-primary" type="button"
                                        data-step-target="#wizard-simple2-step2">
                                        Suivant <i class="fa fa-angle-right ml-1" aria-hidden="true"></i>
                                    </button>
                                </div>

                            </div>

                            <!-- END Step 1 -->

                            <!-- Step 2 -->
                            <div class="tab-pane fade" id="wizard-simple2-step2" role="tabpanel">

                                <p class="font-size-sm text-muted">
                                    Renseignez les parents et, si besoin, un tuteur légal différent. Tous ces champs
                                    sont facultatifs.
                                </p>

                                <div class="block block-rounded block-bordered mb-4">
                                    <div class="block-header block-header-default">
                                        <h3 class="block-title"><i class="fa fa-male mr-2 text-primary"
                                                aria-hidden="true"></i>Père</h3>
                                    </div>
                                    <div class="block-content">

                                        <div class="row mx-0 px-0">

                                            <div class="form-group col-lg-3">
                                                <label for="pere-nom">Nom du père</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="pere-nom" name="nom_pere"
                                                    value="{{ old('nom_pere', $pere['nom'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-6">
                                                <label for="pere-prenom">Prénom du père</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="pere-prenom" name="prenom_pere"
                                                    value="{{ old('prenom_pere', $pere['prenom'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-3">
                                                <label for="pere-contact">Contact du père</label>
                                                <input class="form-control form-control-alt" type="tel"
                                                    id="pere-contact" name="contact_pere"
                                                    value="{{ old('contact_pere', $pere['telephone'] ?? '') }}" />
                                            </div>

                                        </div>

                                        <div class="row mx-0 px-0">

                                            <div class="form-group col-lg-6">
                                                <label for="pere-adresse">Adresse du père</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="pere-adresse" name="adresse_pere"
                                                    value="{{ old('adresse_pere', $pere['adresse'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-3">
                                                <label for="pere-profession">Profession du père</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="pere-profession" name="profession_pere"
                                                    value="{{ old('profession_pere', $pere['profession'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-3">
                                                <label for="pere-situation">Situation matrimoniale</label>
                                                @php $situationPere = old('situation_matrimoniale_pere', $pere['situation_matrimoniale'] ?? ''); @endphp
                                                <select class="form-control form-control-alt"
                                                    name="situation_matrimoniale_pere" id="pere-situation">
                                                    <option value="Célibataire"
                                                        @selected($situationPere === 'Célibataire')>
                                                        Célibataire</option>
                                                    <option value="Marié" @selected($situationPere === 'Marié')>
                                                        Marié
                                                    </option>
                                                    <option value="Veuf" @selected($situationPere === 'Veuf')>Veuf
                                                    </option>
                                                    <option value="Autre" @selected($situationPere === 'Autre')>
                                                        Autre
                                                    </option>
                                                </select>
                                            </div>

                                        </div>

                                    </div>
                                </div>

                                <div class="block block-rounded block-bordered mb-4">
                                    <div class="block-header block-header-default">
                                        <h3 class="block-title"><i class="fa fa-female mr-2 text-primary"
                                                aria-hidden="true"></i>Mère</h3>
                                    </div>
                                    <div class="block-content">

                                        <div class="row mx-0 px-0">

                                            <div class="form-group col-lg-3">
                                                <label for="mere-nom">Nom de la mère</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="mere-nom" name="nom_mere"
                                                    value="{{ old('nom_mere', $mere['nom'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-6">
                                                <label for="mere-prenom">Prénom de la mère</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="mere-prenom" name="prenom_mere"
                                                    value="{{ old('prenom_mere', $mere['prenom'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-3">
                                                <label for="mere-contact">Contact de la mère</label>
                                                <input class="form-control form-control-alt" type="tel"
                                                    id="mere-contact" name="contact_mere"
                                                    value="{{ old('contact_mere', $mere['telephone'] ?? '') }}" />
                                            </div>

                                        </div>

                                        <div class="row mx-0 px-0">

                                            <div class="form-group col-lg-6">
                                                <label for="mere-adresse">Adresse de la mère</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="mere-adresse" name="adresse_mere"
                                                    value="{{ old('adresse_mere', $mere['adresse'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-3">
                                                <label for="mere-profession">Profession de la mère</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="mere-profession" name="profession_mere"
                                                    value="{{ old('profession_mere', $mere['profession'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-3">
                                                <label for="mere-situation">Situation matrimoniale</label>
                                                @php $situationMere = old('situation_matrimoniale_mere', $mere['situation_matrimoniale'] ?? ''); @endphp
                                                <select class="form-control form-control-alt"
                                                    name="situation_matrimoniale_mere" id="mere-situation">
                                                    <option value="Célibataire"
                                                        @selected($situationMere === 'Célibataire')>
                                                        Célibataire</option>
                                                    <option value="Mariée" @selected($situationMere === 'Mariée')>
                                                        Mariée
                                                    </option>
                                                    <option value="Veuve" @selected($situationMere === 'Veuve')>
                                                        Veuve
                                                    </option>
                                                    <option value="Autre" @selected($situationMere === 'Autre')>
                                                        Autre
                                                    </option>
                                                </select>
                                            </div>

                                        </div>

                                    </div>
                                </div>

                                <div class="block block-rounded block-bordered mb-4">
                                    <div class="block-header block-header-default">
                                        <h3 class="block-title"><i class="fa fa-hands-helping mr-2 text-primary"
                                                aria-hidden="true"></i>Tuteur (si différent des parents)</h3>
                                        <div class="block-options">
                                            <button type="button" class="btn btn-sm btn-alt-secondary"
                                                data-copy-from="pere">Identique au père</button>
                                            <button type="button" class="btn btn-sm btn-alt-secondary"
                                                data-copy-from="mere">Identique à la mère</button>
                                        </div>
                                    </div>
                                    <div class="block-content">

                                        <div class="row mx-0 px-0">

                                            <div class="form-group col-lg-3">
                                                <label for="tuteur-nom">Nom du tuteur</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="tuteur-nom" name="nom_tuteur"
                                                    value="{{ old('nom_tuteur', $tuteur['nom'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-6">
                                                <label for="tuteur-prenom">Prénom du tuteur</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="tuteur-prenom" name="prenom_tuteur"
                                                    value="{{ old('prenom_tuteur', $tuteur['prenom'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-3">
                                                <label for="tuteur-contact">Contact du tuteur</label>
                                                <input class="form-control form-control-alt" type="tel"
                                                    id="tuteur-contact" name="contact_tuteur"
                                                    value="{{ old('contact_tuteur', $tuteur['telephone'] ?? '') }}" />
                                            </div>

                                        </div>

                                        <div class="row mx-0 px-0">

                                            <div class="form-group col-lg-6">
                                                <label for="tuteur-adresse">Adresse du tuteur</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="tuteur-adresse" name="adresse_tuteur"
                                                    value="{{ old('adresse_tuteur', $tuteur['adresse'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-3">
                                                <label for="tuteur-profession">Profession du tuteur</label>
                                                <input class="form-control form-control-alt" type="text"
                                                    id="tuteur-profession" name="profession_tuteur"
                                                    value="{{ old('profession_tuteur', $tuteur['profession'] ?? '') }}" />
                                            </div>

                                            <div class="form-group col-lg-3">
                                                <label for="tuteur-situation">Situation matrimoniale</label>
                                                @php $situationTuteur = old('situation_matrimoniale_tuteur', $tuteur['situation_matrimoniale'] ?? ''); @endphp
                                                <select class="form-control form-control-alt"
                                                    name="situation_matrimoniale_tuteur" id="tuteur-situation">
                                                    <option value="Célibataire"
                                                        @selected($situationTuteur === 'Célibataire')>
                                                        Célibataire</option>
                                                    <option value="Marié(e)"
                                                        @selected($situationTuteur === 'Marié(e)')>
                                                        Marié(e)</option>
                                                    <option value="Veuf" @selected($situationTuteur === 'Veuf')>
                                                        Veuf
                                                    </option>
                                                    <option value="Veuve" @selected($situationTuteur === 'Veuve')>
                                                        Veuve
                                                    </option>
                                                    <option value="Autre" @selected($situationTuteur === 'Autre')>
                                                        Autre
                                                    </option>
                                                </select>
                                            </div>

                                        </div>

                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-alt-secondary" type="button"
                                        data-step-target="#wizard-simple2-step1">
                                        <i class="fa fa-angle-left mr-1" aria-hidden="true"></i> Précédent
                                    </button>
                                    <button class="btn btn-alt-primary" type="button"
                                        data-step-target="#wizard-simple2-step3">
                                        Suivant <i class="fa fa-angle-right ml-1" aria-hidden="true"></i>
                                    </button>
                                </div>

                            </div>
                            <!-- END Step 2 -->

                            <!-- Step 3 -->
                            <div class="tab-pane fade" id="wizard-simple2-step3" role="tabpanel">

                                <p class="font-size-sm text-muted">
                                    Ces informations permettent de réagir rapidement en cas d'urgence. Elles sont
                                    facultatives.
                                </p>

                                <div class="row mx-0 px-0">

                                    <div class="form-group col-lg-3">
                                        <label for="sante-groupe"><i class="fa fa-tint text-danger mr-1"
                                                aria-hidden="true"></i> Groupe sanguin</label>
                                        @php $groupeSanguin = old('groupe_sanguin', $sante['groupe'] ?? ''); @endphp
                                        <select class="form-control form-control-alt" id="sante-groupe"
                                            name="groupe_sanguin">
                                            <option value="">Non renseigné</option>
                                            @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $groupe)
                                                <option value="{{ $groupe }}"
                                                    @selected($groupeSanguin === $groupe)>{{ $groupe }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-lg-9">
                                        <label for="sante-restrictions"><i class="fa fa-ban mr-1"
                                                aria-hidden="true"></i> Activités restreintes</label>
                                        <textarea class="form-control form-control-alt" id="sante-restrictions" rows="2"
                                            name="restrictions" placeholder="Ex : course, sport intense, éducation physique...">{{ old('restrictions', $sante['restrictions'] ?? '') }}</textarea>
                                    </div>

                                </div>

                                <div class="row mx-0 px-0">

                                    <div class="form-group col-lg-6">
                                        <label for="sante-problemes"><i class="fa fa-notes-medical mr-1"
                                                aria-hidden="true"></i> Problèmes de santé importants</label>
                                        <textarea class="form-control form-control-alt" id="sante-problemes" rows="3"
                                            name="problemes" placeholder="Ex : diabète, asthme, allergies, épilepsie...">{{ old('problemes', $sante['problemes'] ?? '') }}</textarea>
                                    </div>

                                    <div class="form-group col-lg-6">
                                        <label for="sante-medicaments"><i class="fa fa-pills mr-1"
                                                aria-hidden="true"></i> Médicaments pris régulièrement</label>
                                        <textarea class="form-control form-control-alt" id="sante-medicaments" rows="3"
                                            name="medicaments" placeholder="Ex : ventoline, insuline...">{{ old('medicaments', $sante['medicaments'] ?? '') }}</textarea>
                                        <small class="form-text text-muted">Précisez si possible le dosage ou la
                                            fréquence.</small>
                                    </div>

                                </div>

                                <hr />

                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-alt-secondary" type="button"
                                        data-step-target="#wizard-simple2-step2">
                                        <i class="fa fa-angle-left mr-1" aria-hidden="true"></i> Précédent
                                    </button>
                                    <button class="btn btn-success" type="submit">
                                        <i class="fa fa-check mr-1" aria-hidden="true"></i> Enregistrer les
                                        modifications
                                    </button>
                                </div>

                            </div>
                            <!-- END Step 3 -->

                        </div>
                        <!-- END Steps Content -->
                    </form>
                    <!-- END Form -->

                </div>
                <!-- END Simple Wizard 2 -->
            </div>
        </div>

    </div>


    <script>
        let photo_change = document.querySelector('#photo_change')

        function previewImage(event) {
            var input = event.target;
            if (!input.files || !input.files[0]) {
                return;
            }

            photo_change.value = 1;
            var reader = new FileReader();
            reader.onload = function() {
                document.getElementById('imagePreview').src = reader.result;
            };
            reader.readAsDataURL(input.files[0]);
        }

        jQuery(function($) {
            // Navigation Suivant/Précédent entre les étapes du formulaire, avec
            // validation des champs de l'étape courante avant de passer à la suivante.
            $('[data-step-target]').on('click', function() {
                var $currentPane = $(this).closest('.tab-pane');
                var $invalid = $currentPane.find(':invalid').first();

                if ($invalid.length) {
                    $invalid[0].reportValidity();
                    return;
                }

                var target = $(this).data('step-target');
                $('.nav-tabs a[href="' + target + '"]').tab('show');
            });

            // Pré-remplit le bloc "Tuteur" avec les informations du père ou de la
            // mère déjà saisies, pour éviter de retaper deux fois les mêmes infos
            // lorsque le tuteur légal est l'un des parents.
            $('[data-copy-from]').on('click', function() {
                var source = $(this).data('copy-from');
                ['nom', 'prenom', 'contact', 'adresse', 'profession', 'situation'].forEach(function(field) {
                    $('#tuteur-' + field).val($('#' + source + '-' + field).val());
                });
            });
        });
    </script>

@endsection
