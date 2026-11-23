<?php

declare(strict_types=1);

namespace Tests\Unit\EmpodatLegacyBackfill;

use Database\Seeders\EmpodatLegacy\Backfill\BackfillTables;
use Database\Seeders\EmpodatLegacy\Backfill\FileRangeIndex;
use Database\Seeders\EmpodatLegacy\Backfill\TableSpec;
use Database\Seeders\EmpodatLegacy\Backfill\ValueCoercer;
use PHPUnit\Framework\TestCase;

class FileRangeIndexAndCoercionTest extends TestCase
{
    public function test_overlapping_ranges_resolve_to_the_highest_list_id(): void
    {
        $index = new FileRangeIndex([[1, 10, 5], [5, 20, 7], [15, 30, 6]]);

        $this->assertSame(5, $index->fileIdFor(1));
        $this->assertSame(5, $index->fileIdFor(4));
        $this->assertSame(7, $index->fileIdFor(5));
        $this->assertSame(7, $index->fileIdFor(12));
        $this->assertSame(7, $index->fileIdFor(18));
        $this->assertSame(6, $index->fileIdFor(21));
        $this->assertSame(6, $index->fileIdFor(30));
        $this->assertNull($index->fileIdFor(31));
        $this->assertNull($index->fileIdFor(0));
        $this->assertSame([5, 7], $index->winnersWithin(1, 12));
    }

    public function test_ids_outside_every_range_have_no_file(): void
    {
        $index = new FileRangeIndex([[1, 2, 3], [10, 11, 4]]);

        $this->assertSame(3, $index->fileIdFor(2));
        $this->assertNull($index->fileIdFor(5));
        $this->assertSame(4, $index->fileIdFor(10));
        $this->assertSame(2, $index->segmentCount());
    }

    public function test_an_empty_or_inverted_range_set_matches_nothing(): void
    {
        $this->assertNull((new FileRangeIndex([]))->fileIdFor(1));
        $this->assertNull((new FileRangeIndex([[10, 5, 1]]))->fileIdFor(7));
    }

    public function test_a_single_id_file_range_matches_that_id_only(): void
    {
        $index = new FileRangeIndex([[163_702_564, 163_702_564, 660]]);

        $this->assertSame(660, $index->fileIdFor(163_702_564));
        $this->assertNull($index->fileIdFor(163_702_565));
    }

    public function test_numeric_text_keeps_numbers_and_nulls_everything_else(): void
    {
        $this->assertSame('1.5', ValueCoercer::numericText('1,5'));
        $this->assertSame('2.5', ValueCoercer::numericText(' 2.5 '));
        $this->assertSame('1e-5', ValueCoercer::numericText('1e-5'));
        $this->assertNull(ValueCoercer::numericText('abc'));
        $this->assertNull(ValueCoercer::numericText('<0.5'));
        $this->assertNull(ValueCoercer::numericText(''));
        $this->assertNull(ValueCoercer::numericText(null));
        $this->assertSame('0.1', ValueCoercer::numericText(0.1));
        $this->assertSame('7', ValueCoercer::numericText(7));
    }

    public function test_int_and_text_conversion_preserve_null(): void
    {
        $this->assertNull(ValueCoercer::intText(null));
        $this->assertSame('7', ValueCoercer::intText('7'));
        $this->assertSame('0', ValueCoercer::intText(0));
        $this->assertNull(ValueCoercer::text(null));
        $this->assertSame('', ValueCoercer::text(''));
        $this->assertSame('2018', ValueCoercer::text(2018));
    }

    public function test_copy_line_escapes_control_characters_and_marks_nulls(): void
    {
        $line = ValueCoercer::copyLine(["a\tb", null, 'x\\y', "l1\nl2", "cr\r", "nul\0"]);

        $this->assertSame("a\\tb\t\\N\tx\\\\y\tl1\\nl2\tcr\\r\tnul", $line);
    }

    public function test_table_spec_reads_aliases_and_null_fills(): void
    {
        $spec = new TableSpec('t', 'legacy_t', 'a:s,b:i,c:n,d:s', ['c' => null, 'd' => 'legacy_d']);

        $this->assertSame(['id', 'a', 'b', 'c', 'd'], $spec->pgColumns());
        $this->assertSame(['id', 'a', 'b', 'legacy_d'], $spec->legacySelect());
        $this->assertSame(['c'], $spec->nullFilled());

        $line = $spec->copyLine((object) ['id' => '5', 'a' => 'x', 'b' => '3', 'legacy_d' => null]);

        $this->assertSame("5\tx\t3\t\\N\t\\N", $line);
    }

    public function test_table_spec_convert_reports_legacy_values_that_become_null(): void
    {
        $spec = new TableSpec('t', 'legacy_t', 'a:n,b:n,c:n,d:s,e:i');

        $converted = $spec->convert((object) ['id' => 7, 'a' => '<0.5', 'b' => '1,5', 'c' => '', 'd' => 'free text', 'e' => 4]);

        $this->assertSame(
            ['id' => '7', 'a' => null, 'b' => '1.5', 'c' => null, 'd' => 'free text', 'e' => '4'],
            $converted['values'],
        );
        $this->assertSame(['a'], $converted['nulled']);
        $this->assertSame("7\t\\N\t1.5\t\\N\tfree text\t4", $spec->copyLine((object) ['id' => 7, 'a' => '<0.5', 'b' => '1,5', 'c' => '', 'd' => 'free text', 'e' => 4]));
    }

    public function test_registry_applies_default_null_aliases_to_the_two_columns_without_a_legacy_source(): void
    {
        $specs = [];
        foreach (BackfillTables::all([
            'empodat_matrix_biota.packing_material' => null,
            'empodat_matrix_water_surface.ocean_sea_region_name' => null,
        ]) as $spec) {
            $specs[$spec->pgTable] = $spec;
        }

        $this->assertSame(['packing_material'], $specs['empodat_matrix_biota']->nullFilled());
        $this->assertSame(['ocean_sea_region_name'], $specs['empodat_matrix_water_surface']->nullFilled());
        $this->assertSame([], $specs['empodat_matrix_water_ground']->nullFilled());
        $this->assertSame('dct_analysis', $specs['empodat_minor']->legacyTable);
        $this->assertArrayNotHasKey('sampling_date_m', $specs['empodat_minor']->columns());
        $this->assertCount(10, $specs);
    }
}
