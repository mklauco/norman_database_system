<?php

namespace Database\Seeders;

use App\Models\DatabaseEntity;
use Illuminate\Database\Seeder;

class DatabaseEntityEmpodatSuspectModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DatabaseEntity::create([
            'name' => 'Empodat Suspect',
            'description' => 'Empodat Suspect screening results module',
            'image_path' => 'fas fa-vial',
            'code' => 'empodat_suspect',
            'dashboard_route_name' => 'empodat_suspect.home.index',
            'last_update' => null,
            'number_of_records' => 0,
            'has_templates' => false,
            'parent_id' => null,
            'is_public' => false,
        ]);
    }
}
// php artisan db:seed --class=DatabaseEntityEmpodatSuspectModuleSeeder
