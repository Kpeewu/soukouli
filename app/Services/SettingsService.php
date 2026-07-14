<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class SettingsService
{
    /**
     * Taille maximale du logo (largeur en pixels)
     */
    protected const LOGO_MAX_WIDTH = 300;

    /**
     * Taille maximale du logo (hauteur en pixels)
     */
    protected const LOGO_MAX_HEIGHT = 300;

    /**
     * Taille maximale de l'image de fond (largeur en pixels)
     */
    protected const BACKGROUND_MAX_WIDTH = 1920;

    /**
     * Taille maximale de l'image de fond (hauteur en pixels)
     */
    protected const BACKGROUND_MAX_HEIGHT = 1080;

    /**
     * Qualite de compression JPEG (0-100)
     */
    protected const IMAGE_QUALITY = 85;
    /**
     * Recuperer un parametre
     */
    public function get(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Definir un parametre
     */
    public function set(string $key, $value, string $type = 'string', string $group = 'general'): Setting
    {
        return Setting::set($key, $value, $type, $group);
    }

    /**
     * Recuperer tous les parametres
     */
    public function all(): array
    {
        $settings = Setting::getAllCached();

        // Ajouter les URLs generees
        $settings['school_logo_url'] = Setting::getLogoUrl();
        $settings['login_background_url'] = Setting::getLoginBackgroundUrl();
        $settings['school_full_name'] = Setting::getSchoolFullName();
        $settings['school_full_address'] = Setting::getFullAddress();

        return $settings;
    }

    /**
     * Recuperer les parametres par groupe
     */
    public function getByGroup(string $group): array
    {
        return Setting::getByGroup($group);
    }

    /**
     * Mettre a jour plusieurs parametres a la fois
     */
    public function updateMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if ($value !== null) {
                $existing = Setting::where('key', $key)->first();
                $type = $existing ? $existing->type : 'string';
                $group = $existing ? $existing->group : 'general';

                Setting::set($key, $value, $type, $group);
            }
        }
    }

    /**
     * Gerer l'upload du logo
     */
    public function uploadLogo(UploadedFile $file): string
    {
        // Supprimer l'ancien logo si c'est dans storage
        $oldLogo = $this->get('school_logo');
        if ($oldLogo && str_starts_with($oldLogo, 'settings/')) {
            Storage::disk('public')->delete($oldLogo);
        }

        // Determiner l'extension (forcer PNG pour meilleure qualite des logos)
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'svg'])) {
            $extension = 'png';
        }

        // Pour les SVG, pas de redimensionnement
        if ($extension === 'svg') {
            $filename = 'logo.svg';
            $path = $file->storeAs('settings', $filename, 'public');
        } else {
            // Redimensionner l'image
            $filename = 'logo.png'; // Convertir en PNG pour meilleure qualite
            $path = 'settings/' . $filename;
            $fullPath = storage_path('app/public/' . $path);

            // S'assurer que le repertoire existe
            if (!is_dir(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            // Redimensionner et sauvegarder
            $image = Image::read($file->getPathname());
            $image->scaleDown(self::LOGO_MAX_WIDTH, self::LOGO_MAX_HEIGHT);
            $image->toPng()->save($fullPath);
        }

        // Mettre a jour le parametre
        $this->set('school_logo', $path, 'image', 'display');

        return $path;
    }

    /**
     * Gerer l'upload de l'image de fond
     */
    public function uploadLoginBackground(UploadedFile $file): string
    {
        // Supprimer l'ancienne image si c'est dans storage
        $oldBg = $this->get('login_background');
        if ($oldBg && str_starts_with($oldBg, 'settings/')) {
            Storage::disk('public')->delete($oldBg);
        }

        // Redimensionner et compresser l'image de fond
        $filename = 'login_background.jpg'; // Utiliser JPG pour les photos (meilleure compression)
        $path = 'settings/' . $filename;
        $fullPath = storage_path('app/public/' . $path);

        // S'assurer que le repertoire existe
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Redimensionner et sauvegarder en JPEG avec compression
        $image = Image::read($file->getPathname());
        $image->scaleDown(self::BACKGROUND_MAX_WIDTH, self::BACKGROUND_MAX_HEIGHT);
        $image->toJpeg(self::IMAGE_QUALITY)->save($fullPath);

        // Mettre a jour le parametre
        $this->set('login_background', $path, 'image', 'display');

        return $path;
    }

    /**
     * Reinitialiser un parametre a sa valeur par defaut
     */
    public function resetToDefault(string $key): void
    {
        $defaults = $this->getDefaults();

        if (isset($defaults[$key])) {
            $this->set($key, $defaults[$key]['value'], $defaults[$key]['type'], $defaults[$key]['group']);
        }
    }

    /**
     * Valeurs par defaut des parametres
     */
    public function getDefaults(): array
    {
        return [
            'school_name' => ['value' => 'Soukouli', 'type' => 'string', 'group' => 'general'],
            'school_full_name' => ['value' => 'Soukouli', 'type' => 'string', 'group' => 'general'],
            'school_type' => ['value' => 'COMPLEXE SCOLAIRE', 'type' => 'string', 'group' => 'general'],
            'school_motto' => ['value' => 'Travail - Discipline - Succes', 'type' => 'string', 'group' => 'general'],
            'school_bp' => ['value' => 'BP: 68', 'type' => 'string', 'group' => 'contact'],
            'school_city' => ['value' => 'SOKODE', 'type' => 'string', 'group' => 'contact'],
            'school_country' => ['value' => 'TOGO', 'type' => 'string', 'group' => 'contact'],
            'school_phone' => ['value' => '', 'type' => 'string', 'group' => 'contact'],
            'school_email' => ['value' => '', 'type' => 'string', 'group' => 'contact'],
            'school_logo' => ['value' => 'assets/images/logo2.png', 'type' => 'image', 'group' => 'display'],
            'login_background' => ['value' => 'assets/images/primaire.jpg', 'type' => 'image', 'group' => 'display'],
            'system_name' => ['value' => 'Soukouli', 'type' => 'string', 'group' => 'general'],
            'system_version' => ['value' => '1.1.0', 'type' => 'string', 'group' => 'general'],
        ];
    }

    /**
     * Invalider le cache
     */
    public function clearCache(): void
    {
        Setting::clearCache();
    }
}
