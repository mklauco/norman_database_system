<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Models\DatabaseEntity;
use App\Models\User;
use Database\Seeders\Hazards\HazardsSetupRestoreSeeder;
use Database\Seeders\Hazards\HazardsSetupSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class HazardsSetupSeedersTest extends HazardsTestCase
{
    private function otherState(): array
    {
        $role = Role::firstOrCreate(['name' => 'literature', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        DatabaseEntity::factory()->private()->create(['code' => 'other_module']);

        return [
            'roles' => json_encode(DB::table('roles')->where('name', '!=', 'hazards')->orderBy('id')->get()->all()),
            'assignments' => json_encode(DB::table('model_has_roles')->orderBy('role_id')->orderBy('model_id')->get()->all()),
            'entities' => json_encode(DB::table('database_entities')->where('code', '!=', 'hazards')->orderBy('id')->get()->all()),
        ];
    }

    private function currentOtherState(): array
    {
        return [
            'roles' => json_encode(DB::table('roles')->where('name', '!=', 'hazards')->orderBy('id')->get()->all()),
            'assignments' => json_encode(DB::table('model_has_roles')->orderBy('role_id')->orderBy('model_id')->get()->all()),
            'entities' => json_encode(DB::table('database_entities')->where('code', '!=', 'hazards')->orderBy('id')->get()->all()),
        ];
    }

    public function test_setup_creates_hazards_role_and_private_entity_row(): void
    {
        $this->seed(HazardsSetupSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'hazards', 'guard_name' => 'web']);
        $entity = DatabaseEntity::where('code', 'hazards')->first();
        $this->assertNotNull($entity);
        $this->assertFalse($entity->is_public);
    }

    public function test_setup_twice_changes_nothing(): void
    {
        $this->seed(HazardsSetupSeeder::class);
        $first = [
            json_encode(DB::table('roles')->orderBy('id')->get()->all()),
            json_encode(DB::table('database_entities')->orderBy('id')->get()->all()),
        ];

        $this->seed(HazardsSetupSeeder::class);

        $this->assertSame($first, [
            json_encode(DB::table('roles')->orderBy('id')->get()->all()),
            json_encode(DB::table('database_entities')->orderBy('id')->get()->all()),
        ]);
        $this->assertSame(1, Role::where('name', 'hazards')->count());
        $this->assertSame(1, DatabaseEntity::where('code', 'hazards')->count());
    }

    public function test_setup_does_not_update_an_existing_entity_row(): void
    {
        DatabaseEntity::factory()->public()->create(['code' => 'hazards', 'name' => 'Custom name']);

        $this->seed(HazardsSetupSeeder::class);

        $entity = DatabaseEntity::where('code', 'hazards')->firstOrFail();
        $this->assertSame('Custom name', $entity->name);
        $this->assertTrue($entity->is_public);
    }

    public function test_setup_leaves_other_roles_assignments_and_entities_untouched(): void
    {
        $before = $this->otherState();

        $this->seed(HazardsSetupSeeder::class);

        $this->assertSame($before, $this->currentOtherState());
    }

    public function test_setup_restore_removes_exactly_the_role_and_the_entity_row(): void
    {
        $before = $this->otherState();
        $this->seed(HazardsSetupSeeder::class);
        User::factory()->create()->assignRole('hazards');

        $this->seed(HazardsSetupRestoreSeeder::class);

        $this->assertDatabaseMissing('roles', ['name' => 'hazards']);
        $this->assertDatabaseMissing('database_entities', ['code' => 'hazards']);
        $assignments = json_decode($before['assignments'], true);
        $this->assertSame(count($assignments), DB::table('model_has_roles')->count());
        $before['assignments'] = $this->currentOtherState()['assignments'];
        $this->assertSame($before, $this->currentOtherState());
    }

    public function test_setup_restore_without_prior_setup_is_harmless(): void
    {
        $before = $this->otherState();

        $this->seed(HazardsSetupRestoreSeeder::class);

        $this->assertSame($before, $this->currentOtherState());
    }
}
