<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Http\Middleware\CheckModuleAccess;
use App\Models\DatabaseEntity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Locks down the access matrix for app/Http/Middleware/CheckModuleAccess.php.
 *
 * The middleware controls whether a request reaches a module based on:
 *   1. Module's database_entities.is_public flag
 *   2. User's authentication state
 *   3. User's roles (super_admin, admin, or the per-module role)
 *
 * A regression here means either data leakage (private module exposed) or
 * legitimate users locked out. These 7 tests cover the full matrix.
 */
class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    private const MODULE_CODE = 'test_module';

    private const MODULE_ROLE = 'test_module_role';

    private const PROBE_PATH = '/__probe/module-access';

    protected function setUp(): void
    {
        parent::setUp();

        // Register a synthetic route guarded by the middleware so we can
        // exercise it without depending on which real routes happen to use it.
        Route::middleware(['web', CheckModuleAccess::class.':'.self::MODULE_CODE.','.self::MODULE_ROLE])
            ->get(self::PROBE_PATH, fn () => response('ok', 200));

        // Seed the roles the middleware checks against.
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => self::MODULE_ROLE, 'guard_name' => 'web']);
    }

    public function test_public_module_accessible_by_guest(): void
    {
        DatabaseEntity::factory()->public()->create(['code' => self::MODULE_CODE]);

        $this->get(self::PROBE_PATH)->assertStatus(200);
    }

    public function test_public_module_accessible_by_authenticated_user_without_role(): void
    {
        DatabaseEntity::factory()->public()->create(['code' => self::MODULE_CODE]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(self::PROBE_PATH)->assertStatus(200);
    }

    public function test_private_module_blocked_for_guest(): void
    {
        DatabaseEntity::factory()->private()->create(['code' => self::MODULE_CODE]);

        $this->get(self::PROBE_PATH)->assertStatus(403);
    }

    public function test_private_module_blocked_for_user_without_role(): void
    {
        DatabaseEntity::factory()->private()->create(['code' => self::MODULE_CODE]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(self::PROBE_PATH)->assertStatus(403);
    }

    public function test_private_module_accessible_by_user_with_role(): void
    {
        DatabaseEntity::factory()->private()->create(['code' => self::MODULE_CODE]);
        $user = User::factory()->create();
        $user->assignRole(self::MODULE_ROLE);

        $this->actingAs($user)->get(self::PROBE_PATH)->assertStatus(200);
    }

    public function test_admin_can_access_any_private_module(): void
    {
        DatabaseEntity::factory()->private()->create(['code' => self::MODULE_CODE]);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)->get(self::PROBE_PATH)->assertStatus(200);
    }

    public function test_super_admin_can_access_any_private_module(): void
    {
        DatabaseEntity::factory()->private()->create(['code' => self::MODULE_CODE]);
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get(self::PROBE_PATH)->assertStatus(200);
    }

    public function test_module_access_returns_403_for_unknown_module(): void
    {
        // No DatabaseEntity row created for self::MODULE_CODE.
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get(self::PROBE_PATH)->assertStatus(403);
    }
}
