<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {

        DB::table('roles')->delete();
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('model_has_permissions')->delete();
        DB::table('permissions')->delete();

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create all roles:
        $roleNames = [
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
        foreach ($roleNames as $role) {
            $roles[$role] = Role::firstOrCreate(['name' => $role]);
        }

        // Dedicated permissions (checked via Spatie's `can`/`permission:` gate rather than
        // a role-name string comparison). super_admin already gets every ability implicitly
        // via the Gate::before hook in AppServiceProvider, but the permission row still needs
        // to exist so it can be granted to other roles/users, and so `@can`/`permission:`
        // checks resolve cleanly instead of throwing/failing for everyone else.
        $permissionNames = [
            // Triggers the empodat-suspect:* materialized-view refresh commands from the
            // EMPODAT Suspect Command Center (app/Livewire/EmpodatSuspect/CommandCenter.php).
            'empodat-suspect.refresh',
        ];
        foreach ($permissionNames as $permission) {
            $permissions[$permission] = Permission::firstOrCreate(['name' => $permission]);
        }
        $roles['super_admin']->givePermissionTo(array_values($permissions));

        $users = \App\Models\User::whereIn('email', ['martin@klauco.com', 'lubos.cirka@stuba.sk'])->get();
        foreach ($users as $user) {
            foreach ($roleNames as $role) {
                $user->assignRole($roles[$role]);
            }
        }

        $users = \App\Models\User::whereNotIn('email', ['martin@klauco.com', 'lubos.cirka@stuba.sk'])->get();
        foreach ($users as $user) {
            $user->assignRole($roles['user']);
        }
    }
}

// php artisan db:seed --class=RolesAndPermissionsSeeder
