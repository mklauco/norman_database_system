<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create all roles:
        $roleNames = ['super-admin', 'user'];
        foreach($roleNames as $role){
            $roles[$role] = Role::firstOrCreate(['name' => $role]);
        }

        $users = \App\Models\User::whereIn('email', ['martin.klauco@stuba.sk', 'lubos.cirka@stuba.sk'])->get();
        foreach ($users as $user){
            $user->assignRole($roles['super-admin']);   
        }
    }
}