<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LogViewerService
{
    /**
     * Taille maximale (en octets) lue depuis un fichier de log avant de se limiter
     * a sa derniere partie, pour eviter d'epuiser la memoire PHP sur un gros fichier
     */
    protected const MAX_READ_BYTES = 20 * 1024 * 1024;

    /**
     * Niveaux de log Monolog/PSR-3 reconnus, du plus au moins critique
     */
    protected const LEVELS = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];

    /**
     * Liste les fichiers de log disponibles dans storage/logs, du plus recent au plus ancien
     */
    public function getLogFiles(): Collection
    {
        $files = glob(storage_path('logs/laravel*.log')) ?: [];

        return collect($files)
            ->map(function (string $path) {
                return [
                    'name' => basename($path),
                    'size' => filesize($path),
                    'size_human' => $this->formatBytes(filesize($path)),
                    'modified_at' => date('Y-m-d H:i:s', filemtime($path)),
                    'mtime' => filemtime($path),
                ];
            })
            ->sortByDesc('mtime')
            ->values();
    }

    /**
     * Resout le chemin absolu d'un fichier de log, en verifiant qu'il fait partie
     * des fichiers autorises (protection contre le path traversal)
     */
    public function resolvePath(string $filename): ?string
    {
        $allowed = $this->getLogFiles()->pluck('name');

        if (! $allowed->contains(basename($filename))) {
            return null;
        }

        return storage_path('logs/' . basename($filename));
    }

    /**
     * Parse les entrees d'un fichier de log, filtrees et triees du plus recent au plus ancien
     */
    public function parseEntries(string $filename, array $filters = []): Collection
    {
        $path = $this->resolvePath($filename);

        if (! $path || ! is_file($path)) {
            return collect();
        }

        $content = $this->readTail($path);

        $chunks = preg_split('/(?=^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/m', $content, -1, PREG_SPLIT_NO_EMPTY);

        $entries = collect($chunks)
            ->map(fn (string $chunk) => $this->parseEntry($chunk))
            ->filter()
            ->reverse()
            ->values();

        $level = $filters['level'] ?? null;
        if ($level) {
            $entries = $entries->filter(fn (array $entry) => $entry['level'] === strtoupper($level))->values();
        }

        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $entries = $entries->filter(
                fn (array $entry) => Str::contains($entry['message'], $search, true)
            )->values();
        }

        return $entries;
    }

    /**
     * Vide le contenu d'un fichier de log (action destructive)
     */
    public function clear(string $filename): bool
    {
        $path = $this->resolvePath($filename);

        if (! $path || ! is_file($path)) {
            return false;
        }

        return file_put_contents($path, '') !== false;
    }

    /**
     * Lit un fichier en se limitant a sa derniere partie s'il depasse MAX_READ_BYTES
     */
    protected function readTail(string $path): string
    {
        $size = filesize($path);

        if ($size <= self::MAX_READ_BYTES) {
            return file_get_contents($path) ?: '';
        }

        $handle = fopen($path, 'r');
        fseek($handle, -self::MAX_READ_BYTES, SEEK_END);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content ?: '';
    }

    /**
     * Extrait datetime/environnement/niveau/message/utilisateur d'un bloc d'entree brut
     */
    protected function parseEntry(string $chunk): ?array
    {
        $levelsPattern = implode('|', self::LEVELS);

        if (! preg_match(
            '/^\[(?<datetime>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (?<env>\S+)\.(?<level>' . $levelsPattern . '): (?<message>.*)$/us',
            $chunk,
            $matches
        )) {
            return null;
        }

        $firstLine = strtok($matches['message'], "\n");

        return [
            'datetime' => $matches['datetime'],
            'environment' => $matches['env'],
            'level' => $matches['level'],
            'message' => trim($firstLine),
            'user' => $this->resolveUser($chunk),
            'raw' => trim($chunk),
        ];
    }

    /**
     * Resout le nom de l'utilisateur associe a une entree via le "userId" attache
     * automatiquement par Laravel au contexte de chaque exception loguee
     */
    protected function resolveUser(string $chunk): ?string
    {
        if (! preg_match('/"userId":\s*(\d+)/', $chunk, $matches)) {
            return null;
        }

        $user = User::find((int) $matches[1]);

        if (! $user) {
            return null;
        }

        $name = trim(($user->prenom ?? '') . ' ' . ($user->nom ?? ''));

        return $name !== '' ? $name : $user->username;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' Mo';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' Ko';
        }

        return $bytes . ' octets';
    }
}
