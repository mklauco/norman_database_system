<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Throwable;

/**
 * Phase 3a — bulk insert delta into `empodat_main` (file_id left NULL; Phase 3b populates).
 *
 * Source : legacy `dct_analysis` WHERE id > $sinceId (skips the 1-row overlap at sinceId).
 * Target : PG `empodat_main` (id preserved from legacy).
 *
 * FK resolution (3 in-memory hashmaps, built once at step start):
 *   substance_id    = susdat_substances.id   keyed by LPAD(sus_id, 8, '0')     -> NULL if no match (71 orphan sus_ids)
 *   station_id      = empodat_stations.id    keyed by 13-col canonical sig     -> NULL if no match (should never happen — Phase 2 covered all)
 *   country_id      = list_countries.id      keyed by ISO-2 code               -> NULL if no match (28/28 covered)
 *
 * Passthroughs:
 *   matrix_id, sampling_date_year, concentration_indicator_id,
 *   concentration_value, method_id, data_source_id  -> direct copy
 *   (method_id, data_source_id may be orphan; Phase 5 handles per policy)
 *
 * Stream: chunkById(5000) on dct_analysis with PK ordering. PG INSERT batched
 * via insertOnConflictDoNothing (auto-chunks under 65K param cap). Idempotent on
 * primary key: re-runs are no-ops.
 */
class ImportEmpodatMainStep extends Step
{
    private const TABLE = 'empodat_main';

    private const CHUNK_SIZE = 5000;

    public function name(): string
    {
        return 'Import empodat_main (dct_analysis -> empodat_main, file_id NULL)';
    }

    public function run(): void
    {
        $start = microtime(true);

        if ($this->previouslyCompleted(self::TABLE)) {
            $this->note('Already completed for this since_id; skipping (run rollback to re-run).');
            $this->logBulkStep(self::TABLE, 0, null, null, (int) ((microtime(true) - $start) * 1000));

            return;
        }

        try {
            $stationSigToId = $this->buildStationSigMap();
            $substanceCodeToId = $this->buildSubstanceMap();
            $countryCodeToId = $this->buildCountryMap();

            $this->note(sprintf(
                'Lookups loaded: stations=%d  substances=%d  countries=%d',
                count($stationSigToId),
                count($substanceCodeToId),
                count($countryCodeToId),
            ));

            $totalInserted = 0;
            $idMin = null;
            $idMax = null;
            $stationMisses = 0;

            $this->legacy()
                ->table('dct_analysis')
                ->where('id', '>', $this->sinceId)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use (
                    $stationSigToId,
                    $substanceCodeToId,
                    $countryCodeToId,
                    &$totalInserted,
                    &$idMin,
                    &$idMax,
                    &$stationMisses,
                ) {
                    $payload = [];

                    foreach ($rows as $r) {
                        $sig = $this->stationSignature($r);
                        $stationId = $stationSigToId[$sig] ?? null;
                        if ($stationId === null) {
                            $stationMisses++;
                        }

                        $code = str_pad((string) $r->sus_id, 8, '0', STR_PAD_LEFT);

                        $payload[] = [
                            'id' => (int) $r->id,
                            'station_id' => $stationId,
                            'substance_id' => $substanceCodeToId[$code] ?? null,
                            'matrix_id' => (int) $r->matrix,
                            'sampling_date_year' => (int) $r->sampling_date_y,
                            'concentration_indicator_id' => (int) $r->dic_id,
                            'concentration_value' => (float) $r->concentration_value,
                            'method_id' => $r->id_method !== null ? (int) $r->id_method : null,
                            'data_source_id' => $r->id_data !== null ? (int) $r->id_data : null,
                            'country_id' => $countryCodeToId[trim((string) $r->country)] ?? null,
                            'file_id' => null,
                        ];
                    }

                    $insertedIds = $this->insertOnConflictDoNothing(self::TABLE, $payload);
                    if ($insertedIds !== []) {
                        $totalInserted += count($insertedIds);
                        $chunkMin = min($insertedIds);
                        $chunkMax = max($insertedIds);
                        $idMin = $idMin === null ? $chunkMin : min($idMin, $chunkMin);
                        $idMax = $idMax === null ? $chunkMax : max($idMax, $chunkMax);
                    }
                }, 'id');

            if ($stationMisses > 0) {
                $this->note("WARNING: $stationMisses delta rows had no matching station signature in PG (expected 0).");
            }

            $this->note(sprintf(
                'Inserted=%d  id range=%s..%s',
                $totalInserted,
                $idMin ?? 'n/a',
                $idMax ?? 'n/a',
            ));

            $this->logBulkStep(self::TABLE, $totalInserted, $idMin, $idMax, (int) ((microtime(true) - $start) * 1000));
        } catch (Throwable $e) {
            $this->logStepFailed(self::TABLE, $e);
            throw $e;
        }
    }

    /**
     * Build canonical signature string for a legacy dct_analysis row.
     * Must match the canonicalisation used when Phase 2 inserted into empodat_stations.
     */
    private function stationSignature(object $r): string
    {
        return implode('|', [
            $this->trimOrEmpty($r->country),
            $this->trimOrEmpty($r->country_other),
            $this->trimOrEmpty($r->station_name),
            $this->trimOrEmpty($r->national_name),
            $this->trimOrEmpty($r->short_sample_code),
            $this->trimOrEmpty($r->sample_code),
            $this->trimOrEmpty($r->provider_code),
            $this->trimOrEmpty($r->code_ec_wise),
            $this->trimOrEmpty($r->code_ec_other),
            $this->trimOrEmpty($r->code_other),
            $this->trimOrEmpty($r->specific_locations),
            $this->decimalKey($r->longitude_decimal),
            $this->decimalKey($r->latitude_decimal),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function buildStationSigMap(): array
    {
        $map = [];
        $this->pg()
            ->table('empodat_stations')
            ->select([
                'id', 'name', 'country', 'country_other', 'national_name',
                'short_sample_code', 'sample_code', 'provider_code',
                'code_ec_wise', 'code_ec_other', 'code_other',
                'specific_locations', 'longitude', 'latitude',
            ])
            ->orderBy('id')
            ->chunk(20000, function ($rows) use (&$map) {
                foreach ($rows as $r) {
                    $sig = implode('|', [
                        $this->trimOrEmpty($r->country),
                        $this->trimOrEmpty($r->country_other),
                        $this->trimOrEmpty($r->name),
                        $this->trimOrEmpty($r->national_name),
                        $this->trimOrEmpty($r->short_sample_code),
                        $this->trimOrEmpty($r->sample_code),
                        $this->trimOrEmpty($r->provider_code),
                        $this->trimOrEmpty($r->code_ec_wise),
                        $this->trimOrEmpty($r->code_ec_other),
                        $this->trimOrEmpty($r->code_other),
                        $this->trimOrEmpty($r->specific_locations),
                        $this->floatKey($r->longitude),
                        $this->floatKey($r->latitude),
                    ]);
                    $map[$sig] = (int) $r->id;
                }
            });

        return $map;
    }

    /**
     * @return array<string, int>
     */
    private function buildSubstanceMap(): array
    {
        $map = [];
        $this->pg()
            ->table('susdat_substances')
            ->select(['id', 'code'])
            ->orderBy('id')
            ->chunk(20000, function ($rows) use (&$map) {
                foreach ($rows as $r) {
                    $map[(string) $r->code] = (int) $r->id;
                }
            });

        return $map;
    }

    /**
     * @return array<string, int>
     */
    private function buildCountryMap(): array
    {
        return $this->pg()
            ->table('list_countries')
            ->pluck('id', 'code')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Match Phase 2 canonicalisation: trim text, '' -> empty.
     */
    private function trimOrEmpty(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    /**
     * Canonical key for a legacy varchar lat/lon. Matches Phase 2 parseDecimal():
     * comma->dot, cast to float, '' for non-numeric/empty.
     */
    private function decimalKey(mixed $value): string
    {
        $cleaned = str_replace(',', '.', trim((string) ($value ?? '')));
        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return '';
        }

        return $this->floatKey((float) $cleaned);
    }

    /**
     * Canonical key for a PG double. Must match decimalKey() for the same logical value.
     * 6 decimals = ~10 cm station precision; tight enough for identity, loose enough
     * to absorb PHP's default precision=14 round-trip when binding floats to PG.
     */
    private function floatKey(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return rtrim(rtrim(sprintf('%.6F', (float) $value), '0'), '.');
    }
}
