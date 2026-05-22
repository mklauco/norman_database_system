<?php

declare(strict_types=1);

namespace Tests\Feature\EmpodatSuspect;

use App\Jobs\EmpodatSuspect\GenerateEmpodatSuspectStatisticsJob;
use App\Models\DatabaseEntity;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StatisticsGenerationTest extends TestCase
{
    public function test_generate_route_dispatches_queued_job_and_returns_quickly(): void
    {
        DatabaseEntity::firstOrCreate(
            ['code' => 'empodat_suspect'],
            [
                'name' => 'Empodat Suspect (test)',
                // Match production: empodat_suspect is private. The super_admin
                // user created below satisfies the controller's module-access check.
                'is_public' => false,
                // Keep the stub off the landing page — it has no dashboard_route_name
                // and would otherwise crash route() in the @php block of landing/index.blade.php.
                'show_in_dashboard' => false,
            ]
        );

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        Bus::fake([GenerateEmpodatSuspectStatisticsJob::class]);

        $response = $this->actingAs($user)
            ->post(route('empodat_suspect.statistics.generate'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Bus::assertDispatched(GenerateEmpodatSuspectStatisticsJob::class, 1);
    }

    public function test_artisan_command_defaults_to_queued_dispatch(): void
    {
        DatabaseEntity::firstOrCreate(
            ['code' => 'empodat_suspect'],
            [
                'name' => 'Empodat Suspect (test)',
                // Match production: empodat_suspect is private. The super_admin
                // user created below satisfies the controller's module-access check.
                'is_public' => false,
                // Keep the stub off the landing page — it has no dashboard_route_name
                // and would otherwise crash route() in the @php block of landing/index.blade.php.
                'show_in_dashboard' => false,
            ]
        );

        Bus::fake([GenerateEmpodatSuspectStatisticsJob::class]);

        $this->artisan('empodat-suspect:generate-statistics')
            ->expectsOutputToContain('queued')
            ->assertSuccessful();

        Bus::assertDispatched(GenerateEmpodatSuspectStatisticsJob::class, 1);
    }
}
