<?php

declare(strict_types=1);

namespace Database\Seeders\Hazards;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Additive setup of the Hazards module. Touches only:
 * - roles: creates the role 'hazards' (guard web) if it does not exist;
 * - database_entities: inserts the row code='hazards' (private, shown on the dashboard) if it
 *   does not exist.
 * Existing rows are never updated and no other row is touched. Safe to run repeatedly.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class="Database\Seeders\Hazards\HazardsSetupSeeder" --force
 *
 * Undo: php artisan db:seed --class="Database\Seeders\Hazards\HazardsSetupRestoreSeeder" --force
 */
class HazardsSetupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $role = Role::firstOrCreate(['name' => 'hazards', 'guard_name' => 'web']);
            $this->command->info($role->wasRecentlyCreated ? "Role 'hazards' created." : "Role 'hazards' already exists.");

            if (DB::table('database_entities')->where('code', 'hazards')->exists()) {
                $this->command->info("database_entities row 'hazards' already exists, left as it is.");

                return;
            }

            $now = now();
            DB::table('database_entities')->insert([
                'name' => 'Hazards and Properties',
                'description' => 'Hazard and property data derived from Janus and PIKME',
                'image_path' => 'fas fa-triangle-exclamation',
                'code' => 'hazards',
                'dashboard_route_name' => 'hazardshome.index',
                'last_update' => null,
                'number_of_records' => 0,
                'parent_id' => null,
                'show_in_dashboard' => true,
                'has_templates' => false,
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->command->info("database_entities row 'hazards' inserted.");
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('Hazards setup seeder completed.');
    }
}
