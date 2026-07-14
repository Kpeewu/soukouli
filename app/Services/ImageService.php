<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    /**
     * Taille maximale des photos d'eleves (largeur en pixels)
     */
    protected const STUDENT_PHOTO_MAX_WIDTH = 400;

    /**
     * Taille maximale des photos d'eleves (hauteur en pixels)
     */
    protected const STUDENT_PHOTO_MAX_HEIGHT = 500;

    /**
     * Qualite de compression JPEG (0-100)
     */
    protected const IMAGE_QUALITY = 85;

    /**
     * Image par defaut pour les eleves
     */
    public const DEFAULT_STUDENT_PHOTO = 'assets/media/avatars/avatar1.jpg';

    /**
     * Upload et redimensionne la photo d'un eleve
     *
     * @param UploadedFile $file Fichier uploade
     * @param string $annee Annee scolaire (ex: "2024-2025")
     * @param string $classe Nom de la classe simplifie
     * @param string $matricule Matricule de l'eleve
     * @return string Chemin relatif de l'image stockee
     */
    public function uploadStudentPhoto(UploadedFile $file, string $annee, string $classe, string $matricule): string
    {
        // Nettoyer le nom de la classe pour le chemin
        $classePath = preg_replace('/[^a-zA-Z0-9]/', '', $classe);

        // Definir le chemin de stockage
        $directory = "eleves/{$annee}/{$classePath}";
        $filename = strtolower($matricule) . '.jpg';
        $path = "{$directory}/{$filename}";
        $fullPath = storage_path("app/public/{$path}");

        // S'assurer que le repertoire existe
        $dirPath = dirname($fullPath);
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        // Redimensionner et sauvegarder l'image
        $image = Image::read($file->getPathname());
        $image->scaleDown(self::STUDENT_PHOTO_MAX_WIDTH, self::STUDENT_PHOTO_MAX_HEIGHT);
        $image->toJpeg(self::IMAGE_QUALITY)->save($fullPath);

        return $path;
    }

    /**
     * Supprime l'ancienne photo d'un eleve si elle existe
     *
     * @param string|null $oldPath Ancien chemin de la photo
     * @return bool True si la suppression a reussi
     */
    public function deleteStudentPhoto(?string $oldPath): bool
    {
        if ($oldPath && str_starts_with($oldPath, 'eleves/')) {
            return Storage::disk('public')->delete($oldPath);
        }

        return false;
    }

    /**
     * Recupere l'URL de la photo d'un eleve
     *
     * @param string|null $path Chemin de la photo
     * @return string URL de la photo ou de l'image par defaut
     */
    public function getStudentPhotoUrl(?string $path): string
    {
        if ($path && $this->studentPhotoExists($path)) {
            return asset('storage/' . $path);
        }

        return asset(self::DEFAULT_STUDENT_PHOTO);
    }

    /**
     * Verifie si la photo d'un eleve existe
     *
     * @param string|null $path Chemin de la photo
     * @return bool
     */
    public function studentPhotoExists(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        // Si c'est un chemin dans storage
        if (str_starts_with($path, 'eleves/')) {
            return Storage::disk('public')->exists($path);
        }

        // Si c'est un ancien chemin (compatibilite)
        if (str_starts_with($path, '/')) {
            return file_exists($path);
        }

        return file_exists(public_path('storage/' . $path));
    }

    /**
     * Recupere le chemin absolu de la photo pour LaTeX
     *
     * @param string|null $path Chemin de la photo
     * @return string Chemin absolu
     */
    public function getStudentPhotoPath(?string $path): string
    {
        if ($path && $this->studentPhotoExists($path)) {
            if (str_starts_with($path, 'eleves/')) {
                return storage_path('app/public/' . $path);
            }

            // Compatibilite avec les anciens chemins
            if (str_starts_with($path, '/')) {
                return $path;
            }

            return storage_path('app/public/' . $path);
        }

        return public_path(self::DEFAULT_STUDENT_PHOTO);
    }
}
