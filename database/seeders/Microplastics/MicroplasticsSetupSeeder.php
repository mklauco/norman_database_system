<?php

declare(strict_types=1);

namespace Database\Seeders\Microplastics;

use App\Models\DatabaseEntity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Telescope;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Additive setup of the private Microplastics module. Touches only:
 * - roles: creates the role 'microplastics' (guard web) if it does not exist;
 * - database_entities: inserts the row code='microplastics' (private, shown on the dashboard)
 *   if it does not exist.
 * Existing rows are never updated or deleted. Safe to run repeatedly.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class="Database\Seeders\Microplastics\MicroplasticsSetupSeeder" --force
 *
 * Undo: php artisan db:seed --class="Database\Seeders\Microplastics\MicroplasticsSetupRestoreSeeder" --force
 */
class MicroplasticsSetupSeeder extends Seeder
{
    public function run(): void
    {
        Telescope::stopRecording();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'microplastics', 'guard_name' => 'web']);
        $this->command->info($role->wasRecentlyCreated ? "Role 'microplastics' created." : "Role 'microplastics' already exists.");

        DB::transaction(function (): void {
            if (DatabaseEntity::query()->where('code', 'microplastics')->exists()) {
                $this->command->info("database_entities row 'microplastics' already exists, left as it is.");

                return;
            }

            DatabaseEntity::query()->create([
                'name' => 'Microplastics',
                'description' => 'A database of microplastics in environmental matrices',
                'image_path' => 'fas fa-bottle-water',
                'code' => 'microplastics',
                'dashboard_route_name' => 'microplastics.home.index',
                'last_update' => null,
                'number_of_records' => 0,
                'parent_id' => null,
                'show_in_dashboard' => true,
                'has_templates' => true,
                'is_public' => false,
            ]);
            $this->command->info("database_entities row 'microplastics' inserted.");
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('Microplastics setup seeder completed.');
    }
}

// php artisan db:seed --class="Database\Seeders\Microplastics\MicroplasticsSetupSeeder" --force
