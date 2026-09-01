<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Ensure the application's roles and permissions exist.
 *
 * Purely additive and safe to run on any environment, including production,
 * any number of times. It creates missing roles and permissions and grants
 * permissions to roles. It never deletes a role, a permission, or anyone's
 * role assignment, and it never assigns a role to a user.
 *
 * Deciding WHO holds which role is a separate concern, deliberately not done
 * here — see ResetRolesAndPermissionsSeeder, which is destructive and refuses
 * to run in production.
 *
 * Until 2026-08-28 this class began by deleting every row from `roles`,
 * `permissions`, `role_has_permissions`, `model_has_roles` and
 * `model_has_permissions` before rebuilding a fixed set. Run against
 * production, that destroyed every role assigned through the admin UI
 * (UserController::syncRoles). The name promised "ensure these exist" while
 * the body performed a reset; the two behaviours are now separate classes.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Every role the application recognises.
     *
     * @var list<string>
     */
    public const ROLE_NAMES = [
        'super_admin',
        'admin',
        'user',
        'user_manager',
        'project_manager',
        'susdat',
        'empodat',
        'ecotox',
        'sle',
        'arbg',
        'indoor',
        'passive',
    ];

    /**
     * Permissions checked through Spatie's `can` / `permission:` gate rather
     * than by comparing role names. `super_admin` already receives every
     * ability implicitly via the Gate::before hook in AppServiceProvider, but
     * the permission row still has to exist so it can be granted to other
     * roles and users, and so `@can` / `permission:` checks resolve cleanly
     * instead of failing for everyone else.
     *
     * `empodat-suspect.refresh` triggers the empodat-suspect:* refresh
     * commands from app/Livewire/EmpodatSuspect/CommandCenter.php.
     *
     * @var array<string, list<string>> permission name => roles that get it
     */
    public const PERMISSIONS = [
        'empodat-suspect.refresh' => ['super_admin'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [];

        foreach (self::ROLE_NAMES as $roleName) {
            $roles[$roleName] = Role::firstOrCreate(['name' => $roleName]);
        }

        foreach (self::PERMISSIONS as $permissionName => $grantedTo) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);

            foreach ($grantedTo as $roleName) {
                // givePermissionTo is idempotent — granting an already-granted
                // permission is a no-op rather than a duplicate row.
                $roles[$roleName]->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(
            'Ensured '.count(self::ROLE_NAMES).' roles and '.count(self::PERMISSIONS).
            ' permissions. No assignments were changed.'
        );
    }
}

// php artisan db:seed --class=RolesAndPermissionsSeeder
