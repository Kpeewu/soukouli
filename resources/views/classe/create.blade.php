@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Ajout d'une classe {{ $promotion->nom }}
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">Classes</li>
                        <li class="breadcrumb-item">{{ $promotion->nom }}
                        </li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Ajout d'une classe de
                                {{ $promotion->nom }}</a>
                        </li>
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

        <div class="block block-rounded p-5">
            <form action="{{ route('classe.store') }}" method="post">
                @csrf
                <h3>Nouvelle classe de {{ $promotion->nom }}</h3>

                <div class="col-12 py-3 row justify-content-center">
                    <div class="row justify-content-center align-items-center mx-0 px-0">
                        <!-- Affichage du nom de l'élève -->
                        <label class="col-12 col-lg-3">Classe de {{ $promotion->nom }}</label>

                        <input class="col-lg-3" type="hidden" name="promotion_id" value="{{ $promotion->id }}">


                        <!-- Champ pour le groupe -->
                        <input type="text" class="form-control form-control-alt col-12 col-lg-3" name="nom"
                            required />

                        <label for="" class="col-12 col-lg-3">Enseignant titulaire</label>

                        <div class="col-lg-3">
                            <select name="professeur_id" class="js-select2 form-control form-control-alt col-lg-3"
                            style="width: 100%;" data-placeholder="Sélectionnez un titulaire" required>
                                <option></option>
                                @foreach ($professeurs as $professeur)
                                    <option value="{{ $professeur->id }}">{{ $professeur->nom }} {{ $professeur->prenom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-success mx-4 my-3" type="submit">Enregistrer</button>
                </div>
            </form>
        </div>

    @endsection
