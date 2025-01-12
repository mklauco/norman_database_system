<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
            'passive'
        ];
        foreach($roleNames as $role){
            $roles[$role] = Role::firstOrCreate(['name' => $role]);
        }

        $users = \App\Models\User::whereIn('email', ['martin.klauco@stuba.sk', 'lubos.cirka@stuba.sk'])->get();
        foreach ($users as $user){
            foreach($roleNames as $role){
                $user->assignRole($roles[$role]);
            }
        }

        $users = \App\Models\User::whereNotIn('email', ['martin.klauco@stuba.sk', 'lubos.cirka@stuba.sk'])->get();
        foreach ($users as $user){
            $user->assignRole($roles['user']);
        }
    }
}