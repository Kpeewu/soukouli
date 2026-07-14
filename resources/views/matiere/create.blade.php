@extends('layouts.dashboard')

@section('main-content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill h3 my-2">
                    Ajouter une matière
                </h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">{{ $annee }}</li>
                        <li class="breadcrumb-item"><a class="link-fx" href="">Ajouter une matière</a></li>
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
            <div class="block-content">
                <p class="font-size-sm text-muted">
                    Ajoutez une nouvelle matière enseignée dans votre établissement.
                </p>
                <form action="{{ route('matiere.store') }}" method="post">
                    @csrf
                    <div class="col-12 py-3">
                        <div class="row justify-content-center align-items-center mx-0 px-0 my-4">
                            <!-- Affichage du nom de l'élève -->
                            <label class="col-4 col-lg-4">Nom de la matière</label>

                            <!-- Champ pour le groupe -->
                            <input type="text" class="form-control form-control-alt col-8 col-lg-5" name="intitule"
                                required />

                        </div>
                        <div class="row justify-content-center align-items-center mx-0 px-0">
                            <!-- Affichage du nom de l'élève -->
                            <label class="col-8 col-lg-4">Niveaux d'enseignements</label>

                            <div class="col-8 col-lg-5 px-0">
                                <select class="js-select2 form-control" name="promotions[]" multiple="multiple"
                                    style="width: 100%;" data-placeholder="Choisissez les niveaux d'enseignement...">
                                    @foreach ($promotions as $promotion)
                                        <option value="{{ $promotion->id }}">{{ $promotion->nom }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="col-12 py-3">
                        <div class="row justify-content-center align-items-center mx-0 px-0">
                            <button class="btn btn-success" type="submit">Enregistrer</button>
                        </div>
                    </div>

                </form>

            </div>
        </div>

    </div>
@endsection
