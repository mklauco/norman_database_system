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
 * Reverts MicroplasticsSetupSeeder. Detaches the role 'microplastics' from its users, then
 * deletes exactly that role and the database_entities row code='microplastics'. Nothing else
 * is touched. Runs in one transaction.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class="Database\Seeders\Microplastics\MicroplasticsSetupRestoreSeeder" --force
 */
class MicroplasticsSetupRestoreSeeder extends Seeder
{
    public function run(): void
    {
        Telescope::stopRecording();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function (): void {
            $roles = Role::query()->where('name', 'microplastics')->where('guard_name', 'web')->get();

            foreach ($roles as $role) {
                $role->users()->detach();
                $role->delete();
            }

            $entities = DatabaseEntity::query()->where('code', 'microplastics')->delete();

            $this->command->info("Deleted {$roles->count()} role(s) 'microplastics' and {$entities} database_entities row(s) 'microplastics'.");
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('Microplastics setup restore seeder completed.');
    }
}

// php artisan db:seed --class="Database\Seeders\Microplastics\MicroplasticsSetupRestoreSeeder" --force
