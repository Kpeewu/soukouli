<?php

namespace App\Services;

use App\Models\Setting;

class CronScheduleService
{
    public const KEY_ANNEE_ENABLED = 'cron_annee_scolaire_enabled';
    public const KEY_ANNEE_MONTH = 'cron_annee_scolaire_month';
    public const KEY_PASSAGE_ENABLED = 'cron_passage_eleves_enabled';
    public const KEY_PASSAGE_MONTH = 'cron_passage_eleves_month';

    /**
     * Definition statique des taches planifiees administrables
     */
    protected const TASKS = [
        'annee-scolaire' => [
            'label' => 'Passage à la nouvelle année scolaire',
            'description' => "Crée automatiquement l'année scolaire suivante (promotions, classes, configurations de frais, cours) le mois configuré. Ne bascule pas l'année courante : sans effet sur les données live tant qu'elle n'a pas été activée manuellement.",
            'command' => 'anneeScolaire:change',
            'log_file' => 'nouvelleAnneeSchedule.log',
            'enabled_key' => self::KEY_ANNEE_ENABLED,
            'month_key' => self::KEY_ANNEE_MONTH,
            'default_month' => 8,
            'default_enabled' => true,
            'manual_route' => 'annees-scolaires.generer',
            'manual_method' => 'post',
        ],
        'passage-eleves' => [
            'label' => 'Passage des élèves en classe supérieure',
            'description' => "Fait passer les élèves en classe supérieure (ou les diplôme en fin de cycle) le mois configuré. Désactivée par défaut : le passage en masse automatique reste une action à activer explicitement, cohérente avec l'activation manuelle de l'année scolaire.",
            'command' => 'eleves:passage',
            'log_file' => 'passageElevesSchedule.log',
            'enabled_key' => self::KEY_PASSAGE_ENABLED,
            'month_key' => self::KEY_PASSAGE_MONTH,
            'default_month' => 8,
            'default_enabled' => false,
            'manual_route' => 'passage.index',
            'manual_method' => 'link',
        ],
    ];

    /**
     * Determine si une tache doit s'executer ce mois-ci, utilise par Kernel::schedule()
     */
    public static function isEnabledThisMonth(string $enabledKey, string $monthKey, int $defaultMonth, bool $defaultEnabled = true): bool
    {
        return (bool) Setting::get($enabledKey, $defaultEnabled)
            && now()->month === (int) Setting::get($monthKey, $defaultMonth);
    }

    /**
     * Retourne toutes les taches administrables avec leur configuration actuelle
     */
    public function all(): array
    {
        return collect(self::TASKS)
            ->map(function (array $task, string $key) {
                $task['key'] = $key;
                $task['enabled'] = (bool) Setting::get($task['enabled_key'], $task['default_enabled'] ?? true);
                $task['month'] = (int) Setting::get($task['month_key'], $task['default_month']);

                return $task;
            })
            ->values()
            ->all();
    }

    /**
     * Met a jour la configuration (active/mois) d'une tache
     */
    public function updateConfig(string $key, bool $enabled, int $month): void
    {
        $task = $this->findTask($key);

        Setting::set($task['enabled_key'], $enabled ? '1' : '0', 'boolean', 'cron');
        Setting::set($task['month_key'], (string) $month, 'integer', 'cron');
    }

    /**
     * Lit le contenu brut du fichier de log d'une tache (ecrase a chaque execution)
     */
    public function readLog(string $key): array
    {
        $task = $this->findTask($key);
        $path = storage_path('logs/' . $task['log_file']);

        if (! is_file($path) || filesize($path) === 0) {
            return ['content' => '', 'modified_at' => null];
        }

        return [
            'content' => file_get_contents($path),
            'modified_at' => date('Y-m-d H:i:s', filemtime($path)),
        ];
    }

    /**
     * Retrouve la definition d'une tache, ou abort(404) si la cle est inconnue
     */
    protected function findTask(string $key): array
    {
        abort_unless(array_key_exists($key, self::TASKS), 404);

        return self::TASKS[$key];
    }
}
