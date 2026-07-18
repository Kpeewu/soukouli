<?php

namespace Database\Seeders\Support;

/**
 * Profil deterministe d'un eleve, derive de son id.
 *
 * Partage par tous les seeders pour que les notes, l'assiduite et les
 * resultats d'examen d'un meme eleve racontent la meme histoire : un eleve
 * en difficulte a de mauvaises notes, plus d'absences et echoue au BEPC.
 * Sans ce partage, la demo se contredit des qu'on croise deux ecrans.
 */
class ProfilEleve
{
    /**
     * Facteur de performance stable entre deux executions, de 0.4 (grande
     * difficulte) a 1.5 (excellent).
     *
     * Repartition : 5% en grande difficulte, 15% en difficulte, 40% moyens,
     * 30% bons, 10% excellents.
     */
    public static function facteur(int $eleveId): float
    {
        $normalized = (crc32((string) $eleveId) % 1000) / 1000;

        if ($normalized < 0.05) {
            return 0.4 + ($normalized / 0.05) * 0.2;
        }

        if ($normalized < 0.20) {
            return 0.6 + (($normalized - 0.05) / 0.15) * 0.2;
        }

        if ($normalized < 0.60) {
            return 0.8 + (($normalized - 0.20) / 0.40) * 0.2;
        }

        if ($normalized < 0.90) {
            return 1.0 + (($normalized - 0.60) / 0.30) * 0.3;
        }

        return 1.3 + (($normalized - 0.90) / 0.10) * 0.2;
    }

    /**
     * Position stable de 0 a 99, pour repartir les eleves en cohortes.
     *
     * Le sel ("paiement", "examen", "assiduite") evite que les memes eleves
     * se retrouvent systematiquement dans la meme cohorte partout : sans lui,
     * tous les mauvais payeurs seraient aussi les plus absents.
     */
    public static function cohorte(int $eleveId, string $sel): int
    {
        return crc32($sel . ':' . $eleveId) % 100;
    }

    /**
     * Tirage suivant une loi normale (Box-Muller).
     */
    public static function gauss(float $moyenne, float $ecartType): float
    {
        $u1 = max(mt_rand() / mt_getrandmax(), 0.0001);
        $u2 = mt_rand() / mt_getrandmax();

        $z = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);

        return $moyenne + $z * $ecartType;
    }
}
