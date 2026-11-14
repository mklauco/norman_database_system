<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Jobs\Hazards\FillHazardsComptoxSubstanceDataJob;
use App\Models\User;
use Database\Seeders\Hazards\HazardsSetupSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

class HazardsAccessTest extends HazardsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(HazardsSetupSeeder::class);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    private function userWith(?string $role): User
    {
        $user = User::factory()->create();
        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * @return list<string>
     */
    private function viewRoutes(): array
    {
        return ['hazardshome.index', 'hazards.data.search.filter', 'hazards.derivation.search.filter', 'hazards.classification.search.filter'];
    }

    public function test_guest_cannot_open_hazards_pages(): void
    {
        foreach ($this->viewRoutes() as $name) {
            $status = $this->get(route($name))->getStatusCode();
            $this->assertContains($status, [302, 403], "{$name} returned {$status} for a guest");
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('hazardshome.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_role_gets_403(): void
    {
        $user = $this->userWith(null);

        foreach ($this->viewRoutes() as $name) {
            $this->actingAs($user)->get(route($name))->assertForbidden();
        }
    }

    public function test_hazards_admin_and_super_admin_can_open_hazards_pages(): void
    {
        foreach (['hazards', 'admin', 'super_admin'] as $role) {
            $user = $this->userWith($role);
            foreach ($this->viewRoutes() as $name) {
                $this->actingAs($user)->get(route($name))->assertOk();
            }
        }
    }

    public function test_hazards_and_admin_cannot_fill_or_count(): void
    {
        Queue::fake();

        foreach (['hazards', 'admin'] as $role) {
            $user = $this->userWith($role);
            $this->actingAs($user)->post(route('hazards.substance_data.fill'))->assertForbidden();
            $this->actingAs($user)->get(route('hazards.countAll'))->assertForbidden();
        }

        Queue::assertNothingPushed();
    }

    public function test_user_without_role_cannot_fill_or_count(): void
    {
        $user = $this->userWith(null);

        $this->actingAs($user)->post(route('hazards.substance_data.fill'))->assertForbidden();
        $this->actingAs($user)->get(route('hazards.countAll'))->assertForbidden();
    }

    public function test_super_admin_can_fill_and_the_job_is_dispatched(): void
    {
        Bus::fake();
        Cache::forget(FillHazardsComptoxSubstanceDataJob::REQUEST_TOKEN_CACHE_KEY);

        $this->actingAs($this->userWith('super_admin'))
            ->post(route('hazards.substance_data.fill'))
            ->assertRedirect();

        Bus::assertDispatched(FillHazardsComptoxSubstanceDataJob::class);
    }

    public function test_super_admin_can_open_count_all(): void
    {
        $status = $this->actingAs($this->userWith('super_admin'))->get(route('hazards.countAll'))->getStatusCode();

        $this->assertNotSame(403, $status);
        $this->assertLessThan(400, $status);
    }

    public function test_module_without_entity_row_is_denied_even_for_hazards_role(): void
    {
        \DB::table('database_entities')->where('code', 'hazards')->delete();

        $this->actingAs($this->userWith('hazards'))->get(route('hazardshome.index'))->assertForbidden();
    }
}
