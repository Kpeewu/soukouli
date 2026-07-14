<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Setting;
use Illuminate\Http\Request;

class BulletinHeaderConfigController extends Controller
{
    public const SETTING_KEY = 'bulletin_header_layout';

    public const CANVAS_WIDTH = 794;

    public const HEADER_HEIGHT = 300;

    /**
     * Disposition par defaut (= approximation de la disposition historique du bulletin),
     * position {x,y} en pixels de chaque bloc dans la zone d'en-tete.
     */
    public const DEFAULT_LAYOUT = [
        'ministere' => ['x' => 20, 'y' => 10],
        'ecole_info' => ['x' => 20, 'y' => 70],
        'titre_bulletin' => ['x' => 20, 'y' => 180],
        'logo' => ['x' => 350, 'y' => 10],
        'republique' => ['x' => 550, 'y' => 10],
        'annee_scolaire' => ['x' => 550, 'y' => 70],
        'classe_effectif' => ['x' => 550, 'y' => 100],
        'nd_table' => ['x' => 680, 'y' => 100],
        'infos_eleve' => ['x' => 100, 'y' => 230],
    ];

    /**
     * Libelles lisibles des blocs disponibles.
     */
    public const LABELS = [
        'ministere' => 'Mention Ministère (réglementaire)',
        'ecole_info' => 'Informations établissement',
        'titre_bulletin' => 'Titre "Bulletin de notes du ... trimestre"',
        'republique' => 'République Togolaise + devise',
        'annee_scolaire' => 'Année scolaire',
        'classe_effectif' => 'Tableau Classe / Effectif',
        'nd_table' => 'Tableau Nouveau / Doublant',
        'logo' => 'Logo établissement',
        'infos_eleve' => 'Nom, prénoms, sexe, date de naissance',
    ];

    /**
     * Positions non enregistrees en cours d'edition, appliquees le temps d'une
     * requete "Apercu PDF" sans toucher a Setting.
     */
    private static ?array $previewOverride = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            // Disposition partagee par tous les cycles : les secretaires de
            // cycle ne doivent pas pouvoir la modifier, seul le secretaire
            // general (portee transverse) le peut, au meme titre qu'un directeur.
            if (!$user->isDirecteur() && !$user->hasRole('secretaire_general')) {
                abort(403, "Seuls les directeurs et le secretaire general peuvent configurer la disposition de l'en-tete des bulletins.");
            }
            return $next($request);
        });
    }

    public static function getLayout(): array
    {
        if (self::$previewOverride !== null) {
            return self::$previewOverride;
        }

        $raw = Setting::get(self::SETTING_KEY);

        if (!$raw) {
            return self::DEFAULT_LAYOUT;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : self::DEFAULT_LAYOUT;
    }

    public function edit(LaTexToPDFController $bulletinController)
    {
        [$eleve, $classe, $trimestre] = $this->resolveSample();
        $sample = $bulletinController->buildBulletinData($eleve, $classe, $trimestre);

        // buildBulletinData() fournit un chemin filesystem absolu (pour dompdf),
        // mais cette page est affichee dans un vrai navigateur qui a besoin d'une URL.
        $sample['logo'] = Setting::getLogoUrl();

        return view('admin.bulletin-config.header', array_merge($sample, [
            'positions' => self::getLayout(),
            'labels' => self::LABELS,
            'canvasWidth' => self::CANVAS_WIDTH,
            'headerHeight' => self::HEADER_HEIGHT,
        ]));
    }

    public function update(Request $request)
    {
        $positions = $this->validatePositions($request);

        Setting::set(self::SETTING_KEY, json_encode($positions), 'json', 'bulletin');

        return redirect()->route('bulletin-config.header')
            ->with('notification', ['type' => 'success', 'message' => "Disposition de l'en-tete mise a jour"]);
    }

    public function reset()
    {
        Setting::set(self::SETTING_KEY, json_encode(self::DEFAULT_LAYOUT), 'json', 'bulletin');

        return redirect()->route('bulletin-config.header')
            ->with('notification', ['type' => 'info', 'message' => 'Disposition reinitialisee']);
    }

    /**
     * Genere un bulletin PDF d'exemple avec la disposition en cours d'edition
     * (non enregistree) pour verification avant sauvegarde.
     */
    public function preview(Request $request, LaTexToPDFController $bulletinController)
    {
        self::$previewOverride = $this->validatePositions($request);

        [$eleve, $classe, $trimestre] = $this->resolveSample();

        return $bulletinController->bulletinTrimestre($eleve, $classe, $trimestre);
    }

    private function validatePositions(Request $request): array
    {
        $rules = ['positions' => ['required', 'array']];

        foreach (array_keys(self::LABELS) as $bloc) {
            $rules["positions.$bloc.x"] = ['required', 'integer', 'min:0', 'max:' . self::CANVAS_WIDTH];
            $rules["positions.$bloc.y"] = ['required', 'integer', 'min:0', 'max:' . self::HEADER_HEIGHT];
        }

        $validated = $request->validate($rules);

        return collect(array_keys(self::LABELS))->mapWithKeys(fn ($bloc) => [
            $bloc => [
                'x' => (int) $validated['positions'][$bloc]['x'],
                'y' => (int) $validated['positions'][$bloc]['y'],
            ],
        ])->all();
    }

    private function resolveSample(): array
    {
        $eleve = Eleve::whereHas('classes.promotion.trimestres')->first();

        abort_if(!$eleve, 404, "Aucune donnee disponible pour generer un apercu.");

        $classe = $eleve->classes()->with('promotion.trimestres')->first();
        $trimestre = $classe->promotion->trimestres->first();

        return [$eleve, $classe, $trimestre];
    }

}
