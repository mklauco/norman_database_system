<?php

declare(strict_types=1);

namespace Tests\Feature\Empodat;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

/**
 * Integration test for the modal-data endpoint hit by the EMPODAT search-page
 * "open record" modal (`window.empodatRoutes.show`). Verifies that:
 *
 *   1. All sections the modal renders (Substance, Concentration, Station,
 *      Analytical Method, Data Source, Additional Record Details, Matrix
 *      Metadata) get the keys they expect in the JSON response.
 *   2. The `formatted_sampling_date` accessor falls back to the year when the
 *      legacy v1 zero-datetime sentinel `'0000-00-00 00:00:00'` is in
 *      `empodat_minor.sampling_date` — preventing the historic display bug
 *      where Carbon emits '-000001-11-30T00:00:00.000000Z' as the date.
 *   3. Real dates round-trip to `Y-m-d` correctly.
 *
 * Runs against the dedicated `norman_test` PostgreSQL database (per
 * `phpunit.xml`). Each test wraps its seed inserts in a DB transaction that
 * tearDown rolls back, so the test DB stays clean even across reruns.
 */
class EmpodatModalShowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->markTestSkipped('Cannot connect to PostgreSQL: '.$e->getMessage());
        }

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_modal_endpoint_returns_year_when_minor_sampling_date_is_legacy_zero_sentinel(): void
    {
        $id = $this->seedRecord(minorSamplingDate: '0000-00-00 00:00:00', samplingDateYear: 2024);

        $response = $this->getJson(route('codsearch.show', $id));

        $response->assertOk();
        $response->assertJsonPath('formatted_sampling_date', '2024');

        // The broken Carbon serialisation of the zero-datetime
        // ('-000001-11-30T00:00:00.000000Z' / '-0001-11-30...') must never
        // leak into the modal payload as the formatted date.
        $this->assertNotEquals('-0001-11-30', $response->json('formatted_sampling_date'));
        $this->assertStringNotContainsString(
            '-000001-11-30',
            (string) $response->json('formatted_sampling_date'),
        );
    }

    public function test_modal_endpoint_returns_full_iso_date_when_minor_has_real_date(): void
    {
        $id = $this->seedRecord(minorSamplingDate: '2024-11-13 00:00:00', samplingDateYear: 2024);

        $response = $this->getJson(route('codsearch.show', $id));

        $response->assertOk();
        $response->assertJsonPath('formatted_sampling_date', '2024-11-13');
    }

    public function test_modal_endpoint_returns_na_when_no_date_data_available(): void
    {
        $id = $this->seedRecord(minorSamplingDate: null, samplingDateYear: null);

        $response = $this->getJson(route('codsearch.show', $id));

        $response->assertOk();
        $response->assertJsonPath('formatted_sampling_date', 'N/A');
    }

    public function test_modal_endpoint_returns_all_expected_sections_with_loaded_relationships(): void
    {
        $id = $this->seedRecord();

        $response = $this->getJson(route('codsearch.show', $id));

        $response->assertOk();

        // Every key the Alpine modal reads. Missing key => the corresponding
        // modal section silently empties on the frontend.
        $response->assertJsonStructure([
            'id',
            'station_id',
            'substance_id',
            'matrix_id',
            'file_id',
            'country_id',
            'concentration_value',
            'concentration_indicator_id',
            'sampling_date_year',
            'formatted_sampling_date',
            'substance' => ['id', 'name'],
            'station' => ['id', 'name', 'latitude', 'longitude'],
            'matrix' => ['id', 'name'],
            'minor' => ['id', 'sampling_date'],
            'matrix_data',
        ]);

        $response->assertJsonPath('id', $id);
        $response->assertJsonPath('substance.name', 'Test substance');
        $response->assertJsonPath('station.name', 'Test station');
        $response->assertJsonPath('matrix.name', 'Soil - Bulk');
    }

    /**
     * Build the minimum FK chain (countries, matrices, substances, files,
     * stations, main, minor) required for `EmpodatController::show()` to
     * resolve and return JSON.
     *
     * Returns the id of the seeded `empodat_main` row.
     */
    private function seedRecord(
        ?string $minorSamplingDate = '0000-00-00 00:00:00',
        ?int $samplingDateYear = 2024,
    ): int {
        $countryId = DB::table('list_countries')->insertGetId([
            'code' => 'NL',
            'name' => 'Netherlands',
        ]);

        $matrixId = DB::table('list_matrices')->insertGetId([
            'name' => 'Soil - Bulk',
            'unit' => 'µg/kg dw',
        ]);

        $substanceId = DB::table('susdat_substances')->insertGetId([
            'code' => '00012345',
            'name' => 'Test substance',
        ]);

        // Indicator id=2 is the "Less than LoD" semantic in this app.
        // Seed it so the FK on empodat_main.concentration_indicator_id resolves.
        $concentrationIndicatorId = DB::table('list_concentration_indicators')->insertGetId([
            'name' => 'Less than LoD',
        ]);

        // is_deleted / is_protected default to false → visible to the
        // anonymous-user filter in `EmpodatMain::scopeByUserPermissions`.
        $fileId = DB::table('files')->insertGetId([
            'name' => 'Test file',
            'original_name' => 'test.xlsx',
        ]);

        $stationId = DB::table('empodat_stations')->insertGetId([
            'name' => 'Test station',
            'country' => 'NL',
            'country_id' => $countryId,
            'latitude' => 52.0,
            'longitude' => 5.0,
        ]);

        $mainId = DB::table('empodat_main')->insertGetId([
            'station_id' => $stationId,
            'substance_id' => $substanceId,
            'matrix_id' => $matrixId,
            'file_id' => $fileId,
            'country_id' => $countryId,
            'sampling_date_year' => $samplingDateYear,
            'concentration_value' => 0.0,
            // Less-than-LoD indicator — the modal renders this without
            // touching `concentration_value`.
            'concentration_indicator_id' => $concentrationIndicatorId,
        ]);

        DB::table('empodat_minor')->insert([
            'id' => $mainId,
            'sampling_date' => $minorSamplingDate,
        ]);

        return $mainId;
    }
}
