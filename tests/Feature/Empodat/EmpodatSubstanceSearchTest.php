<?php

declare(strict_types=1);

namespace Tests\Feature\Empodat;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

/**
 * Searching Chemical Occurrence Data by substance must return that substance's
 * records, with the columns the results table shows.
 *
 * The search page is the entry point to EMPODAT — if it returns nothing, or
 * returns rows with a blank Substance, Matrix or Country, the module is
 * effectively broken no matter what else works. Issue #22 was reported exactly
 * that way: results arrived, but without those three columns.
 *
 * Runs against the dedicated `norman_test` database (per `phpunit.xml`) and
 * wraps its inserts in a transaction that tearDown rolls back.
 */
class EmpodatSubstanceSearchTest extends TestCase
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

    public function test_searching_by_substance_returns_that_substances_records(): void
    {
        $seeded = $this->seedSubstanceWithRecords('Triclosan', recordCount: 3);

        $response = $this->get(route('codsearch.search', ['substances' => [$seeded['substance_id']]]));

        $response->assertOk();

        // The substance, and the three columns whose absence was the original
        // complaint, must all reach the page.
        $response->assertSee('Triclosan');
        $response->assertSee('Surface water - River water');
        $response->assertSee('Netherlands');
    }

    public function test_searching_by_substance_excludes_other_substances(): void
    {
        $wanted = $this->seedSubstanceWithRecords('Triclosan', recordCount: 2);
        $other = $this->seedSubstanceWithRecords('Ibuprofen', recordCount: 2, code: '00000214');

        $response = $this->get(route('codsearch.search', ['substances' => [$wanted['substance_id']]]));

        $response->assertOk();
        $response->assertSee('Triclosan');
        $response->assertDontSee('Ibuprofen');
    }

    public function test_searching_a_substance_with_no_records_does_not_error(): void
    {
        $empty = DB::table('susdat_substances')->insertGetId([
            'code' => '00099999',
            'name' => 'Substance With No Occurrence Data',
        ]);

        $response = $this->get(route('codsearch.search', ['substances' => [$empty]]));

        // An empty result set is a legitimate answer, not a failure.
        $response->assertOk();
    }

    /**
     * Seed a substance plus `recordCount` EMPODAT records for it, with the
     * station, country and matrix the results table renders.
     *
     * @return array{substance_id: int, main_ids: list<int>}
     */
    private function seedSubstanceWithRecords(string $name, int $recordCount, string $code = '00009700'): array
    {
        $countryId = DB::table('list_countries')->insertGetId([
            'code' => 'NL',
            'name' => 'Netherlands',
        ]);

        $matrixId = DB::table('list_matrices')->insertGetId([
            'name' => 'Surface water - River water',
            'unit' => 'µg/l',
            'empodat_matrix_link' => 'water_surface',
        ]);

        $substanceId = DB::table('susdat_substances')->insertGetId([
            'code' => $code,
            'name' => $name,
        ]);

        // is_deleted / is_protected default to false, so the record is visible
        // to the anonymous-user filter in EmpodatMain::scopeByUserPermissions.
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

        $mainIds = [];

        for ($i = 0; $i < $recordCount; $i++) {
            $mainIds[] = DB::table('empodat_main')->insertGetId([
                'station_id' => $stationId,
                'substance_id' => $substanceId,
                'matrix_id' => $matrixId,
                'file_id' => $fileId,
                'country_id' => $countryId,
                'sampling_date_year' => 2020 + $i,
                'concentration_value' => 0.5 + $i,
            ]);
        }

        return ['substance_id' => $substanceId, 'main_ids' => $mainIds];
    }
}
