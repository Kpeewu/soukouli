<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Parametres generaux
            [
                'key' => 'school_name',
                'value' => 'Soukouli',
                'type' => 'string',
                'group' => 'general'
            ],
            [
                'key' => 'school_full_name',
                'value' => '',
                'type' => 'string',
                'group' => 'general'
            ],
            [
                'key' => 'school_type',
                'value' => 'COMPLEXE SCOLAIRE',
                'type' => 'string',
                'group' => 'general'
            ],
            [
                'key' => 'school_motto',
                'value' => 'Devise',
                'type' => 'string',
                'group' => 'general'
            ],
            [
                'key' => 'system_name',
                'value' => 'Soukouli',
                'type' => 'string',
                'group' => 'general'
            ],
            [
                'key' => 'system_version',
                'value' => '1.1.0',
                'type' => 'string',
                'group' => 'general'
            ],

            // Parametres de contact
            [
                'key' => 'school_bp',
                'value' => 'BP: 68',
                'type' => 'string',
                'group' => 'contact'
            ],
            [
                'key' => 'school_city',
                'value' => 'SOKODE',
                'type' => 'string',
                'group' => 'contact'
            ],
            [
                'key' => 'school_country',
                'value' => 'TOGO',
                'type' => 'string',
                'group' => 'contact'
            ],
            [
                'key' => 'school_phone',
                'value' => '',
                'type' => 'string',
                'group' => 'contact'
            ],
            [
                'key' => 'school_email',
                'value' => '',
                'type' => 'string',
                'group' => 'contact'
            ],
            [
                'key' => 'school_address',
                'value' => '',
                'type' => 'text',
                'group' => 'contact'
            ],

            // Parametres d'affichage
            [
                'key' => 'school_logo',
                'value' => 'assets/images/logo.png',
                'type' => 'image',
                'group' => 'display'
            ],
            [
                'key' => 'login_background',
                'value' => 'assets/images/background.png',
                'type' => 'image',
                'group' => 'display'
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Parametres de l\'etablissement initialises avec succes.');
    }
}
