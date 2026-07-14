<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Afficher la page de configuration
     */
    public function index()
    {
        $settings = $this->settingsService->all();
        $groups = [
            'general' => 'Informations generales',
            'contact' => 'Contact',
            'display' => 'Affichage'
        ];

        return view('admin.settings.index', compact('settings', 'groups'));
    }

    /**
     * Mettre a jour les parametres
     */
    public function update(Request $request)
    {
        $request->validate([
            'school_name' => 'required|string|max:255',
            'school_full_name' => 'required|string|max:255',
            'school_type' => 'required|string|max:100',
            'school_motto' => 'nullable|string|max:255',
            'school_bp' => 'nullable|string|max:50',
            'school_city' => 'nullable|string|max:100',
            'school_country' => 'nullable|string|max:100',
            'school_phone' => 'nullable|string|max:50',
            'school_email' => 'nullable|email|max:255',
            'school_address' => 'nullable|string|max:500',
            'system_name' => 'nullable|string|max:100',
            'system_version' => 'nullable|string|max:20',
            'school_logo' => 'nullable|image|mimes:png,jpg,jpeg,gif,svg|max:2048',
            'login_background' => 'nullable|image|mimes:png,jpg,jpeg|max:4096',
        ]);

        // Mettre a jour les parametres textuels
        $textSettings = [
            'school_name',
            'school_full_name',
            'school_type',
            'school_motto',
            'school_bp',
            'school_city',
            'school_country',
            'school_phone',
            'school_email',
            'school_address',
            'system_name',
            'system_version',
        ];

        foreach ($textSettings as $key) {
            if ($request->has($key)) {
                $existing = Setting::where('key', $key)->first();
                $type = $existing ? $existing->type : 'string';
                $group = $existing ? $existing->group : 'general';

                Setting::set($key, $request->input($key), $type, $group);
            }
        }

        // Gerer l'upload du logo
        if ($request->hasFile('school_logo')) {
            $this->settingsService->uploadLogo($request->file('school_logo'));
        }

        // Gerer l'upload de l'image de fond
        if ($request->hasFile('login_background')) {
            $this->settingsService->uploadLoginBackground($request->file('login_background'));
        }

        // Invalider le cache
        $this->settingsService->clearCache();

        return redirect()->route('settings.index')
            ->with('notification', [
                'type' => 'success',
                'message' => 'Parametres de l\'etablissement mis a jour avec succes.'
            ]);
    }

    /**
     * Reinitialiser les parametres par defaut
     */
    public function reset()
    {
        $defaults = $this->settingsService->getDefaults();

        foreach ($defaults as $key => $data) {
            Setting::set($key, $data['value'], $data['type'], $data['group']);
        }

        $this->settingsService->clearCache();

        return redirect()->route('settings.index')
            ->with('notification', [
                'type' => 'info',
                'message' => 'Parametres reinitialises aux valeurs par defaut.'
            ]);
    }
}
