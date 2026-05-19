<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

/**
 * Phase 2 — distinct upsert of stations.
 *
 * Source : SELECT DISTINCT (13-col station signature) FROM legacy `dct_analysis`
 *          WHERE id > $sinceId.
 * Target : PG `empodat_stations`.
 *
 * Station signature (13 cols, identity per v1 doc):
 *   country, country_other, station_name (-> name), national_name,
 *   short_sample_code, sample_code, provider_code,
 *   code_ec_wise, code_ec_other, code_other,
 *   longitude_decimal (-> longitude double), latitude_decimal (-> latitude double),
 *   specific_locations
 *
 * For each distinct signature: lookup in PG; if absent, INSERT with
 * country_id / country_other_id resolved via `list_countries.code`.
 * is_deprecated defaults false; timestamps now().
 *
 * Side effect: populate an in-memory map (signature -> empodat_stations.id)
 * for use by ImportEmpodatMainStep.
 */
class ImportStationsStep extends Step
{
    /** @var array<string, int> signature-hash -> empodat_stations.id */
    public array $signatureToStationId = [];

    public function name(): string
    {
        return 'Upsert stations (distinct dct_analysis -> empodat_stations)';
    }

    public function run(): void
    {
        // TODO: extract distinct signatures from legacy delta, upsert into PG.
        // Populate $this->signatureToStationId for downstream use.
    }
}
