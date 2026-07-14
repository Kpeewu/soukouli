<?php

namespace App\Support;

class RolePermissions
{
    public static function directeur(): array
    {
        return [
            'eleves.view', 'eleves.create', 'eleves.edit', 'eleves.delete',
            'professeurs.view', 'professeurs.create', 'professeurs.edit', 'professeurs.delete',
            'classes.view', 'classes.create', 'classes.edit', 'classes.delete',
            'notes.view', 'notes.create', 'notes.edit', 'notes.delete',
            'evaluations.view', 'evaluations.create', 'evaluations.edit', 'evaluations.delete',
            'paiements.view', 'paiements.create', 'paiements.edit', 'paiements.delete',
            'matieres.view', 'matieres.create', 'matieres.edit', 'matieres.delete',
            'examens.view',
            'inscriptions_examen.view', 'inscriptions_examen.create', 'inscriptions_examen.edit',
        ];
    }

    public static function professeur(): array
    {
        return [
            'notes.view',
            'notes.create',
            'notes.edit',
            'evaluations.view',
            'evaluations.create',
            'evaluations.edit',
            'classes.view',
            'eleves.view',
        ];
    }

    public static function comptable(): array
    {
        return [
            'comptabilite.dashboard',
            'comptabilite.eleves',
            'comptabilite.rapports',
            'paiements.view',
            'paiements.create',
            'paiements.edit',
            'recus.view',
            'recus.create',
            'recus.annuler',
            'eleves.view',
        ];
    }

    public static function secretaire(): array
    {
        return [
            'eleves.view',
            'eleves.create',
            'eleves.edit',
            'classes.view',
            'inscriptions_examen.view',
            'inscriptions_examen.create',
            'inscriptions_examen.edit',
            // Comptabilite : consultation + encaissement (generation de recus), pas d'annulation
            'comptabilite.dashboard',
            'comptabilite.eleves',
            'comptabilite.rapports',
            'paiements.view',
            'paiements.create',
            'recus.view',
            'recus.create',
        ];
    }

    /**
     * Surveillants : uniquement l'assiduite des eleves (absences, retards,
     * comportement) - aucun autre acces.
     */
    public static function surveillant(): array
    {
        return [
            'eleves.view',
            'classes.view',
            'assiduite.view',
            'assiduite.create',
            'assiduite.edit',
            'assiduite.delete',
        ];
    }
}
