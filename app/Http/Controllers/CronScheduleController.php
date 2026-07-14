<?php

namespace App\Http\Controllers;

use App\Services\CronScheduleService;
use Illuminate\Http\Request;

class CronScheduleController extends Controller
{
    protected CronScheduleService $cronScheduleService;

    public function __construct(CronScheduleService $cronScheduleService)
    {
        $this->cronScheduleService = $cronScheduleService;
    }

    /**
     * Afficher la liste des taches planifiees et leur configuration
     */
    public function index()
    {
        $tasks = collect($this->cronScheduleService->all())
            ->map(function (array $task) {
                $task['log'] = $this->cronScheduleService->readLog($task['key']);

                return $task;
            });

        return view('admin.crons.index', compact('tasks'));
    }

    /**
     * Mettre a jour la configuration (active/mois) d'une tache
     */
    public function updateConfig(Request $request, string $key)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'month' => 'required|integer|between:1,12',
        ]);

        $this->cronScheduleService->updateConfig($key, (bool) ($validated['enabled'] ?? false), (int) $validated['month']);

        return redirect()->route('crons.index')
            ->with('notification', ['type' => 'success', 'message' => 'Configuration mise a jour avec succes.']);
    }

    /**
     * Retourne la derniere sortie d'execution d'une tache (interroge par le polling JS)
     */
    public function log(string $key)
    {
        return response()->json($this->cronScheduleService->readLog($key));
    }
}
