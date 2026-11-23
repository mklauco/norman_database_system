<?php

declare(strict_types=1);

namespace Tests\Unit\EmpodatLegacyBackfill;

use Database\Seeders\EmpodatLegacy\Backfill\StationIndex;
use Database\Seeders\EmpodatLegacy\Backfill\StationSignature;
use PHPUnit\Framework\TestCase;
use stdClass;

class StationSignatureTest extends TestCase
{
    public function test_float_key_trims_trailing_zeros_at_six_decimals(): void
    {
        $this->assertSame('11.5', StationSignature::floatKey(11.5));
        $this->assertSame('10', StationSignature::floatKey(10.0));
        $this->assertSame('100', StationSignature::floatKey(100.0));
        $this->assertSame('', StationSignature::floatKey(null));
        $this->assertSame('10.123457', StationSignature::floatKey(10.1234567));
    }

    public function test_decimal_key_fixes_comma_decimals_and_blanks_garbage(): void
    {
        $this->assertSame('11.5', StationSignature::decimalKey('11,5'));
        $this->assertSame('11.5', StationSignature::decimalKey(' 11.5 '));
        $this->assertSame('', StationSignature::decimalKey(''));
        $this->assertSame('', StationSignature::decimalKey(null));
        $this->assertSame('', StationSignature::decimalKey('n/a'));
    }

    public function test_legacy_and_station_signatures_agree_for_the_same_station(): void
    {
        $legacy = $this->legacyRow(['country' => 'DE ', 'station_name' => ' Rhine ', 'longitude_decimal' => '10,1234567', 'latitude_decimal' => '50.5']);
        $station = $this->stationRow(['country' => 'DE', 'name' => 'Rhine', 'longitude' => 10.1234567, 'latitude' => 50.5]);

        $this->assertSame(StationSignature::fromStation($station), StationSignature::fromLegacy($legacy));
    }

    public function test_six_decimal_precision_absorbs_float_binding_drift(): void
    {
        $legacy = $this->legacyRow(['longitude_decimal' => '10.12345678901234']);
        $station = $this->stationRow(['longitude' => 10.1234567890]);
        $truncatedByPdo = $this->stationRow(['longitude' => 10.123456789012]);

        $this->assertSame(StationSignature::fromStation($station), StationSignature::fromLegacy($legacy));
        $this->assertSame(StationSignature::fromStation($truncatedByPdo), StationSignature::fromLegacy($legacy));
    }

    public function test_null_and_blank_text_are_the_same_and_signature_has_thirteen_parts(): void
    {
        $withNull = $this->legacyRow(['national_name' => null]);
        $withBlank = $this->legacyRow(['national_name' => '   ']);

        $this->assertSame(StationSignature::fromLegacy($withNull), StationSignature::fromLegacy($withBlank));
        $this->assertCount(13, explode('|', StationSignature::fromLegacy($withNull)));
    }

    public function test_different_stations_have_different_signatures(): void
    {
        $this->assertNotSame(
            StationSignature::fromLegacy($this->legacyRow(['sample_code' => 'A1'])),
            StationSignature::fromLegacy($this->legacyRow(['sample_code' => 'A2'])),
        );
    }

    public function test_insert_row_resolves_countries_and_nulls_blank_values(): void
    {
        $row = StationSignature::insertRow(
            $this->legacyRow(['country' => 'DE', 'country_other' => '', 'longitude_decimal' => '10,5', 'latitude_decimal' => 'x']),
            ['DE' => 7],
            '2026-11-22 10:00:00',
        );

        $this->assertSame(7, $row['country_id']);
        $this->assertNull($row['country_other']);
        $this->assertNull($row['country_other_id']);
        $this->assertSame(10.5, $row['longitude']);
        $this->assertNull($row['latitude']);
        $this->assertFalse($row['is_deprecated']);
        $this->assertSame(StationSignature::INSERT_COLUMNS, array_keys($row));
    }

    public function test_station_index_prefers_the_lowest_live_station(): void
    {
        $index = new StationIndex;
        $index->add('a', 5, true);
        $index->add('a', 7, false);
        $index->add('a', 9, false);
        $index->add('b', 3, false);
        $index->add('b', 4, true);
        $index->add('c', 1, true);

        $this->assertSame(7, $index->find('a'));
        $this->assertSame(3, $index->find('b'));
        $this->assertSame(1, $index->find('c'));
        $this->assertNull($index->find('missing'));
        $this->assertSame(3, $index->count());
        $this->assertSame(2, $index->ambiguousCount());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function legacyRow(array $overrides = []): stdClass
    {
        return (object) array_merge([
            'country' => 'DE',
            'country_other' => null,
            'station_name' => 'Rhine',
            'national_name' => 'Rhein',
            'short_sample_code' => 'S1',
            'sample_code' => 'SC1',
            'provider_code' => 'P1',
            'code_ec_wise' => null,
            'code_ec_other' => null,
            'code_other' => null,
            'specific_locations' => null,
            'longitude_decimal' => '10.5',
            'latitude_decimal' => '50.5',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function stationRow(array $overrides = []): stdClass
    {
        return (object) array_merge([
            'country' => 'DE',
            'country_other' => null,
            'name' => 'Rhine',
            'national_name' => 'Rhein',
            'short_sample_code' => 'S1',
            'sample_code' => 'SC1',
            'provider_code' => 'P1',
            'code_ec_wise' => null,
            'code_ec_other' => null,
            'code_other' => null,
            'specific_locations' => null,
            'longitude' => 10.5,
            'latitude' => 50.5,
        ], $overrides);
    }
}
