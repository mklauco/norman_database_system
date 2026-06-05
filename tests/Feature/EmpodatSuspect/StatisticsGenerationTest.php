<?php

declare(strict_types=1);

namespace Tests\Feature\EmpodatSuspect;

use App\Jobs\EmpodatSuspect\GenerateEmpodatSuspectStatisticsJob;
use App\Models\DatabaseEntity;
use App\Models\Statistic;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StatisticsGenerationTest extends TestCase
{
    public function test_total_substances_card_shows_non_overlapping_partition(): void
    {
        $entity = DatabaseEntity::firstOrCreate(
            ['code' => 'empodat_suspect'],
            ['name' => 'Empodat Suspect (test)', 'is_public' => false, 'show_in_dashboard' => false]
        );

        // total = 93016 unique substances; 92994 have >=1 numeric record.
        // The card must derive N/A-only = 93016 - 92994 = 22, NOT the old
        // overlapping per-partition count.
        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'empodat_suspect.total_substances',
            'meta_data' => [
                'count' => 93016,
                'numeric_count' => 92994,
                'non_numeric_count' => 22,
                'generated_at' => now()->toISOString(),
            ],
        ]);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('empodat_suspect.statistics.index'))
            ->assertOk()
            ->assertSee('With a numeric concentration:')
            ->assertSee('N/A only (never numeric):')
            ->assertSee('92 994')   // numeric
            ->assertSee('22')       // N/A only = total - numeric
            ->assertDontSee('In records with concentration:');
    }

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
