<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Support\RolePermissions;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            // Élèves
            'eleves.view',
            'eleves.create',
            'eleves.edit',
            'eleves.delete',

            // Professeurs
            'professeurs.view',
            'professeurs.create',
            'professeurs.edit',
            'professeurs.delete',

            // Classes
            'classes.view',
            'classes.create',
            'classes.edit',
            'classes.delete',

            // Notes
            'notes.view',
            'notes.create',
            'notes.edit',
            'notes.delete',

            // Évaluations
            'evaluations.view',
            'evaluations.create',
            'evaluations.edit',
            'evaluations.delete',

            // Paiements
            'paiements.view',
            'paiements.create',
            'paiements.edit',
            'paiements.delete',

            // Matières
            'matieres.view',
            'matieres.create',
            'matieres.edit',
            'matieres.delete',

            // Examens officiels
            'examens.view',
            'examens.create',
            'examens.edit',
            'examens.delete',

            // Inscriptions aux examens
            'inscriptions_examen.view',
            'inscriptions_examen.create',
            'inscriptions_examen.edit',
            'inscriptions_examen.delete',

            // Cycles (admin only)
            'cycles.view',
            'cycles.create',
            'cycles.edit',
            'cycles.delete',

            // Années scolaires (admin only)
            'annees.view',
            'annees.create',
            'annees.edit',
            'annees.delete',

            // Utilisateurs (admin only)
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Comptabilité
            'comptabilite.dashboard',
            'comptabilite.eleves',
            'comptabilite.rapports',
            'recus.view',
            'recus.create',
            'recus.annuler',
            'types_frais.view',
            'types_frais.create',
            'types_frais.edit',
            'types_frais.delete',
            'configurations_frais.view',
            'configurations_frais.create',
            'configurations_frais.edit',
            'configurations_frais.delete',

            // Assiduite (absences, retards, comportement)
            'assiduite.view',
            'assiduite.create',
            'assiduite.edit',
            'assiduite.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Créer le rôle Admin - gestion du système uniquement (cycles, années, utilisateurs,
        // examens officiels). Pas d'accès à la gestion scolaire courante ni à la comptabilité.
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions([
            'cycles.view', 'cycles.create', 'cycles.edit', 'cycles.delete',
            'annees.view', 'annees.create', 'annees.edit', 'annees.delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'examens.view', 'examens.create', 'examens.edit', 'examens.delete',
        ]);

        // Permissions pour les directeurs (CRUD sur leur cycle)
        $directeurPermissions = RolePermissions::directeur();

        // Créer le rôle directeur transverse (acces a tous les cycles)
        $directeurGeneralRole = Role::firstOrCreate(['name' => 'directeur_general']);
        $directeurGeneralRole->syncPermissions($directeurPermissions);

        // Créer les rôles directeurs
        $directeurRoles = [
            'directeur_maternelle',
            'directeur_primaire',
            'directeur_college',
            'directeur_lycee',
        ];

        foreach ($directeurRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($directeurPermissions);
        }

        // Permissions pour les professeurs (uniquement saisie des notes de leurs cours)
        $professeurPermissions = RolePermissions::professeur();

        // Créer le rôle professeur
        $professeurRole = Role::firstOrCreate(['name' => 'professeur']);
        $professeurRole->syncPermissions($professeurPermissions);

        // Permissions pour les comptables
        $comptablePermissions = RolePermissions::comptable();

        // Créer le rôle comptable transverse (accès à la comptabilité de tous les cycles)
        $comptableGeneralRole = Role::firstOrCreate(['name' => 'comptable_general']);
        $comptableGeneralRole->syncPermissions($comptablePermissions);

        // Créer les rôles comptables par cycle
        $comptableCycleRoles = [
            'comptable_maternelle',
            'comptable_primaire',
            'comptable_college',
            'comptable_lycee',
        ];

        foreach ($comptableCycleRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($comptablePermissions);
        }

        // Permissions pour les secretaires
        $secretairePermissions = RolePermissions::secretaire();

        // Créer le rôle secretaire transverse (accès a tous les cycles)
        $secretaireGeneralRole = Role::firstOrCreate(['name' => 'secretaire_general']);
        $secretaireGeneralRole->syncPermissions($secretairePermissions);

        // Créer les rôles secretaires par cycle
        $secretaireCycleRoles = [
            'secretaire_maternelle',
            'secretaire_primaire',
            'secretaire_college',
            'secretaire_lycee',
        ];

        foreach ($secretaireCycleRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($secretairePermissions);
        }

        // Permissions pour les surveillants (assiduite uniquement : absences, retards, comportement)
        $surveillantPermissions = RolePermissions::surveillant();

        // Créer le rôle surveillant transverse (accès a tous les cycles)
        $surveillantGeneralRole = Role::firstOrCreate(['name' => 'surveillant_general']);
        $surveillantGeneralRole->syncPermissions($surveillantPermissions);

        // Créer les rôles surveillants par cycle
        $surveillantCycleRoles = [
            'surveillant_maternelle',
            'surveillant_primaire',
            'surveillant_college',
            'surveillant_lycee',
        ];

        foreach ($surveillantCycleRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($surveillantPermissions);
        }

        // Assigner le rôle admin à l'utilisateur existant "monavenir"
        $adminUser = User::where('username', 'monavenir')->first();
        if ($adminUser) {
            $adminUser->assignRole('admin');
        }
    }
}
