<?php

declare(strict_types=1);

namespace Tests\Feature\EmpodatSuspect;

use App\Models\DatabaseEntity;
use App\Models\Statistic;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression: after the partitioning refactor the Action emits
 * ['sample_code' => ['total' => int, 'numeric' => int, 'non_numeric' => int]]
 * but the sample_code detail views called array_sum() on it, blowing up in
 * production with "array_sum(): Addition is not supported on type array".
 */
class StatisticsDetailViewsTest extends TestCase
{
    public function test_sample_code_detail_pages_render_with_new_array_shape(): void
    {
        $entity = DatabaseEntity::firstOrCreate(
            ['code' => 'empodat_suspect'],
            [
                'name' => 'Empodat Suspect (test)',
                'is_public' => false,
                'show_in_dashboard' => false,
            ]
        );

        $newShape = [
            'data' => [
                'EE001' => ['total' => 100, 'numeric' => 70, 'non_numeric' => 30],
                'EE002' => ['total' => 50,  'numeric' => 50, 'non_numeric' => 0],
            ],
            'total_sample_codes' => 2,
            'generated_at' => now()->toISOString(),
        ];

        foreach (['empodat_suspect.records_by_sample_code', 'empodat_suspect.substances_by_sample_code'] as $key) {
            Statistic::create([
                'database_entity_id' => $entity->id,
                'key' => $key,
                'meta_data' => $newShape,
            ]);
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('empodat_suspect.statistics.recordsBySampleCode'))
            ->assertOk()
            ->assertSee('EE001')
            ->assertSee('70 numeric / 30 N/A', false);

        $this->actingAs($user)
            ->get(route('empodat_suspect.statistics.substancesBySampleCode'))
            ->assertOk()
            ->assertSee('EE001');
    }

    public function test_sample_code_detail_pages_still_render_with_legacy_int_shape(): void
    {
        $entity = DatabaseEntity::firstOrCreate(
            ['code' => 'empodat_suspect'],
            [
                'name' => 'Empodat Suspect (test)',
                'is_public' => false,
                'show_in_dashboard' => false,
            ]
        );

        $legacyShape = [
            'data' => ['EE001' => 100, 'EE002' => 50],
            'total_sample_codes' => 2,
            'generated_at' => now()->toISOString(),
        ];

        foreach (['empodat_suspect.records_by_sample_code', 'empodat_suspect.substances_by_sample_code'] as $key) {
            Statistic::create([
                'database_entity_id' => $entity->id,
                'key' => $key,
                'meta_data' => $legacyShape,
            ]);
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('empodat_suspect.statistics.recordsBySampleCode'))
            ->assertOk()
            ->assertSee('EE001');

        $this->actingAs($user)
            ->get(route('empodat_suspect.statistics.substancesBySampleCode'))
            ->assertOk()
            ->assertSee('EE001');
    }
}
