<?php

declare(strict_types=1);

namespace Tests\Feature\EmpodatSuspect;

use App\Services\EmpodatSuspect\MatrixMetadataLabeller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatrixMetadataLabellerTest extends TestCase
{
    use RefreshDatabase;

    private function seedLookups(): void
    {
        $now = now();

        DB::table('list_kingdoms')->insert(['id' => 6, 'name' => 'Plantae', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('list_measurements')->insert(['id' => 2, 'name' => 'Dry weight', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('list_categories')->insert(['id' => 22, 'name' => 'Plant', 'created_at' => $now, 'updated_at' => $now]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function labelBiota(array $overrides = []): array
    {
        $row = array_merge([
            'id' => 999,
            'station_id' => 153071,
            'empodat_main_id' => 999,
            'dki_id' => 6,
            'dmeas_id' => 2,
            'dcat_id' => 22,
            'dspc_id' => 0,
            'dord_id' => 99,
            'biota_weight' => '69.77',
            'storage_temperature' => '-20',
            'water_content' => '',
            'species_name' => 'Quercus suber',
        ], $overrides);

        return (new MatrixMetadataLabeller)->label('biota', (object) $row);
    }

    public function test_id_columns_resolve_to_lookup_labels(): void
    {
        $this->seedLookups();

        $rows = collect($this->labelBiota());

        $this->assertEquals('Plantae', $rows->firstWhere('label', 'Kingdom')['value'] ?? null);
        $this->assertEquals('Dry weight', $rows->firstWhere('label', 'Basis of measurement')['value'] ?? null);
        $this->assertEquals('Plant', $rows->firstWhere('label', 'Category')['value'] ?? null);
    }

    public function test_units_are_appended_from_legacy_dump(): void
    {
        $this->seedLookups();

        $rows = collect($this->labelBiota());

        $this->assertEquals('69.77 g', $rows->firstWhere('label', 'Biota weight')['value'] ?? null);
        $this->assertEquals('-20 °C', $rows->firstWhere('label', 'Storage temperature')['value'] ?? null);
    }

    public function test_zero_ids_and_blank_values_are_hidden(): void
    {
        $this->seedLookups();

        $rows = collect($this->labelBiota());

        // dspc_id = 0 means "not specified" -> never rendered.
        $this->assertNull($rows->firstWhere('label', 'Species (common name)'));
        // empty string water_content -> hidden.
        $this->assertNull($rows->firstWhere('label', 'Water content of tissue'));
    }

    public function test_internal_plumbing_columns_are_never_shown(): void
    {
        $this->seedLookups();

        $values = collect($this->labelBiota())->pluck('value');

        $this->assertFalse($values->contains('999'));
        $this->assertFalse($values->contains('153071'));
    }

    public function test_fk_columns_without_a_lookup_table_render_raw(): void
    {
        $this->seedLookups();

        $rows = collect($this->labelBiota());

        // list_orders does not exist yet -> dord_id stays a raw integer.
        $this->assertEquals('99', $rows->firstWhere('label', 'Order')['value'] ?? null);
    }
}
