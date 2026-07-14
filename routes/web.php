<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\AnneeScolaireController;
use App\Http\Controllers\AssiduiteController;
use App\Http\Controllers\BulletinConfigController;
use App\Http\Controllers\BulletinHeaderConfigController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\EleveController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CronScheduleController;
use App\Http\Controllers\InscriptionExamenController;
use App\Http\Controllers\LaTexToPDFController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\MatiereController;
use App\Http\Controllers\CoursController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfesseurController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RetardController;
use App\Http\Controllers\SessionExamenController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExamenOfficielController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ComptabiliteController;
use App\Http\Controllers\ConfigurationFraisController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PassageController;
use App\Http\Controllers\RecuController;
use App\Http\Controllers\TypeFraisController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

/* Healthcheck (utilise par le conteneur webserver Docker) */
Route::get('/up', function () {
    return response('OK', 200);
});

/* Routes d'authentification */

Auth::routes();

Route::get('/', function () {
    return view('auth.login');
});





Route::middleware(['auth', 'filter.cycle'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

    // Profil de l'utilisateur connecte
    Route::get('/mon-profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mon-profil', [ProfileController::class, 'update'])->name('profile.update');

    /*
    |--------------------------------------------------------------------------
    | Routes Administration (Admin seulement)
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Routes Gestion des cycles (Admin + Directeur General)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin|directeur_general'])->group(function () {
        Route::resource('cycles', CycleController::class);
        Route::post('cycles/{cycle}/add-niveaux', [CycleController::class, 'addNiveaux'])->name('cycles.add-niveaux');
        Route::post('cycles/{cycle}/update-niveaux', [CycleController::class, 'updateNiveaux'])->name('cycles.update-niveaux');
    });

    Route::middleware(['role:admin'])->group(function () {
        // Gestion des utilisateurs
        Route::resource('users', UserController::class);

        // Logs et erreurs applicatives
        Route::get('logs', [LogViewerController::class, 'index'])->name('logs.index');
        Route::get('logs/download', [LogViewerController::class, 'download'])->name('logs.download');
        Route::delete('logs/clear', [LogViewerController::class, 'clear'])->name('logs.clear');

        // Administration des taches planifiees (crons)
        Route::get('crons', [CronScheduleController::class, 'index'])->name('crons.index');
        Route::post('crons/{key}/config', [CronScheduleController::class, 'updateConfig'])
            ->whereIn('key', ['annee-scolaire', 'passage-eleves'])->name('crons.config');
        Route::get('crons/{key}/log', [CronScheduleController::class, 'log'])
            ->whereIn('key', ['annee-scolaire', 'passage-eleves'])->name('crons.log');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Generation Annee Scolaire (Admin + Directeur General)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin|directeur_general'])->group(function () {
        Route::get('annees-scolaires', [AnneeScolaireController::class, 'index'])->name('annees-scolaires.index');
        Route::post('annees-scolaires/generer', [AnneeScolaireController::class, 'genererAnneeSuivante'])->name('annees-scolaires.generer');
        Route::post('annees-scolaires/{anneeScolaire}/activer', [AnneeScolaireController::class, 'activer'])->name('annees-scolaires.activer');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Examens Officiels (Directeur General uniquement)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:directeur_general'])->group(function () {
        // Gestion des examens officiels
        Route::resource('examens-officiels', ExamenOfficielController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Parametres (Directeur General + Secretaire General uniquement)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:directeur_general|secretaire_general'])->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/reset', [SettingsController::class, 'reset'])->name('settings.reset');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Promotions (Directeurs uniquement - filtrees par cycle dans le controleur)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:directeur_general|directeur_maternelle|directeur_primaire|directeur_college|directeur_lycee'])->group(function () {
        Route::get('promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::patch('promotions/{promotion}/periode', [PromotionController::class, 'updatePeriode'])->name('promotions.updatePeriode');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration bulletins - ordre des matieres + disposition en-tete
    | (Secretaires uniquement)
    |--------------------------------------------------------------------------
    */
    // Disposition de l'en-tete : partagee par tous les cycles, reservee au
    // secretaire general - les secretaires de cycle ne doivent pas pouvoir la
    // modifier. Enregistrees avant le groupe suivant (routes litterales
    // "header/*" avant le wildcard {promotion}) pour ne pas etre captees par
    // celui-ci.
    Route::prefix('bulletin-config')->middleware('role:secretaire_general')->group(function () {
        Route::get('header', [BulletinHeaderConfigController::class, 'edit'])->name('bulletin-config.header');
        Route::post('header', [BulletinHeaderConfigController::class, 'update'])->name('bulletin-config.header.update');
        Route::post('header/reset', [BulletinHeaderConfigController::class, 'reset'])->name('bulletin-config.header.reset');
        Route::post('header/preview', [BulletinHeaderConfigController::class, 'preview'])->name('bulletin-config.header.preview');
    });

    Route::prefix('bulletin-config')->middleware('role:secretaire_general|secretaire_maternelle|secretaire_primaire|secretaire_college|secretaire_lycee')->group(function () {
        Route::get('/', [BulletinConfigController::class, 'index'])->name('bulletin-config.index');

        Route::get('/{promotion}', [BulletinConfigController::class, 'edit'])->name('bulletin-config.edit');
        Route::post('/{promotion}', [BulletinConfigController::class, 'update'])->name('bulletin-config.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Passage en annee superieure (Directeurs uniquement)
    |--------------------------------------------------------------------------
    */
    Route::controller(PassageController::class)->middleware('deny.admin')->group(function () {
        Route::get('passage', 'index')->name('passage.index');
        Route::get('passage/plan', 'plan')->name('passage.plan');
        Route::post('passage/classe/{classe}/executer', 'executerClasse')->name('passage.executerClasse');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Examens Officiels
    |--------------------------------------------------------------------------
    */
    Route::prefix('examens')->middleware('deny.admin')->group(function () {
        // Sessions d'examen
        Route::resource('sessions', SessionExamenController::class);

        // Inscriptions aux examens
        Route::get('sessions/{session}/inscriptions', [InscriptionExamenController::class, 'index'])->name('inscriptions.index');
        Route::get('sessions/{session}/inscriptions/create', [InscriptionExamenController::class, 'create'])->name('inscriptions.create');
        Route::post('sessions/{session}/inscriptions', [InscriptionExamenController::class, 'store'])->name('inscriptions.store');
        Route::delete('inscriptions/{inscription}', [InscriptionExamenController::class, 'destroy'])->name('inscriptions.destroy');

        // Resultats
        Route::get('resultats/{session}/saisie', [InscriptionExamenController::class, 'saisie'])->name('resultats.saisie');
        Route::post('resultats/{session}/enregistrer', [InscriptionExamenController::class, 'enregistrerResultats'])->name('resultats.enregistrer');
        Route::get('resultats/{session}/liste', [InscriptionExamenController::class, 'liste'])->name('resultats.liste');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Comptabilite (comptable + admin)
    |--------------------------------------------------------------------------
    */
    // Configuration des frais (directeur general + comptable general) - independante de
    // l'acces au dashboard comptabilite, pour ne pas exiger la permission comptabilite.dashboard.
    Route::middleware(['role:directeur_general|comptable_general'])->prefix('comptabilite')->group(function () {
        Route::resource('types-frais', TypeFraisController::class);
        Route::resource('configurations-frais', ConfigurationFraisController::class);
        Route::post('configurations-frais/{config}/tranches', [ConfigurationFraisController::class, 'storeTranche'])
            ->name('configurations-frais.tranches.store');
    });

    // Consultation de la comptabilite : accessible aux comptables (permission comptabilite.dashboard)
    // ainsi qu'aux directeurs, en lecture seule (leur cycle pour un directeur de cycle, tous les
    // cycles pour le directeur general - deja gere par FiltersByCycle dans les controleurs).
    Route::middleware(['role_or_permission:directeur_general|directeur_maternelle|directeur_primaire|directeur_college|directeur_lycee|comptabilite.dashboard'])->prefix('comptabilite')->group(function () {
        // Dashboard
        Route::get('/', [ComptabiliteController::class, 'dashboard'])->name('comptabilite.dashboard');

        // Paiements eleves (consultation)
        Route::get('eleves', [ComptabiliteController::class, 'listeEleves'])->name('comptabilite.eleves');
        Route::get('eleves/{eleve}', [ComptabiliteController::class, 'ficheEleve'])->name('comptabilite.eleve.fiche');

        // Recus (consultation)
        Route::get('recus', [RecuController::class, 'index'])->name('recus.index');
        Route::get('recus/{recu}', [RecuController::class, 'show'])->name('recus.show');
        Route::get('recus/{recu}/pdf', [RecuController::class, 'pdf'])->name('recus.pdf');

        // Rapports
        Route::get('rapports', [ComptabiliteController::class, 'rapports'])->name('comptabilite.rapports');
        Route::get('rapports/en-retard', [ComptabiliteController::class, 'elevesEnRetard'])->name('comptabilite.retard');
    });

    // Encaissement (creation de paiement / generation de recu) : comptables et secretaires.
    // Les directeurs n'ont qu'un acces en consultation a la comptabilite (voir groupe ci-dessus).
    Route::middleware(['permission:comptabilite.dashboard'])->prefix('comptabilite')->group(function () {
        Route::get('eleves/{eleve}/payer', [PaiementController::class, 'create'])->name('paiements.create');
        Route::post('eleves/{eleve}/payer', [PaiementController::class, 'store'])->name('paiements.store');
    });

    // Annulation de recu : reservee aux comptables uniquement (ni secretaires, ni directeurs).
    Route::middleware(['permission:recus.annuler'])->prefix('comptabilite')->group(function () {
        Route::post('recus/{recu}/annuler', [RecuController::class, 'annuler'])->name('recus.annuler');
    });

    // Routes concernant les professeurs
    Route::controller(ProfesseurController::class)->middleware('deny.admin')->group(function () {
        Route::prefix('/professeurs')->group(function () {
            Route::get('/liste-des-enseignants', 'index')->name('professeur.index');
            Route::get('/ajouter-un-enseignant', 'create')->name('professeur.create');
            Route::post('/ajouter-un-enseignant', 'store')->name('professeur.store');
            Route::get('/modifier-informations/{professeur}', 'edit')->name('professeur.edit');
            Route::post('/modifier-informations/{professeur}', 'update')->name('professeur.update');
            Route::delete('/supprimer-enseignant/{professeur}', 'destroy')->name('professeur.destroy');
        });
    });



    // Routes concernant les matières
    Route::controller(MatiereController::class)->middleware('deny.admin')->group(function () {
        Route::prefix('/matiere')->group(function () {
            Route::get('/liste-des-matieres', 'index')->name('matiere.index');
            Route::get('/ajouter-une-matiere', 'create')->name('matiere.create');
            Route::post('/ajouter-une-matiere', 'store')->name('matiere.store');
            Route::get('/modifier-matiere/{matiere}', 'edit')->name('matiere.edit');
            Route::post('/modifier-matiere/{matiere}', 'update')->name('matiere.update');
            Route::delete('/supprimer-matiere/{matiere}', 'destroy')->name('matiere.delete');
        });
    });


    // Routes concernant les élèves
    Route::controller(EleveController::class)->middleware('deny.admin')->group(function () {
        Route::prefix('/eleve')->group(function () {
            Route::get('/ajouter-un-eleve', 'create')->name('eleve.create');
            Route::post('/ajouter-un-eleve', 'store')->name('eleve.store');
            Route::get('/modifier-informations/{eleve}/classe/{classe}', 'edit')->name('eleve.edit');
            Route::post('/modifier-informations/{eleve}', 'update')->name('eleve.update');
            Route::post('/passage-année-supérieure/{classe}', 'passageAnneeSup')->name('eleve.passage');
            Route::get('/details/{eleve}', 'show')->name('eleve.show');
            Route::delete('/supprimer/{eleve}', 'destroy')->name('eleve.destroy');
            Route::get('/export-eleves/classe/{classe}', 'export')->name('eleves.export');
            Route::get('/import-eleves/classe/{classe}', 'importPage')->name('eleves.importPage');
            Route::post('/import-eleves', 'import')->name('eleves.import');
            Route::get('/download-template', 'template')->name('eleves.template');
        });
    });


    //Routes concernant l'assiduité de l'élève 
    Route::controller(AssiduiteController::class)->middleware('deny.admin')->group(function () {
        Route::prefix('/assiduite')->group(function () {
            Route::get('/liste-des avertissements/{eleve}/classe/{classe}', 'index')->name('assiduite.index');
            Route::get('/ajouter-avertissement/eleve/{eleve}/trimestre/{trimestre}', 'create')->name('assiduite.create');
            Route::post('/ajouter-avertissement/eleve/{eleve}/trimestre/{trimestre}', 'store')->name('assiduite.store');
            Route::get('/modifier-avertissement/{assiduite}', 'edit')->name('assiduite.edit');
            Route::post('/modifier-avertissement/{assiduite}', 'update')->name('assiduite.update');
            Route::get('/comportement-de-l-eleve/{assiduite}/classe/{classe}', 'editComportement')->name('comportement.edit');
            Route::post('/comportement-de-l-eleve/{assiduite}', 'updateComportement')->name('comportement.update');
        });
    });


    //Routes concernant les retards de l'élève
    Route::controller(RetardController::class)->middleware('deny.admin')->group(function () {
        Route::prefix('/retard')->group(function () {
            Route::get('/liste-des-retards/{assiduite}/classe/{classe}', 'index')->name('retard.index');
            Route::get('/ajouter-un-retard/{assiduite}/classe/{classe}', 'create')->name('retard.create');
            Route::post('/ajouter-un-retard/{classe}', 'store')->name('retard.store');
            Route::delete('/supprimer-un-retard/{retard}', 'destroy')->name('retard.destroy');
        });
    });

    //Routes concernant les retards de l'élève
    Route::controller(AbsenceController::class)->middleware('deny.admin')->group(function () {
        Route::prefix('/absence')->group(function () {
            Route::get('/liste-des-absence/{assiduite}/classe/{classe}', 'index')->name('absence.index');
            Route::get('/ajouter-une-absence/{assiduite}/classe/{classe}', 'create')->name('absence.create');
            Route::post('/ajouter-une-absence/{classe}', 'store')->name('absence.store');
            Route::delete('/supprimer-une-absence/{absence}', 'destroy')->name('absence.destroy');
        });
    });



    // Routes concernant les classes
    Route::controller(ClasseController::class)->middleware('deny.admin')->group(function () {
        Route::prefix('/classe')->group(function () {
            Route::get('/ajouter-une-classe/promotion/{promotion}', 'listeClasses')->name('classe.list');
            Route::get('/ajouter-une-classe/{promotion}', 'create')->name('classe.create');
            Route::post('/ajouter-une-classe', 'store')->name('classe.store');
            Route::get('/liste-des-eleves/{classe}', 'index')->name('classe.index');
            Route::delete('/supprimer/classe/{classe}', 'destroy')->name('classe.destroy');
        });
    });


    // Routes concernant les évaluations
    Route::controller(EvaluationController::class)->middleware('deny.admin')->group(function () {
        Route::prefix('/evaluation')->group(function () {
            Route::get('/ajout-evaluation/{promotion}/{matiere}/{trimestre}', 'create')->name('evaluation.create');
            Route::get('/ajout-interrogation/{classe}/cours/{cours}/trimestre/{trimestre}', 'createInterrogation')->name('evaluation.create.interrogation');
            Route::post('/ajout-evaluation', 'store')->name('evaluation.store');
            Route::get('/ajout-evaluation/matieres/{promotion}', 'choixMatiere')->name('evaluation_matieres');
            Route::get('/liste-evaluations/matieres/{promotion}', 'choixMatiereViewEvaluation')->name('view_evaluation_matieres');
            Route::get('/liste-des-evaluations/{promotion}/{matiere}/{trimestre}', 'index')->name('evaluation.index');
            Route::get('/details-evaluation/{evaluation}/trimestre/{trimestre}/niveau/{promotion}', 'show')->name('evaluation.show');
            Route::post('/mise-a-jour/{evaluation}/trimestre/{trimestre}', 'update')->name('evaluation.update');
            Route::get('/ajout-interrogation/cours/classe/{classe}', 'choixCours')->name('interrogation.cours');
            Route::get('/liste-interrogations/classe/{classe}', 'choixCoursViewInterrogation')->name('view_interrogation_cours');
            Route::get('/liste-des-interrogations/classe/{classe}/cours/{cours}/trimestre{trimestre}', 'indexInterrogation')->name('interrogation.index');
            Route::delete('/supprimer/evaluation/{evaluation}', 'destroy')->name('evaluation.destroy');
        });
    });


    // Routes concernant l'année scolaire
    Route::controller(AnneeScolaireController::class)->group(function () {


        // Méthode AJAX
        Route::get('/changeYear/{anneeScolaire}', 'changeAppCurrentYear')->name('changeYear');
    });

    // Routes concernant les cours
    Route::controller(CoursController::class)->middleware('deny.admin')->group(function () {
        Route::prefix('/cours')->group(function () {
            Route::get('/classe/{classe}', 'index')->name('cours.index');
            Route::get('/{cours}', 'show')->name('cours.show');
            Route::post('/update/{cours}', 'update')->name('cours.update');
        });
    });





    //Route pour la génération de documents
    Route::controller(LaTexToPDFController::class)->middleware('deny.admin')->group(function () {
        Route::get('/liste-eleves/classe/{classe}', 'listesDesEleves')->name('listeDesEleves');
        Route::get('/fiche-informations-eleve/{eleve}', 'informationsEleve')->name('eleve.info');
        Route::get('/fiche-informations-eleve/classe/{classe}', 'informationsEleveAll')->name('eleve.classe.info');
        Route::get('/cartes-etudiantes/classe/{classe}', 'cartesEtudiantesClasse')->name('classe.cartes-etudiantes');
        Route::get('/bulletin/eleve/{eleve}/classe/{classe}/{trimestre}', 'bulletinTrimestre')->name('eleve.bulletin');
        Route::get('/bulletins-du-trimestre/classe/{classe}/trimestre/{trimestre}', 'bulletinsTrimestreClasse')->name('classe.bulletins');
    });
});
