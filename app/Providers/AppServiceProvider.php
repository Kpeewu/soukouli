<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrer le service de parametres comme singleton
        $this->app->singleton(SettingsService::class, function ($app) {
            return new SettingsService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Partager les parametres de l'etablissement avec toutes les vues
        view()->composer('*', function ($view) {
            // Verifier que la table settings existe (pour eviter les erreurs lors des migrations)
            if (Schema::hasTable('settings')) {
                $settingsService = app(SettingsService::class);
                $view->with('schoolSettings', $settingsService->all());
            } else {
                // Valeurs par defaut si la table n'existe pas encore
                $view->with('schoolSettings', [
                    'school_name' => 'Mon Avenir',
                    'school_full_name' => 'Complexe Prive Laique Mon Avenir',
                    'school_type' => 'COMPLEXE SCOLAIRE',
                    'school_motto' => 'Travail - Discipline - Succes',
                    'school_bp' => 'BP: 68',
                    'school_city' => 'SOKODE',
                    'school_country' => 'TOGO',
                    'school_phone' => '',
                    'school_email' => '',
                    'school_logo' => 'assets/images/logo2.png',
                    'school_logo_url' => asset('assets/images/logo2.png'),
                    'login_background' => 'assets/images/primaire.jpg',
                    'login_background_url' => asset('assets/images/primaire.jpg'),
                    'system_name' => 'Soukouli',
                    'system_version' => '1.0',
                    'school_full_name' => 'COMPLEXE SCOLAIRE MON AVENIR',
                    'school_full_address' => 'BP: 68 SOKODE - TOGO',
                ]);
            }
        });
    }
}
