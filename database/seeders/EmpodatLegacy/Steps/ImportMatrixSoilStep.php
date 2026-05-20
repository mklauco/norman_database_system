<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use RuntimeException;
use Throwable;

/**
 * Phase 4d — bulk insert delta soil rows into `empodat_matrix_soil`.
 *
 * Source : legacy `dct_analysis_soil` WHERE id > $sinceId (203 271 rows
 *          in the current delta, id 161 948 791…162 152 061).
 * Target : PG `empodat_matrix_soil` (36 cols, after migration
 *          2026_05_20_110210_add_legacy_terrestrial_columns_to_empodat_matrix_soil
 *          which adds 7 missing cols: eunis_habitat_type,
 *          no_pooled_sub_samples, sample_wet_weight, sample_dry_weight,
 *          water_content, fat_content, storage_temperature).
 *
 * Row alignment: 1:1 with `empodat_main` by id, but only the subset of main
 * rows whose matrix resolves to a soil family.
 *
 * Mechanism: same COPY-based pattern as `ImportEmpodatMinorStep`,
 * `ImportMatrixBiotaStep`, and `ImportMatrixSedimentsStep`. Per-chunk
 * `COPY FROM STDIN` into a TEMP staging table, then
 * `INSERT … SELECT … FROM stg ON CONFLICT (id) DO NOTHING`.
 *
 * Prerequisite: migration `2026_05_20_110210_*` must be applied — otherwise
 * COPY fails when staging the 7 new columns.
 */
class ImportMatrixSoilStep extends Step
{
    private const TABLE = 'empodat_matrix_soil';

    private const CHUNK_SIZE = 10000;

    private const PROGRESS_EVERY_ROWS = 50000;

    /**
     * Legacy columns pulled from MariaDB, in the exact order produced by
     * mapRow() and consumed by COPY. 35 cols + id = 36 (mirrors PG_COLUMNS).
     *
     * @var list<string>
     */
    private const LEGACY_COLUMNS = [
        'id',
        'basin_name', 'de_id', 'de_other', 'depth_m', 'ph', 'carbon',
        'wider_area', 'dry_wet', 'soil_type', 'soil_texture',
        'dps_id', 'dgra_id', 'dgra_other', 'dilution_factor', 'km',
        'dsot_id', 'dsot_other', 'dcnps_id',
        'dcat_id', 'dcat_other', 'dtbu_id', 'dtbu_other',
        'bulk_density', 'organic_carbon_content', 'ph_cacl2', 'ph_h2o',
        'dpr_id', 'dpr_other',
        // Added by migration 2026_05_20_110210
        'eunis_habitat_type', 'no_pooled_sub_samples',
        'sample_wet_weight', 'sample_dry_weight',
        'water_content', 'fat_content', 'storage_temperature',
    ];

    /**
     * PG column order for COPY. Must match mapRow() positional output.
     *
     * @var list<string>
     */
    private const PG_COLUMNS = [
        'id',
        'basin_name', 'de_id', 'de_other', 'depth_m', 'ph', 'carbon',
        'wider_area', 'dry_wet', 'soil_type', 'soil_texture',
        'dps_id', 'dgra_id', 'dgra_other', 'dilution_factor', 'km',
        'dsot_id', 'dsot_other', 'dcnps_id',
        'dcat_id', 'dcat_other', 'dtbu_id', 'dtbu_other',
        'bulk_density', 'organic_carbon_content', 'ph_cacl2', 'ph_h2o',
        'dpr_id', 'dpr_other',
        'eunis_habitat_type', 'no_pooled_sub_samples',
        'sample_wet_weight', 'sample_dry_weight',
        'water_content', 'fat_content', 'storage_temperature',
    ];

    /** Native pgsql connection used only for COPY. Separate from Laravel's PDO. */
    private mixed $pgConn = null;

    public function name(): string
    {
        return 'Import soil metadata (dct_analysis_soil -> empodat_matrix_soil, COPY)';
    }

    public function run(): void
    {
        $start = microtime(true);

        if ($this->previouslyCompleted(self::TABLE)) {
            $this->note('Already completed for this since_id; skipping (run rollback to re-run).');
            $this->logBulkStep(self::TABLE, 0, null, null, (int) ((microtime(true) - $start) * 1000));

            return;
        }

        $this->pgConn = $this->openPgsqlConn();
        @pg_query($this->pgConn, 'SET synchronous_commit = off');

        try {
            $totalInserted = 0;
            $idMin = null;
            $idMax = null;
            $nextProgressAt = self::PROGRESS_EVERY_ROWS;

            $this->legacy()
                ->table('dct_analysis_soil')
                ->select(self::LEGACY_COLUMNS)
                ->where('id', '>', $this->sinceId)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use (
                    &$totalInserted,
                    &$idMin,
                    &$idMax,
                    &$nextProgressAt,
                    $start,
                ): void {
                    if ($rows->isEmpty()) {
                        return;
                    }

                    $chunkIdMin = PHP_INT_MAX;
                    $chunkIdMax = PHP_INT_MIN;
                    $payload = [];
                    foreach ($rows as $r) {
                        $row = $this->mapRow($r);
                        $payload[] = $row;
                        $chunkIdMin = min($chunkIdMin, $row[0]);
                        $chunkIdMax = max($chunkIdMax, $row[0]);
                    }

                    $inserted = $this->bulkCopyChunk($payload);
                    if ($inserted > 0) {
                        $totalInserted += $inserted;
                        $idMin = $idMin === null ? $chunkIdMin : min($idMin, $chunkIdMin);
                        $idMax = $idMax === null ? $chunkIdMax : max($idMax, $chunkIdMax);
                    }

                    if ($totalInserted >= $nextProgressAt) {
                        $elapsed = microtime(true) - $start;
                        $rate = $totalInserted / max(0.001, $elapsed);
                        $this->note(sprintf(
                            '  ... %d rows inserted in %.1fs (%.0f rows/s), last id=%d',
                            $totalInserted,
                            $elapsed,
                            $rate,
                            $idMax ?? 0,
                        ));
                        $nextProgressAt = $totalInserted + self::PROGRESS_EVERY_ROWS;
                    }
                }, 'id');

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
        } finally {
            if ($this->pgConn !== null && $this->pgConn !== false) {
                @pg_close($this->pgConn);
                $this->pgConn = null;
            }
        }
    }

    /**
     * @param  list<list<int|float|string|null>>  $payload
     */
    private function bulkCopyChunk(array $payload): int
    {
        $colList = implode(', ', array_map(static fn ($c) => '"'.$c.'"', self::PG_COLUMNS));

        $this->pgQueryOrFail('BEGIN');

        try {
            $this->pgQueryOrFail(
                'CREATE TEMP TABLE stg_empodat_matrix_soil (LIKE "'.self::TABLE.'" INCLUDING DEFAULTS) ON COMMIT DROP'
            );

            $copyStart = pg_query($this->pgConn, 'COPY stg_empodat_matrix_soil ('.$colList.') FROM STDIN');
            if ($copyStart === false) {
                throw new RuntimeException('COPY FROM STDIN failed to start: '.pg_last_error($this->pgConn));
            }

            foreach ($payload as $row) {
                if (pg_put_line($this->pgConn, $this->encodeCopyLine($row)) === false) {
                    throw new RuntimeException('pg_put_line failed: '.pg_last_error($this->pgConn));
                }
            }
            if (pg_put_line($this->pgConn, "\\.\n") === false) {
                throw new RuntimeException('pg_put_line terminator failed: '.pg_last_error($this->pgConn));
            }
            if (pg_end_copy($this->pgConn) === false) {
                throw new RuntimeException('pg_end_copy failed: '.pg_last_error($this->pgConn));
            }

            $insertResult = pg_query($this->pgConn, sprintf(
                'INSERT INTO "%s" (%s) SELECT %s FROM stg_empodat_matrix_soil ON CONFLICT ("id") DO NOTHING',
                self::TABLE,
                $colList,
                $colList,
            ));
            if ($insertResult === false) {
                throw new RuntimeException('INSERT from staging failed: '.pg_last_error($this->pgConn));
            }
            $affected = pg_affected_rows($insertResult);

            $this->pgQueryOrFail('COMMIT');

            return $affected;
        } catch (Throwable $e) {
            @pg_query($this->pgConn, 'ROLLBACK');
            throw $e;
        }
    }

    private function pgQueryOrFail(string $sql): void
    {
        $result = pg_query($this->pgConn, $sql);
        if ($result === false) {
            throw new RuntimeException('pg_query failed ['.$sql.']: '.pg_last_error($this->pgConn));
        }
    }

    private function openPgsqlConn(): mixed
    {
        $cfg = $this->pg()->getConfig();
        $dsn = sprintf(
            'host=%s port=%s dbname=%s user=%s password=%s connect_timeout=10 client_encoding=UTF8',
            $cfg['host'],
            $cfg['port'],
            $cfg['database'],
            $cfg['username'],
            $cfg['password'] ?? '',
        );
        $conn = @pg_connect($dsn);
        if ($conn === false) {
            throw new RuntimeException('pg_connect to target failed (host='.$cfg['host'].' port='.$cfg['port'].' db='.$cfg['database'].')');
        }

        return $conn;
    }

    /**
     * @param  list<int|float|string|null>  $row
     */
    private function encodeCopyLine(array $row): string
    {
        $parts = [];
        foreach ($row as $v) {
            if ($v === null) {
                $parts[] = '\\N';

                continue;
            }
            $s = is_string($v) ? $v : (string) $v;
            $parts[] = strtr($s, [
                '\\' => '\\\\',
                "\t" => '\\t',
                "\n" => '\\n',
                "\r" => '\\r',
            ]);
        }

        return implode("\t", $parts)."\n";
    }

    /**
     * @return list<int|float|string|null>
     */
    private function mapRow(object $r): array
    {
        return [
            (int) $r->id,
            $r->basin_name,
            $this->intOrNull($r->de_id),
            $r->de_other,
            $r->depth_m,
            $r->ph,
            $r->carbon,
            $r->wider_area,
            $r->dry_wet,
            $r->soil_type,
            $r->soil_texture,
            $this->intOrNull($r->dps_id),
            $this->intOrNull($r->dgra_id),
            $r->dgra_other,
            $r->dilution_factor,
            $r->km,
            $this->intOrNull($r->dsot_id),
            $r->dsot_other,
            $this->intOrNull($r->dcnps_id),
            $this->intOrNull($r->dcat_id),
            $r->dcat_other,
            $this->intOrNull($r->dtbu_id),
            $r->dtbu_other,
            $this->floatOrNull($r->bulk_density),
            $this->floatOrNull($r->organic_carbon_content),
            $this->floatOrNull($r->ph_cacl2),
            $this->floatOrNull($r->ph_h2o),
            $this->intOrNull($r->dpr_id),
            $r->dpr_other,
            $r->eunis_habitat_type,
            $r->no_pooled_sub_samples,
            $r->sample_wet_weight,
            $r->sample_dry_weight,
            $r->water_content,
            $r->fat_content,
            $r->storage_temperature,
        ];
    }

    private function intOrNull(mixed $v): ?int
    {
        return $v !== null ? (int) $v : null;
    }

    private function floatOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        return is_numeric($v) ? (float) $v : null;
    }
}
