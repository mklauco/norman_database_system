<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Susdat\Substance;
use App\Models\User;
use Database\Factories\Susdat\SubstanceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SusdatMissingNamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_missing_names_page(): void
    {
        $response = $this->get(route('substances.missing-names'));

        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_users_cannot_access_missing_names_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('substances.missing-names'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_missing_names_page(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('substances.missing-names'));

        $response->assertStatus(200);
        $response->assertSee('Substances Missing Names');
    }

    public function test_missing_names_page_shows_substances_without_names(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Substance with name - should NOT appear
        SubstanceFactory::new()->create(['code' => '00000001', 'name' => 'Test Substance', 'added_by' => $user->id]);

        // Substance without name - should appear
        SubstanceFactory::new()->create(['code' => '00000002', 'name' => null, 'added_by' => $user->id]);
        SubstanceFactory::new()->create(['code' => '00000003', 'name' => '', 'added_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('substances.missing-names'));

        $response->assertStatus(200);
        $response->assertSee('2 substances have a code but no name');
        $response->assertSee('NS00000002');
        $response->assertSee('NS00000003');
        $response->assertDontSee('NS00000001');
    }
}
