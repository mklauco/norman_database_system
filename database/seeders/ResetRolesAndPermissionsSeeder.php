<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * DESTRUCTIVE. Wipes every role, permission and assignment, then rebuilds a
 * fixed bootstrap state: all roles for the two owner accounts, the `user`
 * role for everyone else.
 *
 * This is the behaviour RolesAndPermissionsSeeder used to have. It is split
 * out under a name that says what it does, because run against production it
 * destroys every role granted through the admin UI
 * (UserController::syncRoles) with no way back except a database restore —
 * which is exactly what happened on 2026-08-24.
 *
 * Use RolesAndPermissionsSeeder to ensure roles and permissions exist. Use
 * this one only to reset a local or test database to a known state.
 *
 * Refuses to run in production, whatever flags are passed. There is no
 * --force-production escape hatch here, unlike
 * app/Console/Commands/TruncateEmpodatSuspectMainAndMetadata.php: that
 * command clears reloadable import data, while the rows this one deletes
 * exist nowhere else and cannot be regenerated from a source file.
 */
class ResetRolesAndPermissionsSeeder extends Seeder
{
    /**
     * Accounts that receive every role. Everyone else receives `user`.
     *
     * @var list<string>
     */
    private const OWNER_EMAILS = [
        'martin@klauco.com',
        'lubos.cirka@stuba.sk',
    ];

    /**
     * Phrase an operator must type to proceed when running interactively.
     */
    private const CONFIRMATION_PHRASE = 'RESET ROLES';

    /**
     * Tables emptied before the rebuild, children before parents so the
     * foreign keys between them are never violated mid-run.
     *
     * @var list<string>
     */
    private const TABLES_TO_CLEAR = [
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'roles',
        'permissions',
    ];

    public function run(): void
    {
        $this->guardAgainstProduction();

        if (! $this->confirmedByOperator()) {
            $this->command?->warn('Aborted. Nothing was changed.');

            return;
        }

        DB::transaction(function (): void {
            foreach (self::TABLES_TO_CLEAR as $table) {
                DB::table($table)->delete();
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Recreate the vocabulary through the additive seeder rather than
            // duplicating its role and permission lists here.
            $this->callSilent(RolesAndPermissionsSeeder::class);

            $roles = Role::whereIn('name', RolesAndPermissionsSeeder::ROLE_NAMES)
                ->get()
                ->keyBy('name');

            User::whereIn('email', self::OWNER_EMAILS)
                ->each(function (User $user) use ($roles): void {
                    $user->syncRoles($roles->values());
                });

            User::whereNotIn('email', self::OWNER_EMAILS)
                ->each(function (User $user) use ($roles): void {
                    $user->syncRoles([$roles['user']]);
                });

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        $this->command?->info('Roles, permissions and all assignments were reset.');
    }

    /**
     * Hard stop in production. Unlike the row-limit guard in
     * app/Services/EmpodatSuspect/SeedRowLimiter.php, which merely disables an
     * optimisation, this one prevents irreversible data loss, so it throws
     * rather than returning quietly — a seeder that printed a warning and
     * carried on would be indistinguishable from success in a CI log.
     */
    private function guardAgainstProduction(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException(
                'ResetRolesAndPermissionsSeeder deletes every role assignment and refuses to run in production. '
                .'To add a role or permission on production, run RolesAndPermissionsSeeder, which is additive.'
            );
        }
    }

    /**
     * Require a typed phrase when a human is driving. A non-interactive run
     * (CI, `--no-interaction`) proceeds without prompting, since production is
     * already excluded above and every other environment is disposable.
     */
    private function confirmedByOperator(): bool
    {
        $command = $this->command;

        if ($command === null || ! $command->input->isInteractive()) {
            return true;
        }

        $command->warn('This deletes EVERY role, permission and role assignment in '.app()->environment().'.');

        return (string) $command->ask('Type '.self::CONFIRMATION_PHRASE.' to confirm') === self::CONFIRMATION_PHRASE;
    }
}

// php artisan db:seed --class=ResetRolesAndPermissionsSeeder
