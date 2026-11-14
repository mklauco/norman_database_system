<?php

declare(strict_types=1);

namespace Database\Seeders\Hazards;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Reverts HazardsSetupSeeder. Deletes exactly the role 'hazards' (with its user assignments)
 * and the database_entities row code='hazards'. Nothing else is touched, and no hazards data
 * table is emptied (that is HazardsDataRestoreSeeder). Runs in one transaction.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class="Database\Seeders\Hazards\HazardsSetupRestoreSeeder" --force
 */
class HazardsSetupRestoreSeeder extends Seeder
{
    public function run(): void
    {
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        DB::transaction(function (): void {
            $roles = Role::query()->where('name', 'hazards')->where('guard_name', 'web')->delete();
            $entities = DB::table('database_entities')->where('code', 'hazards')->delete();

            $this->command->info("Deleted {$roles} role(s) 'hazards' and {$entities} database_entities row(s) 'hazards'.");
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('Hazards setup restore seeder completed.');
    }
}
