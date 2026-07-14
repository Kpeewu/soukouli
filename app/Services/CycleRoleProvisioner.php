<?php

namespace App\Services;

use App\Models\Cycle;
use App\Support\RolePermissions;
use Spatie\Permission\Models\Role;

class CycleRoleProvisioner
{
    /**
     * Cree les roles directeur/comptable dedies a ce cycle.
     */
    public static function provision(Cycle $cycle): void
    {
        $suffix = strtolower($cycle->code);

        foreach ([
            'directeur' => RolePermissions::directeur(),
            'comptable' => RolePermissions::comptable(),
            'secretaire' => RolePermissions::secretaire(),
        ] as $type => $permissions) {
            Role::firstOrCreate(['name' => "{$type}_{$suffix}"])->syncPermissions($permissions);
        }
    }
}
