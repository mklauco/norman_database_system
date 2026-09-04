<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Guards the one property `RolesAndPermissionsSeeder` exists to have: it adds
 * what is missing and takes nothing away.
 *
 * Until 2026-08-28 the class began by deleting every row from `roles`,
 * `permissions`, `role_has_permissions`, `model_has_roles` and
 * `model_has_permissions`. Its name and its `firstOrCreate` calls both read
 * as idempotent, so running it on production looked harmless; it destroyed
 * every role granted through the admin UI, recoverable only from a nightly
 * backup.
 *
 * A foreign key cannot catch that, and neither can a smoke test that merely
 * runs the seeder and checks the roles exist — after a wipe-and-rebuild they
 * do exist. The assertions that matter are therefore about what survives a
 * SECOND run: a hand-assigned role, and a role that is not part of the
 * seeder's own vocabulary.
 */
class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_every_role_and_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        foreach (RolesAndPermissionsSeeder::ROLE_NAMES as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }

        foreach (array_keys(RolesAndPermissionsSeeder::PERMISSIONS) as $permissionName) {
            $this->assertDatabaseHas('permissions', ['name' => $permissionName]);
        }

        $this->assertTrue(
            Role::findByName('super_admin')->hasPermissionTo('empodat-suspect.refresh'),
            'super_admin should hold empodat-suspect.refresh.'
        );
    }

    public function test_a_hand_assigned_role_survives_a_second_run(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('empodat');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue(
            $user->fresh()->hasRole('empodat'),
            'Re-running the seeder must not remove a role assigned through the admin UI.'
        );
    }

    public function test_it_does_not_delete_roles_it_does_not_know_about(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Role::create(['name' => 'temporary_external_role', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('temporary_external_role');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'temporary_external_role']);
        $this->assertTrue($user->fresh()->hasRole('temporary_external_role'));
    }

    public function test_it_assigns_no_roles_of_its_own(): void
    {
        $user = User::factory()->create();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertCount(
            0,
            $user->fresh()->roles,
            'Deciding who holds which role belongs to ResetRolesAndPermissionsSeeder, not to this one.'
        );
    }

    public function test_running_it_twice_creates_no_duplicates(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(
            count(RolesAndPermissionsSeeder::ROLE_NAMES),
            Role::whereIn('name', RolesAndPermissionsSeeder::ROLE_NAMES)->count()
        );

        $this->assertSame(
            1,
            Role::findByName('super_admin')->permissions()->where('name', 'empodat-suspect.refresh')->count()
        );
    }
}
