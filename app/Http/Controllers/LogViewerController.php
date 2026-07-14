<?php

namespace App\Http\Controllers;

use App\Services\LogViewerService;
use Illuminate\Http\Request;

class LogViewerController extends Controller
{
    protected LogViewerService $logViewerService;

    public function __construct(LogViewerService $logViewerService)
    {
        $this->logViewerService = $logViewerService;
    }

    /**
     * Afficher la liste des fichiers de log et les entrees du fichier selectionne
     */
    public function index(Request $request)
    {
        $files = $this->logViewerService->getLogFiles();
        $currentFile = $request->query('file', optional($files->first())['name']);

        $filters = [
            'level' => $request->query('level'),
            'q' => $request->query('q'),
        ];

        $entries = $currentFile
            ? $this->logViewerService->parseEntries($currentFile, $filters)
            : collect();

        return view('admin.logs.index', compact('files', 'currentFile', 'entries', 'filters'));
    }

    /**
     * Telecharger le fichier de log brut selectionne
     */
    public function download(Request $request)
    {
        $request->validate(['file' => 'required|string']);

        $path = $this->logViewerService->resolvePath($request->query('file'));

        abort_unless($path, 404);

        return response()->download($path);
    }

    /**
     * Vider le contenu d'un fichier de log (action destructive)
     */
    public function clear(Request $request)
    {
        $request->validate(['file' => 'required|string']);

        $cleared = $this->logViewerService->clear($request->input('file'));

        return redirect()->route('logs.index', ['file' => $request->input('file')])
            ->with('notification', $cleared
                ? ['type' => 'success', 'message' => 'Le fichier de log a ete vide avec succes.']
                : ['type' => 'danger', 'message' => 'Impossible de vider ce fichier de log.']);
    }
}
