<?php

declare(strict_types=1);

namespace Tests\Feature\Empodat;

use App\Services\Empodat\EmpodatRecordDisplay;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

/**
 * Covers the EMPODAT record display rules requested in issue #22:
 *
 *   - codelist columns render the name from their `list_*` table, never the
 *     raw id;
 *   - when that name is "Other" and the sibling `*_other` column holds free
 *     text, the free text replaces it — as one row, not two;
 *   - `*_other` is ignored when the selected codelist entry is not "Other",
 *     and when it holds the v1 import artefact (a copy of the id);
 *   - LOD / LOQ carry the unit implied by the matrix;
 *   - fields are labelled from `config/empodat_field_labels.php`.
 *
 * Runs against the dedicated `norman_test` PostgreSQL database (per
 * `phpunit.xml`); every insert is rolled back in tearDown.
 */
class EmpodatRecordDisplayFieldsTest extends TestCase
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

    public function test_other_codelist_entry_is_replaced_by_the_free_text_in_a_single_row(): void
    {
        $otherId = $this->insertList('list_standardised_methods', 'Other');

        $id = $this->seedRecord(analyticalMethod: [
            'standardised_method_id' => $otherId,
            'standardised_method_other' => 'Home-made SOP',
        ]);

        $details = $this->getJson(route('codsearch.show', $id))
            ->assertOk()
            ->json('analytical_method_details');

        $this->assertSame('Home-made SOP', $details['Has standardised analytical method been used? - Code']);
        $this->assertArrayNotHasKey('Standardised Method Other', $details);
        $this->assertNotContains('Other', $details);
    }

    public function test_free_text_is_ignored_when_the_selected_codelist_entry_is_not_other(): void
    {
        $this->insertList('list_type_monitorings', 'Other');
        $investigativeId = $this->insertList('list_type_monitorings', 'Investigative');

        $id = $this->seedRecord(dataSource: [
            'type_monitoring_id' => $investigativeId,
            'type_monitoring_other' => 'Operational monitoring',
        ]);

        $details = $this->getJson(route('codsearch.show', $id))
            ->assertOk()
            ->json('data_source_details');

        $this->assertSame('Investigative', $details['Type of monitoring']);
        $this->assertArrayNotHasKey('Type Monitoring Other', $details);
    }

    /**
     * The v1 import left `sample_preparation_method_other` holding a copy of
     * the codelist id on ~8 800 rows — the "sample_preparation_method_other
     * = 16" reported in issue #22. That is not free text and must not be
     * shown in place of the codelist name.
     */
    public function test_numeric_free_text_left_by_the_v1_import_is_not_shown(): void
    {
        $otherId = $this->insertList('list_sample_preparation_methods', 'Other');

        $id = $this->seedRecord(analyticalMethod: [
            'sample_preparation_method_id' => $otherId,
            'sample_preparation_method_other' => (string) $otherId,
        ]);

        $details = $this->getJson(route('codsearch.show', $id))
            ->assertOk()
            ->json('analytical_method_details');

        $this->assertSame('Other', $details['Sample preparation method']);
        $this->assertNotContains((string) $otherId, $details);
    }

    public function test_lod_and_loq_are_shown_with_the_unit_implied_by_the_matrix(): void
    {
        $id = $this->seedRecord(
            analyticalMethod: ['lod' => 5, 'loq' => 12.5],
            matrixUnit: 'µg/l',
        );

        $details = $this->getJson(route('codsearch.show', $id))
            ->assertOk()
            ->json('analytical_method_details');

        $this->assertSame('5 µg/l', $details['Limit of Detection (LoD)']);
        $this->assertSame('12.5 µg/l', $details['Limit of Quantification (LoQ)']);
    }

    public function test_matrix_unit_html_is_normalised_for_text_rendering(): void
    {
        $id = $this->seedRecord(
            analyticalMethod: ['lod' => 2],
            matrixUnit: 'µg/m<sup>3</sup>',
        );

        $response = $this->getJson(route('codsearch.show', $id))->assertOk();

        $this->assertSame('µg/m³', $response->json('matrix.display_unit'));
        $this->assertSame('2 µg/m³', $response->json('analytical_method_details.Limit of Detection (LoD)'));
    }

    public function test_minor_codelist_ids_are_labelled_and_resolved(): void
    {
        $precisionId = $this->insertList('list_coordinate_precisions', 'Average (range 10-100 m)');

        $id = $this->seedRecord(minor: ['dpc_id' => $precisionId]);

        $details = $this->getJson(route('codsearch.show', $id))
            ->assertOk()
            ->json('additional_details');

        $this->assertSame('Average (range 10-100 m)', $details['Precision of coordinates']);
        $this->assertArrayNotHasKey('dpc_id', $details);
    }

    /**
     * Surface water is the most common matrix in EMPODAT and had no lookup
     * configuration at all, so its metadata rendered as "Df Id: 1".
     */
    public function test_surface_water_matrix_metadata_resolves_shared_codelists(): void
    {
        $fractionId = $this->insertList('list_fractions', 'Dissolved fraction');

        $id = $this->seedRecord(
            matrixLink: 'empodat_matrix_water_surface',
            matrixMetadata: ['df_id' => $fractionId, 'ph' => 7.4],
        );

        $metaData = $this->getJson(route('codsearch.show', $id))
            ->assertOk()
            ->json('matrix_data.meta_data');

        $this->assertSame('Dissolved fraction', $metaData['Fraction']);
        // `ph` is a varchar in this table, so it round-trips as a string.
        $this->assertSame('7.4', $metaData['pH']);
        $this->assertArrayNotHasKey('df_id', $metaData);
    }

    public function test_plain_unit_leaves_units_without_markup_untouched(): void
    {
        $this->assertSame('µg/kg dry weight', EmpodatRecordDisplay::plainUnit('µg/kg dry weight'));
        $this->assertNull(EmpodatRecordDisplay::plainUnit(null));
        $this->assertSame('ng/m³', EmpodatRecordDisplay::plainUnit('ng/m<sup>3</sup>'));
    }

    /**
     * Build the FK chain `EmpodatController::show()` needs, plus whichever
     * of the optional related rows a test asks for.
     *
     * @param  array<string, mixed>  $analyticalMethod
     * @param  array<string, mixed>  $dataSource
     * @param  array<string, mixed>  $minor
     * @param  array<string, mixed>  $matrixMetadata
     */
    private function seedRecord(
        array $analyticalMethod = [],
        array $dataSource = [],
        array $minor = [],
        array $matrixMetadata = [],
        ?string $matrixLink = null,
        string $matrixUnit = 'µg/l',
    ): int {
        $countryId = DB::table('list_countries')->insertGetId([
            'code' => 'NL',
            'name' => 'Netherlands',
        ]);

        $matrixId = DB::table('list_matrices')->insertGetId([
            'name' => 'Surface water - River water',
            'unit' => $matrixUnit,
            'empodat_matrix_link' => $matrixLink,
        ]);

        $substanceId = DB::table('susdat_substances')->insertGetId([
            'code' => '00012345',
            'name' => 'Test substance',
        ]);

        $fileId = DB::table('files')->insertGetId([
            'name' => 'Test file',
            'original_name' => 'test.xlsx',
        ]);

        $concentrationIndicatorId = DB::table('list_concentration_indicators')->insertGetId([
            'name' => 'Measured value',
        ]);

        $stationId = DB::table('empodat_stations')->insertGetId([
            'name' => 'Test station',
            'country' => 'NL',
            'country_id' => $countryId,
            'latitude' => 52.0,
            'longitude' => 5.0,
        ]);

        $methodId = $analyticalMethod === []
            ? null
            : DB::table('empodat_analytical_methods')->insertGetId($analyticalMethod);

        $dataSourceId = $dataSource === []
            ? null
            : DB::table('empodat_data_sources')->insertGetId($dataSource);

        $mainId = DB::table('empodat_main')->insertGetId([
            'station_id' => $stationId,
            'substance_id' => $substanceId,
            'matrix_id' => $matrixId,
            'file_id' => $fileId,
            'country_id' => $countryId,
            'method_id' => $methodId,
            'data_source_id' => $dataSourceId,
            'sampling_date_year' => 2024,
            'concentration_value' => 0.15,
            'concentration_indicator_id' => $concentrationIndicatorId,
        ]);

        DB::table('empodat_minor')->insert(['id' => $mainId] + $minor);

        if ($matrixMetadata !== [] && $matrixLink !== null) {
            DB::table($matrixLink)->insert(['id' => $mainId] + $matrixMetadata);
        }

        return $mainId;
    }

    private function insertList(string $table, string $name): int
    {
        return (int) DB::table($table)->insertGetId(['name' => $name]);
    }
}
