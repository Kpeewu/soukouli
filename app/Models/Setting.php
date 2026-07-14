<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    /**
     * Cle de cache pour les parametres
     */
    protected const CACHE_KEY = 'app_settings';

    /**
     * Duree du cache en secondes (24 heures)
     */
    protected const CACHE_TTL = 86400;

    /**
     * Recuperer un parametre par sa cle
     */
    public static function get(string $key, $default = null)
    {
        $settings = self::getAllCached();

        return $settings[$key] ?? $default;
    }

    /**
     * Definir un parametre
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );

        // Invalider le cache
        self::clearCache();

        return $setting;
    }

    /**
     * Recuperer tous les parametres avec cache
     */
    public static function getAllCached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Recuperer les parametres par groupe
     */
    public static function getByGroup(string $group): array
    {
        return self::where('group', $group)->pluck('value', 'key')->toArray();
    }

    /**
     * Invalider le cache des parametres
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Recuperer l'URL du logo
     */
    public static function getLogoUrl(): string
    {
        $logo = self::get('school_logo', 'assets/images/logo2.png');

        // Si c'est un chemin relatif dans storage
        if (str_starts_with($logo, 'settings/')) {
            return asset('storage/' . $logo);
        }

        // Sinon c'est un chemin dans public/assets
        return asset($logo);
    }

    /**
     * Recuperer l'URL de l'image de fond de connexion
     */
    public static function getLoginBackgroundUrl(): string
    {
        $bg = self::get('login_background', 'assets/images/primaire.jpg');

        if (str_starts_with($bg, 'settings/')) {
            return asset('storage/' . $bg);
        }

        return asset($bg);
    }

    /**
     * Recuperer le chemin absolu du logo pour LaTeX
     */
    public static function getLogoPath(): string
    {
        $logo = self::get('school_logo', 'assets/images/logo2.png');

        if (str_starts_with($logo, 'settings/')) {
            return storage_path('app/public/' . $logo);
        }

        return public_path($logo);
    }

    /**
     * Recuperer le nom complet de l'ecole
     */
    public static function getSchoolFullName(): string
    {
        $type = self::get('school_type', 'COMPLEXE SCOLAIRE');
        $name = self::get('school_name', 'SOUKOULI');

        return $type . ' ' . $name;
    }

    /**
     * Recuperer l'adresse complete
     */
    public static function getFullAddress(): string
    {
        $bp = self::get('school_bp', 'BP: 68');
        $city = self::get('school_city', 'LOME');
        $country = self::get('school_country', 'TOGO');

        return $bp . ' ' . $city . ' - ' . $country;
    }
}
