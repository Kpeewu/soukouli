<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mode demonstration
    |--------------------------------------------------------------------------
    |
    | Lorsqu'il est actif, l'application charge un jeu de donnees fictives
    | complet au premier demarrage et affiche une banniere rappelant les
    | identifiants de test.
    |
    | ATTENTION : `php artisan demo:reset` efface INTEGRALEMENT la base de
    | donnees. Ne jamais activer ce mode sur une installation client.
    |
    | Note : env() n'est lu que dans ce fichier. L'entrypoint Docker execute
    | `php artisan config:cache`, apres quoi env() renvoie null ailleurs dans
    | l'application. Partout ailleurs, utiliser config('demo.*').
    |
    */

    'enabled' => (bool) env('DEMO_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Reinitialisation automatique
    |--------------------------------------------------------------------------
    |
    | Branche `demo:reset` sur le scheduler pour que la demo reparte propre
    | chaque nuit, quoi qu'aient fait les visiteurs. Necessite DEMO_MODE=true.
    |
    */

    'auto_reset' => (bool) env('DEMO_AUTO_RESET', false),

    'auto_reset_time' => env('DEMO_AUTO_RESET_TIME', '03:00'),

    /*
    |--------------------------------------------------------------------------
    | Volumetrie
    |--------------------------------------------------------------------------
    |
    | Volume reduit par rapport au seeder de developpement : 15 promotions x
    | 1 classe x 15-20 eleves, soit environ 260 eleves. Assez pour demontrer
    | le cloisonnement par cycle, assez peu pour que le seeding et les listes
    | restent rapides.
    |
    */

    'classes_par_promotion' => (int) env('DEMO_CLASSES_PAR_PROMOTION', 1),

    'eleves_min' => (int) env('DEMO_ELEVES_MIN', 15),

    'eleves_max' => (int) env('DEMO_ELEVES_MAX', 20),

    /*
    |--------------------------------------------------------------------------
    | Comptes de test affiches dans la banniere
    |--------------------------------------------------------------------------
    |
    | Doivent rester synchrones avec database/seeders/UserSeeder.php.
    | Un compte par famille de role, en variante "general" (acces a tous les
    | cycles) : c'est ce qui montre le plus de l'application.
    |
    */

    'credentials' => [
        ['role' => 'Administrateur',    'login' => 'admin',               'password' => 'admin123'],
        ['role' => 'Directeur',         'login' => 'directeur.general',   'password' => 'general123'],
        ['role' => 'Secretaire',        'login' => 'secretaire.general',  'password' => 'secretaire123'],
        ['role' => 'Comptable',         'login' => 'comptable.general',   'password' => 'general123'],
        ['role' => 'Surveillant',       'login' => 'surveillant.general', 'password' => 'general123'],
        ['role' => 'Professeur',        'login' => 'professeur',          'password' => 'prof123'],
    ],

];
