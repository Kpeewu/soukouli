<?php

namespace Database\Seeders\Support;

use App\Models\AnneeScolaire;
use Carbon\Carbon;

/**
 * Fenetres de dates des periodes de notation, derivees de l'annee scolaire.
 *
 * La table `trimestres` ne porte volontairement pas de colonnes date_debut /
 * date_fin : l'appartenance d'une note a un trimestre est portee par
 * notes.trimestre_id, saisi par l'utilisateur. Ce helper existe uniquement
 * pour que les seeders puissent repartir des donnees fictives de facon
 * plausible sur l'annee, sans introduire une seconde source de verite.
 */
class CalendrierScolaire
{
    /** Calendrier togolais : rentree en septembre. Cle = rang de la periode. */
    private const TRIMESTRES = [
        1 => ['09-15', '12-15'],
        2 => ['01-05', '03-25'],
        3 => ['04-05', '06-30'],
    ];

    private const SEMESTRES = [
        1 => ['09-15', '01-20'],
        2 => ['01-25', '06-30'],
    ];

    /**
     * Annee civile de la rentree : "2025-2026" => 2025.
     */
    public static function anneeDebut(AnneeScolaire $annee): int
    {
        return (int) explode('-', $annee->annee)[0];
    }

    /**
     * Bornes de la periode de rang $rang (1-indexe).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function fenetre(AnneeScolaire $annee, int $rang, int $nombrePeriodes = 3): array
    {
        $grille = $nombrePeriodes === 2 ? self::SEMESTRES : self::TRIMESTRES;
        [$debut, $fin] = $grille[$rang] ?? reset($grille);

        $anneeDebut = self::anneeDebut($annee);

        return [
            self::jour($debut, $anneeDebut),
            self::jour($fin, $anneeDebut),
        ];
    }

    /**
     * Date aleatoire dans une fenetre, ramenee sur un jour ouvre et jamais
     * dans le futur (une absence datee de l'an prochain n'a aucun sens a
     * l'ecran).
     */
    public static function jourAleatoire(Carbon $debut, Carbon $fin): Carbon
    {
        $plafond = $fin->isFuture() ? Carbon::today() : $fin;

        if ($plafond->lessThan($debut)) {
            $plafond = $debut->copy();
        }

        $date = $debut->copy()->addDays(rand(0, $debut->diffInDays($plafond)));

        return self::jourOuvre($date);
    }

    /**
     * Ramene un samedi / dimanche sur le vendredi precedent.
     */
    public static function jourOuvre(Carbon $date): Carbon
    {
        return match ($date->dayOfWeekIso) {
            6 => $date->copy()->subDay(),
            7 => $date->copy()->subDays(2),
            default => $date->copy(),
        };
    }

    /**
     * Un "MM-JJ" anterieur a aout appartient a l'annee civile suivante :
     * le 2e trimestre de "2025-2026" commence en janvier 2026.
     */
    private static function jour(string $mmjj, int $anneeDebut): Carbon
    {
        $mois = (int) substr($mmjj, 0, 2);
        $annee = $mois >= 8 ? $anneeDebut : $anneeDebut + 1;

        return Carbon::createFromFormat('Y-m-d', "{$annee}-{$mmjj}")->startOfDay();
    }
}
