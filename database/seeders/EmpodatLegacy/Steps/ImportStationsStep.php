<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Throwable;

/**
 * Phase 2 — insert distinct stations from the delta.
 *
 * Source : SELECT DISTINCT (13 cols) FROM legacy `dct_analysis` WHERE id >= sinceId.
 * Target : PG `empodat_stations` (auto-id sequence).
 *
 * Per LEGACY_MIGRATION_PLAN.md: insert all distinct station signatures as new
 * rows. No matching against existing PG stations — pre-existing
 * `empodat_station_merge_log` handles canonicalisation in a later pass.
 *
 * Column mapping (legacy -> PG):
 *   country, country_other, national_name, short_sample_code, sample_code,
 *   provider_code, code_ec_wise, code_ec_other, code_other, specific_locations
 *     -> same name (trim, empty -> NULL)
 *   station_name           -> name
 *   longitude_decimal      -> longitude (varchar -> double; ',' -> '.' fix)
 *   latitude_decimal       -> latitude  (same)
 *   country (ISO-2)        -> country_id (resolved via list_countries.code)
 *   country_other (ISO-2)  -> country_other_id (same)
 *   is_deprecated          -> false
 */
class ImportStationsStep extends Step
{
    private const TABLE = 'empodat_stations';

    public function name(): string
    {
        return 'Upsert stations (distinct dct_analysis -> empodat_stations)';
    }

    public function run(): void
    {
        $start = microtime(true);

        try {
            $distinctSigs = $this->legacy()
                ->table('dct_analysis')
                ->where('id', '>=', $this->sinceId)
                ->select([
                    'country', 'country_other', 'station_name', 'national_name',
                    'short_sample_code', 'sample_code', 'provider_code',
                    'code_ec_wise', 'code_ec_other', 'code_other',
                    'longitude_decimal', 'latitude_decimal', 'specific_locations',
                ])
                ->distinct()
                ->get();

            if ($distinctSigs->isEmpty()) {
                $this->note('No stations in delta.');
                $this->logBulkStep(self::TABLE, 0, null, null, (int) ((microtime(true) - $start) * 1000));

                return;
            }

            $countryIdByCode = $this->pg()
                ->table('list_countries')
                ->pluck('id', 'code')
                ->map(fn ($v) => (int) $v)
                ->all();

            $insertable = $distinctSigs->map(fn ($r) => [
                'name' => $this->trimToNull($r->station_name),
                'country' => $this->trimToNull($r->country),
                'country_other' => $this->trimToNull($r->country_other),
                'country_id' => $this->resolveCountryId($r->country, $countryIdByCode),
                'country_other_id' => $this->resolveCountryId($r->country_other, $countryIdByCode),
                'national_name' => $this->trimToNull($r->national_name),
                'short_sample_code' => $this->trimToNull($r->short_sample_code),
                'sample_code' => $this->trimToNull($r->sample_code),
                'provider_code' => $this->trimToNull($r->provider_code),
                'code_ec_wise' => $this->trimToNull($r->code_ec_wise),
                'code_ec_other' => $this->trimToNull($r->code_ec_other),
                'code_other' => $this->trimToNull($r->code_other),
                'specific_locations' => $this->trimToNull($r->specific_locations),
                'longitude' => $this->parseDecimal($r->longitude_decimal),
                'latitude' => $this->parseDecimal($r->latitude_decimal),
                'is_deprecated' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            $columns = array_keys($insertable[0]);
            $chunkSize = max(1, intdiv(65000, count($columns)));
            $idMin = null;
            $idMax = null;
            $insertedCount = 0;

            foreach (array_chunk($insertable, $chunkSize) as $chunk) {
                $ids = $this->insertReturningIds(self::TABLE, $chunk);
                if ($ids === []) {
                    continue;
                }
                $insertedCount += count($ids);
                $idMin = $idMin === null ? min($ids) : min($idMin, min($ids));
                $idMax = $idMax === null ? max($ids) : max($idMax, max($ids));
            }

            $this->note(sprintf(
                'Distinct sigs=%d  inserted=%d  id range=%s..%s',
                $distinctSigs->count(),
                $insertedCount,
                $idMin ?? 'n/a',
                $idMax ?? 'n/a',
            ));

            $this->logBulkStep(self::TABLE, $insertedCount, $idMin, $idMax, (int) ((microtime(true) - $start) * 1000));
        } catch (Throwable $e) {
            $this->logStepFailed(self::TABLE, $e);
            throw $e;
        }
    }

    /**
     * INSERT with RETURNING id; auto-generated PK.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, int>
     */
    private function insertReturningIds(string $table, array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map(fn ($c) => '"'.$c.'"', $columns));
        $valuesSql = [];
        $bindings = [];

        foreach ($rows as $row) {
            $placeholders = [];
            foreach ($columns as $col) {
                $placeholders[] = '?';
                $bindings[] = $row[$col] ?? null;
            }
            $valuesSql[] = '('.implode(', ', $placeholders).')';
        }

        $sql = sprintf(
            'INSERT INTO "%s" (%s) VALUES %s RETURNING "id"',
            $table,
            $columnList,
            implode(', ', $valuesSql),
        );

        $result = $this->pg()->select($sql, $bindings);

        return array_map(static fn ($r) => (int) $r->id, $result);
    }

    private function resolveCountryId(?string $code, array $countryIdByCode): ?int
    {
        $trimmed = $this->trimToNull($code);

        return $trimmed !== null ? ($countryIdByCode[$trimmed] ?? null) : null;
    }

    private function trimToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseDecimal(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }
        $cleaned = str_replace(',', '.', trim($value));
        if ($cleaned === '') {
            return null;
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
