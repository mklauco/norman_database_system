<?php

declare(strict_types=1);

namespace Tests\Feature\Backend;

use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\Backend\UserLoginRetention;
use App\Models\DatabaseEntity;
use App\Models\List\Country;
use App\Models\User;
use App\Services\Backend\UsageStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private UsageStatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UsageStatisticsService;
    }

    public function test_module_key_mapping(): void
    {
        $this->assertSame('empodat', $this->service->moduleKeyFor('empodat'));
        $this->assertSame('arbg', $this->service->moduleKeyFor('arbg.gene'));
        $this->assertSame('arbg', $this->service->moduleKeyFor('arbg.bacteria'));
        $this->assertSame('ecotox', $this->service->moduleKeyFor('ecotox.ecotox_pnec3'));
        $this->assertSame('prioritisation', $this->service->moduleKeyFor('prioritisation_modelling_danube'));
        $this->assertSame('unknown', $this->service->moduleKeyFor(null));
        $this->assertSame('unknown', $this->service->moduleKeyFor(''));
    }

    public function test_module_labels_use_entity_names_and_fallbacks(): void
    {
        DatabaseEntity::factory()->create(['code' => 'ecotox', 'name' => 'Ecotoxicology']);
        DatabaseEntity::factory()->create(['code' => 'arbg', 'name' => 'Antibiotic Resistant Bacteria/Genes']);

        $labels = $this->service->moduleLabels(['ecotox', 'unknown', 'arbg', 'mystery']);

        $this->assertSame(
            ['arbg' => 'Antibiotic Resistant Bacteria/Genes', 'ecotox' => 'Ecotoxicology', 'mystery' => 'Mystery', 'unknown' => 'Unknown'],
            $labels,
        );
        $this->assertSame(['arbg', 'ecotox', 'mystery', 'unknown'], array_keys($labels));
    }

    public function test_searches_are_zero_filled_for_every_month_and_module(): void
    {
        QueryLog::factory()->forModule('empodat')->at('2025-03-10 10:00:00')->create();
        QueryLog::factory()->forModule('ecotox.ecotox_pnec3')->at('2025-03-11 10:00:00')->create();
        QueryLog::factory()->forModule('ecotox.pnec3')->at('2025-07-01 10:00:00')->create();
        QueryLog::factory()->forModule('')->at('2025-07-02 10:00:00')->create();

        $result = $this->service->searchesByMonthAndModule(2025);

        $this->assertSame(range(1, 12), array_keys($result['months']));
        $this->assertEqualsCanonicalizing(['empodat', 'ecotox', 'unknown'], array_keys($result['modules']));

        foreach ($result['months'] as $row) {
            $this->assertEqualsCanonicalizing(['empodat', 'ecotox', 'unknown'], array_keys($row));
        }

        $this->assertSame(1, $result['months'][3]['empodat']);
        $this->assertSame(1, $result['months'][3]['ecotox']);
        $this->assertSame(1, $result['months'][7]['ecotox']);
        $this->assertSame(1, $result['months'][7]['unknown']);
        $this->assertSame(0, $result['months'][1]['empodat']);
        $this->assertSame(2, $result['monthTotals'][3]);
        $this->assertSame(0, $result['monthTotals'][12]);
        $this->assertSame(2, $result['moduleTotals']['ecotox']);
        $this->assertSame(4, $result['total']);
    }

    public function test_empty_year_still_has_twelve_months(): void
    {
        $result = $this->service->searchesByMonthAndModule(2020);

        $this->assertSame([], $result['modules']);
        $this->assertCount(12, $result['months']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(0, $result['monthTotals'][6]);
    }

    public function test_year_boundaries_are_respected(): void
    {
        QueryLog::factory()->at('2024-12-31 23:59:59')->create();
        QueryLog::factory()->at('2025-01-01 00:00:00')->create();
        QueryLog::factory()->at('2025-12-31 23:59:59')->create();
        QueryLog::factory()->at('2026-01-01 00:00:00')->create();

        $result = $this->service->searchesByMonthAndModule(2025);

        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['monthTotals'][1]);
        $this->assertSame(1, $result['monthTotals'][12]);
    }

    public function test_guest_and_user_filtering(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        QueryLog::factory()->forUser(null)->at('2025-05-01 10:00:00')->create();
        QueryLog::factory()->forUser(null)->at('2025-05-02 10:00:00')->create();
        QueryLog::factory()->forUser($user->id)->at('2025-05-03 10:00:00')->create();
        QueryLog::factory()->forUser($other->id)->at('2025-05-04 10:00:00')->create();

        $this->assertSame(4, $this->service->searchesByMonthAndModule(2025)['total']);
        $this->assertSame(2, $this->service->searchesByMonthAndModule(2025, null, true)['total']);
        $this->assertSame(1, $this->service->searchesByMonthAndModule(2025, $user->id)['total']);
    }

    public function test_downloads_exclude_failed_and_filter_by_user(): void
    {
        $user = User::factory()->create();

        ExportDownload::factory()->forUser($user->id)->forModule('susdat')->at('2025-02-10 10:00:00')->create();
        ExportDownload::factory()->forUser(null)->forModule('arbg.gene')->at('2025-02-11 10:00:00')->create();
        ExportDownload::factory()->forUser($user->id)->failed()->at('2025-02-12 10:00:00')->create();
        ExportDownload::factory()->forUser($user->id)->at('2024-12-31 23:59:59')->create();

        $all = $this->service->downloadsByMonthAndModule(2025);
        $this->assertSame(2, $all['total']);
        $this->assertSame(1, $all['moduleTotals']['arbg']);
        $this->assertSame(1, $all['moduleTotals']['susdat']);
        $this->assertSame(1, $this->service->downloadsByMonthAndModule(2025, $user->id)['total']);
        $this->assertSame(1, $this->service->downloadsByMonthAndModule(2025, null, true)['total']);
    }

    public function test_available_years_include_all_sources_and_current_year(): void
    {
        $user = User::factory()->create();

        QueryLog::factory()->at('2023-06-01 10:00:00')->create();
        UserLoginRetention::factory()->for($user)->at('2022-06-01 10:00:00')->create();
        ExportDownload::factory()->at('2024-06-01 10:00:00')->create();

        $years = $this->service->availableYears();

        $this->assertSame([(int) now()->year, 2024, 2023, 2022], $years);
    }

    public function test_user_usage_has_guest_row_and_sorting(): void
    {
        $country = Country::query()->forceCreate(['code' => 'SK', 'name' => 'Slovakia']);
        $busy = User::factory()->create(['first_name' => 'Zed', 'last_name' => 'Busy', 'country_id' => $country->id]);
        $quiet = User::factory()->create(['first_name' => 'Ann', 'last_name' => 'Quiet']);
        $loginOnly = User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Login']);

        QueryLog::factory()->count(3)->forUser($busy->id)->forModule('empodat')->at('2025-04-01 10:00:00')->create();
        QueryLog::factory()->forUser($quiet->id)->forModule('susdat')->at('2025-04-02 10:00:00')->create();
        QueryLog::factory()->count(2)->forUser(null)->forModule('susdat')->at('2025-04-03 10:00:00')->create();
        ExportDownload::factory()->forUser($busy->id)->at('2025-04-04 10:00:00')->create();
        UserLoginRetention::factory()->for($busy)->at('2025-04-05 10:00:00')->create();
        UserLoginRetention::factory()->for($busy)->at('2026-01-05 08:30:00')->create();
        UserLoginRetention::factory()->for($loginOnly)->at('2025-06-05 10:00:00')->create();

        $result = $this->service->userUsage(2025);

        $this->assertEqualsCanonicalizing(['empodat', 'susdat'], array_keys($result['modules']));
        $this->assertSame(
            ['Zed Busy', 'Guest', 'Ann Quiet', 'Bob Login'],
            array_column($result['rows'], 'name'),
        );

        $busyRow = $result['rows'][0];
        $this->assertSame($busy->id, $busyRow['user_id']);
        $this->assertFalse($busyRow['is_guest']);
        $this->assertSame(3, $busyRow['searches']['empodat']);
        $this->assertSame(0, $busyRow['searches']['susdat']);
        $this->assertSame(3, $busyRow['searches_total']);
        $this->assertSame(1, $busyRow['downloads_total']);
        $this->assertSame(1, $busyRow['logins']);
        $this->assertSame('2026-01-05 08:30', $busyRow['last_login']);
        $this->assertSame('Slovakia', $busyRow['country']);

        $guestRow = $result['rows'][1];
        $this->assertNull($guestRow['user_id']);
        $this->assertTrue($guestRow['is_guest']);
        $this->assertSame(2, $guestRow['searches_total']);
        $this->assertNull($guestRow['last_login']);

        $loginOnlyRow = $result['rows'][3];
        $this->assertSame(0, $loginOnlyRow['searches_total']);
        $this->assertSame(1, $loginOnlyRow['logins']);
        $this->assertSame(0, $loginOnlyRow['searches']['empodat']);
    }

    public function test_user_usage_without_guest_activity_has_no_guest_row(): void
    {
        $user = User::factory()->create();
        QueryLog::factory()->forUser($user->id)->at('2025-04-01 10:00:00')->create();

        $rows = $this->service->userUsage(2025)['rows'];

        $this->assertCount(1, $rows);
        $this->assertFalse($rows[0]['is_guest']);
    }

    public function test_logins_by_month_counts_distinct_users(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        UserLoginRetention::factory()->for($a)->at('2025-03-01 10:00:00')->create();
        UserLoginRetention::factory()->for($a)->at('2025-03-02 10:00:00')->create();
        UserLoginRetention::factory()->for($b)->at('2025-03-03 10:00:00')->create();
        UserLoginRetention::factory()->for($a)->at('2025-04-03 10:00:00')->create();
        UserLoginRetention::factory()->for($a)->at('2024-12-31 23:59:59')->create();

        $result = $this->service->loginsByMonth(2025);

        $this->assertSame(range(1, 12), array_keys($result['months']));
        $this->assertSame(['logins' => 3, 'users' => 2], $result['months'][3]);
        $this->assertSame(['logins' => 1, 'users' => 1], $result['months'][4]);
        $this->assertSame(['logins' => 0, 'users' => 0], $result['months'][1]);
        $this->assertSame(4, $result['total_logins']);
        $this->assertSame(2, $result['total_users']);
    }

    public function test_logins_by_geo_country_has_unknown_bucket(): void
    {
        Country::query()->forceCreate(['code' => 'SK', 'name' => 'Slovakia']);
        $user = User::factory()->create();

        UserLoginRetention::factory()->for($user)->fromCountry('SK')->at('2025-03-01 10:00:00')->create();
        UserLoginRetention::factory()->for($user)->fromCountry('SK')->at('2025-03-02 10:00:00')->create();
        UserLoginRetention::factory()->for($user)->fromCountry(null)->at('2025-03-03 10:00:00')->create();
        UserLoginRetention::factory()->for($user)->fromCountry('ZZ')->at('2025-03-04 10:00:00')->create();

        $result = $this->service->loginsByGeoCountry(2025);

        $this->assertSame('Slovakia', $result[0]['country']);
        $this->assertSame('SK', $result[0]['country_code']);
        $this->assertSame(2, $result[0]['logins']);
        $this->assertSame(1, $result[0]['users']);

        $unknown = collect($result)->firstWhere('country_code', null);
        $this->assertSame('Unknown', $unknown['country']);
        $this->assertSame(1, $unknown['logins']);

        $unmatched = collect($result)->firstWhere('country_code', 'ZZ');
        $this->assertSame('ZZ', $unmatched['country']);
    }

    public function test_logins_by_profile_country_has_not_set_bucket(): void
    {
        $country = Country::query()->forceCreate(['code' => 'DE', 'name' => 'Germany']);
        $withCountry = User::factory()->create(['country_id' => $country->id]);
        $withoutCountry = User::factory()->create();
        $anotherWithout = User::factory()->create();

        UserLoginRetention::factory()->for($withCountry)->at('2025-03-01 10:00:00')->create();
        UserLoginRetention::factory()->for($withoutCountry)->at('2025-03-02 10:00:00')->create();
        UserLoginRetention::factory()->for($withoutCountry)->at('2025-03-03 10:00:00')->create();
        UserLoginRetention::factory()->for($anotherWithout)->at('2025-03-04 10:00:00')->create();
        UserLoginRetention::factory()->for($withCountry)->at('2024-03-04 10:00:00')->create();

        $result = $this->service->loginsByProfileCountry(2025);

        $this->assertSame(['country' => 'Not set', 'users' => 2, 'logins' => 3], $result[0]);
        $this->assertSame(['country' => 'Germany', 'users' => 1, 'logins' => 1], $result[1]);
    }
}
