<?php

declare(strict_types=1);

namespace Tests\Feature\Empodat;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

/**
 * Deployment smoke test for the matrix-metadata codelists of issue #22.
 *
 * Before the fix the record modal printed the raw legacy id — "Dtw Id: 2",
 * "Dtp Id: 8" — because no `list_*` table existed to resolve it against. This
 * test seeds one waste-water record carrying every codelist that section
 * renders and asserts the modal payload comes back as text.
 *
 * It therefore fails loudly if a deployment runs the migration but forgets the
 * seeders: the tables would exist but stand empty, and the ids would fall
 * through to their Title-Cased column names again.
 *
 * Runs against the dedicated `norman_test` database (per `phpunit.xml`) and
 * wraps its inserts in a transaction that tearDown rolls back.
 */
class EmpodatMatrixCodelistDisplayTest extends TestCase
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

    public function test_modal_renders_waste_water_matrix_codelists_as_text(): void
    {
        $id = $this->seedWasteWaterRecord();

        $response = $this->getJson(route('codsearch.show', $id));

        $response->assertOk();

        $meta = $response->json('matrix_data.meta_data');

        $this->assertIsArray($meta, 'The waste-water matrix section is missing from the modal payload.');

        $this->assertSame('Municipal', $meta['Type of waste water'] ?? null);
        $this->assertSame('1. Simple screening', $meta['Type of treatment plant'] ?? null);
        $this->assertSame('C.1. Chlorination', $meta['Advanced treatment steps'] ?? null);

        // `dsa_id` resolves to "Other", so the "Other" rule must substitute the
        // `dsa_other` free text rather than printing the word "Other".
        $this->assertSame('Centrifuge', $meta['Sampling method'] ?? null);

        // No raw id may survive into the payload under a Title-Cased label.
        foreach (['Dtw Id', 'Dtp Id', 'Dtt Id', 'Dsa Id'] as $rawLabel) {
            $this->assertArrayNotHasKey($rawLabel, $meta);
        }
    }

    /**
     * Seed the FK chain plus one `empodat_matrix_water_waste` row carrying the
     * four codelist ids the modal must resolve.
     *
     * Returns the id of the seeded `empodat_main` row.
     */
    private function seedWasteWaterRecord(): int
    {
        $countryId = DB::table('list_countries')->insertGetId([
            'code' => 'NL',
            'name' => 'Netherlands',
        ]);

        // `empodat_matrix_link` is what EmpodatController::show() dispatches on
        // to pick the matrix table and the codelist map for that matrix type.
        $matrixId = DB::table('list_matrices')->insertGetId([
            'name' => 'Waste water',
            'unit' => 'µg/l',
            'empodat_matrix_link' => 'water_waste',
        ]);

        $substanceId = DB::table('susdat_substances')->insertGetId([
            'code' => '00012345',
            'name' => 'Test substance',
        ]);

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

        // The codelist rows the modal must resolve against. Ids and wording
        // match the legacy exports in `database/seeders/seeds/`, so this test
        // passes on a schema-only test database without depending on the
        // seeders having been run there.
        $this->seedCodelistRow('list_type_wastes', 2, 'Municipal');
        $this->seedCodelistRow('list_treatment_plants', 1, '1. Simple screening');
        $this->seedCodelistRow('list_advanced_treatment_steps', 7, 'C.1. Chlorination');
        $this->seedCodelistRow('list_sampling_methods', 18, 'Other');

        $mainId = DB::table('empodat_main')->insertGetId([
            'station_id' => $stationId,
            'substance_id' => $substanceId,
            'matrix_id' => $matrixId,
            'file_id' => $fileId,
            'country_id' => $countryId,
            'sampling_date_year' => 2024,
            'concentration_value' => 1.5,
        ]);

        DB::table('empodat_matrix_water_waste')->insert([
            'id' => $mainId,
            'dtw_id' => 2,   // Municipal
            'dtp_id' => 1,   // 1. Simple screening
            'dtt_id' => 7,   // C.1. Chlorination
            'dsa_id' => 18,  // Other -> falls back to dsa_other
            'dsa_other' => 'Centrifuge',
        ]);

        return $mainId;
    }

    /**
     * Insert one `list_*` row at a fixed legacy id, tolerating a database
     * where the seeders have already put it there.
     */
    private function seedCodelistRow(string $table, int $id, string $name): void
    {
        DB::table($table)->upsert(
            [['id' => $id, 'name' => $name]],
            ['id'],
            ['name'],
        );
    }
}
