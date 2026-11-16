<?php

declare(strict_types=1);

namespace Tests\Feature\Backend;

use App\Models\Backend\QueryLog;
use App\Models\User;
use App\Services\Backend\UsageStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_key_mapping(): void
    {
        $service = new UsageStatisticsService;

        $this->assertSame('empodat', $service->moduleKeyFor('empodat'));
        $this->assertSame('arbg', $service->moduleKeyFor('arbg.gene'));
        $this->assertSame('ecotox', $service->moduleKeyFor('ecotox.ecotox_pnec3'));
        $this->assertSame('prioritisation', $service->moduleKeyFor('prioritisation_modelling_danube'));
        $this->assertSame('unknown', $service->moduleKeyFor(null));
        $this->assertSame('unknown', $service->moduleKeyFor(''));
    }

    public function test_searches_by_month_respect_year_and_guest_filter(): void
    {
        $user = User::factory()->create();

        QueryLog::factory()->at('2024-12-31 23:59:59')->create();
        QueryLog::factory()->forModule('ecotox.ecotox')->at('2025-01-01 00:00:00')->create();
        QueryLog::factory()->forModule('ecotox.pnec3')->forUser($user->id)->at('2025-12-31 23:59:59')->create();
        QueryLog::factory()->at('2026-01-01 00:00:00')->create();

        $service = new UsageStatisticsService;
        $all = $service->searchesByMonthAndModule(2025);

        $this->assertSame(range(1, 12), array_keys($all['months']));
        $this->assertSame(2, $all['total']);
        $this->assertSame(2, $all['moduleTotals']['ecotox']);
        $this->assertSame(1, $all['monthTotals'][1]);
        $this->assertSame(1, $all['monthTotals'][12]);
        $this->assertSame(1, $service->searchesByMonthAndModule(2025, null, true)['total']);
        $this->assertSame(1, $service->searchesByMonthAndModule(2025, $user->id)['total']);
    }
}
