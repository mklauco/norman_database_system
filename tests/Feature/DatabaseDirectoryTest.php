<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DatabaseEntity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression safety net for the public Database Modules landing page
 * (GET / → DatabaseDirectoryController@index → landing.index view).
 *
 * Purpose: every time a new module is merged (adding a row to the
 * database_entities table, optionally with its own role), the visibility
 * rules for ALL OTHER modules must remain unchanged. This test pins those
 * rules down independently of any specific module. It builds its own
 * fixtures via factories, so it is insulated from the production-seeded
 * database_entities.csv.
 *
 * Access rules under test (see DatabaseDirectoryController::canUserAccessModule):
 *   1. show_in_dashboard = false      → invisible to everyone, including super_admin
 *   2. is_public = true               → visible to everyone (guest + any authed user)
 *   3. is_public = false              → visible to:
 *        - admin and super_admin (always)
 *        - users whose role name matches database_entity.code
 *        - nobody else (guest cannot see, plain authed user cannot see)
 */
class DatabaseDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseEntity $publicVisible;

    private DatabaseEntity $privateVisible;

    private DatabaseEntity $hidden;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie/Permission caches role lookups inside the same process.
        // Forget the cache so freshly-inserted roles are resolvable.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Roles the controller checks against. Module-specific role uses the
        // code of $this->privateVisible so the "user with module role" case is exercisable.
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'sample_private_module', 'guard_name' => 'web']);

        // dashboard_route_name must be a real named route; landing.index does
        // route($d->dashboard_route_name) with no null guard. 'home' is the
        // route this test itself hits, so it's guaranteed to exist.
        $this->publicVisible = DatabaseEntity::factory()->create([
            'code' => 'sample_public_module',
            'is_public' => true,
            'show_in_dashboard' => true,
            'dashboard_route_name' => 'home',
        ]);

        $this->privateVisible = DatabaseEntity::factory()->create([
            'code' => 'sample_private_module',
            'is_public' => false,
            'show_in_dashboard' => true,
            'dashboard_route_name' => 'home',
        ]);

        $this->hidden = DatabaseEntity::factory()->create([
            'code' => 'sample_hidden_module',
            'is_public' => true,
            'show_in_dashboard' => false,
            'dashboard_route_name' => 'home',
        ]);
    }

    public function test_page_renders_for_guest(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewIs('landing.index');
        $response->assertViewHas('databases');
    }

    public function test_guest_sees_only_public_visible_modules(): void
    {
        $response = $this->get(route('home'));

        $codes = $this->visibleCodes($response);

        $this->assertContains($this->publicVisible->code, $codes);
        $this->assertNotContains($this->privateVisible->code, $codes);
        $this->assertNotContains($this->hidden->code, $codes);
    }

    public function test_authed_user_without_module_role_sees_only_public_visible_modules(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $codes = $this->visibleCodes($response);

        $this->assertContains($this->publicVisible->code, $codes);
        $this->assertNotContains($this->privateVisible->code, $codes);
        $this->assertNotContains($this->hidden->code, $codes);
    }

    public function test_user_with_matching_module_role_sees_public_plus_that_private_module(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sample_private_module');

        $response = $this->actingAs($user)->get(route('home'));

        $codes = $this->visibleCodes($response);

        $this->assertContains($this->publicVisible->code, $codes);
        $this->assertContains($this->privateVisible->code, $codes);
        $this->assertNotContains($this->hidden->code, $codes);
    }

    public function test_admin_sees_all_visible_modules_regardless_of_public_flag(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('home'));

        $codes = $this->visibleCodes($response);

        $this->assertContains($this->publicVisible->code, $codes);
        $this->assertContains($this->privateVisible->code, $codes);
        $this->assertNotContains($this->hidden->code, $codes);
    }

    public function test_super_admin_sees_all_visible_modules_regardless_of_public_flag(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('home'));

        $codes = $this->visibleCodes($response);

        $this->assertContains($this->publicVisible->code, $codes);
        $this->assertContains($this->privateVisible->code, $codes);
        $this->assertNotContains($this->hidden->code, $codes);
    }

    public function test_show_in_dashboard_false_is_invisible_to_every_persona(): void
    {
        // Personas: guest, plain authed, module-role holder, admin, super_admin
        $guestResponse = $this->get(route('home'));
        $this->assertNotContains($this->hidden->code, $this->visibleCodes($guestResponse));

        $plain = User::factory()->create();
        $this->assertNotContains(
            $this->hidden->code,
            $this->visibleCodes($this->actingAs($plain)->get(route('home')))
        );

        $moduleRole = User::factory()->create();
        $moduleRole->assignRole('sample_private_module');
        $this->assertNotContains(
            $this->hidden->code,
            $this->visibleCodes($this->actingAs($moduleRole)->get(route('home')))
        );

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertNotContains(
            $this->hidden->code,
            $this->visibleCodes($this->actingAs($admin)->get(route('home')))
        );

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->assertNotContains(
            $this->hidden->code,
            $this->visibleCodes($this->actingAs($superAdmin)->get(route('home')))
        );
    }

    /**
     * Helper: extract the `code` column from the controller's filtered $databases collection.
     *
     * @return array<int, string>
     */
    private function visibleCodes($response): array
    {
        return $response->viewData('databases')->pluck('code')->all();
    }
}
