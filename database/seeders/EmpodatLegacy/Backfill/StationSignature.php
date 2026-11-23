<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

/**
 * Canonical 13-column station signature. It reproduces, character for
 * character, the normalisation of `ImportStationsStep` and
 * `ImportEmpodatMainStep` (trim, empty string for NULL, comma-decimal fix,
 * coordinates compared at %.6F because %.10F drifts through PHP's
 * precision=14 float binding).
 *
 * Order: country, country_other, name, national_name, short_sample_code,
 * sample_code, provider_code, code_ec_wise, code_ec_other, code_other,
 * specific_locations, longitude, latitude.
 */
final class StationSignature
{
    /** Legacy `dct_analysis` columns that make up the signature. */
    public const LEGACY_COLUMNS = [
        'country', 'country_other', 'station_name', 'national_name',
        'short_sample_code', 'sample_code', 'provider_code',
        'code_ec_wise', 'code_ec_other', 'code_other',
        'longitude_decimal', 'latitude_decimal', 'specific_locations',
    ];

    /** Column order of a new `empodat_stations` insert (id is auto-generated). */
    public const INSERT_COLUMNS = [
        'name', 'country', 'country_other', 'country_id', 'country_other_id',
        'national_name', 'short_sample_code', 'sample_code', 'provider_code',
        'code_ec_wise', 'code_ec_other', 'code_other', 'specific_locations',
        'longitude', 'latitude', 'is_deprecated', 'created_at', 'updated_at',
    ];

    /**
     * Signature of a legacy `dct_analysis` row (object or array access via property).
     */
    public static function fromLegacy(object $row): string
    {
        return implode('|', [
            self::trimOrEmpty($row->country ?? null),
            self::trimOrEmpty($row->country_other ?? null),
            self::trimOrEmpty($row->station_name ?? null),
            self::trimOrEmpty($row->national_name ?? null),
            self::trimOrEmpty($row->short_sample_code ?? null),
            self::trimOrEmpty($row->sample_code ?? null),
            self::trimOrEmpty($row->provider_code ?? null),
            self::trimOrEmpty($row->code_ec_wise ?? null),
            self::trimOrEmpty($row->code_ec_other ?? null),
            self::trimOrEmpty($row->code_other ?? null),
            self::trimOrEmpty($row->specific_locations ?? null),
            self::decimalKey($row->longitude_decimal ?? null),
            self::decimalKey($row->latitude_decimal ?? null),
        ]);
    }

    /**
     * Signature of an existing PG `empodat_stations` row.
     */
    public static function fromStation(object $row): string
    {
        return implode('|', [
            self::trimOrEmpty($row->country ?? null),
            self::trimOrEmpty($row->country_other ?? null),
            self::trimOrEmpty($row->name ?? null),
            self::trimOrEmpty($row->national_name ?? null),
            self::trimOrEmpty($row->short_sample_code ?? null),
            self::trimOrEmpty($row->sample_code ?? null),
            self::trimOrEmpty($row->provider_code ?? null),
            self::trimOrEmpty($row->code_ec_wise ?? null),
            self::trimOrEmpty($row->code_ec_other ?? null),
            self::trimOrEmpty($row->code_other ?? null),
            self::trimOrEmpty($row->specific_locations ?? null),
            self::floatKey($row->longitude ?? null),
            self::floatKey($row->latitude ?? null),
        ]);
    }

    /**
     * Insert payload (named by INSERT_COLUMNS) for a station that does not yet exist.
     *
     * @param  array<string, int>  $countryIdByCode
     * @return array<string, mixed>
     */
    public static function insertRow(object $row, array $countryIdByCode, string $now): array
    {
        $country = self::trimToNull($row->country ?? null);
        $countryOther = self::trimToNull($row->country_other ?? null);

        return [
            'name' => self::trimToNull($row->station_name ?? null),
            'country' => $country,
            'country_other' => $countryOther,
            'country_id' => $country !== null ? ($countryIdByCode[$country] ?? null) : null,
            'country_other_id' => $countryOther !== null ? ($countryIdByCode[$countryOther] ?? null) : null,
            'national_name' => self::trimToNull($row->national_name ?? null),
            'short_sample_code' => self::trimToNull($row->short_sample_code ?? null),
            'sample_code' => self::trimToNull($row->sample_code ?? null),
            'provider_code' => self::trimToNull($row->provider_code ?? null),
            'code_ec_wise' => self::trimToNull($row->code_ec_wise ?? null),
            'code_ec_other' => self::trimToNull($row->code_ec_other ?? null),
            'code_other' => self::trimToNull($row->code_other ?? null),
            'specific_locations' => self::trimToNull($row->specific_locations ?? null),
            'longitude' => self::parseDecimal($row->longitude_decimal ?? null),
            'latitude' => self::parseDecimal($row->latitude_decimal ?? null),
            'is_deprecated' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    public static function trimOrEmpty(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    public static function trimToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Legacy varchar coordinate: comma to dot, float cast, '' for empty or non-numeric.
     */
    public static function decimalKey(mixed $value): string
    {
        $cleaned = str_replace(',', '.', trim((string) ($value ?? '')));
        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return '';
        }

        return self::floatKey((float) $cleaned);
    }

    /**
     * PG double (or float) coordinate: 6 decimals, trailing zeros trimmed.
     */
    public static function floatKey(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return rtrim(rtrim(sprintf('%.6F', (float) $value), '0'), '.');
    }

    public static function parseDecimal(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        $cleaned = str_replace(',', '.', trim((string) $value));
        if ($cleaned === '') {
            return null;
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
