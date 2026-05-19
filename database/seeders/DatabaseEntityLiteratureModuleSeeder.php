<?php

namespace Database\Seeders;

use App\Models\DatabaseEntity;
use Illuminate\Database\Seeder;

class DatabaseEntityLiteratureModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DatabaseEntity::updateOrCreate(
            ['code' => 'literature'], // Search by code
            [
                'name' => 'Literature',
                'description' => 'Literature module',
                'image_path' => 'fas fa-book',
                'dashboard_route_name' => 'literature.home.index',
                'last_update' => null,
                'number_of_records' => 0,
                'has_templates' => false,
                'parent_id' => null,
                'is_public' => false,
            ]
        );

        $this->command->info('✓ Literature database entity created/updated successfully');
    }
}
// php artisan db:seed --class=DatabaseEntityLiteratureModuleSeeder
