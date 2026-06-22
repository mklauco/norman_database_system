<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use RuntimeException;
use Throwable;

/**
 * Phase 4e — bulk insert delta surface-water rows into `empodat_matrix_water_surface`.
 *
 * Source : legacy `dct_analysis_water_surface` WHERE id > $sinceId.
 * Target : PG `empodat_matrix_water_surface` (52 cols, after migration
 *          2026_06_22_093808_add_legacy_chemistry_columns_to_empodat_matrix_water_surface
 *          which adds the 22 cols the original v1->v2 pgloader pass omitted:
 *          ocean_sea_region_name, flow, sampled_volume, the N/P/C chemistry,
 *          and the cation/anion list).
 *
 * Row alignment: 1:1 with `empodat_main` by id, but only the subset of main
 * rows whose matrix resolves to a water-surface family (matrix_id IN
 * 2..8). All cols on PG side are nullable; values that are NOT NULL in
 * legacy with empty strings as the default land as '' verbatim.
 *
 * Mechanism: same COPY-based pattern as `ImportEmpodatMinorStep`,
 * `ImportMatrixBiotaStep`, `ImportMatrixSedimentsStep`, and
 * `ImportMatrixSoilStep`. Per-chunk `COPY FROM STDIN` into a TEMP staging
 * table, then `INSERT … SELECT … FROM stg ON CONFLICT (id) DO NOTHING`.
 */
class ImportMatrixWaterSurfaceStep extends Step
{
    private const TABLE = 'empodat_matrix_water_surface';

    private const CHUNK_SIZE = 10000;

    private const PROGRESS_EVERY_ROWS = 50000;

    /**
     * Legacy columns pulled from MariaDB, in the exact order produced by
     * mapRow() and consumed by COPY. 51 cols + id = 52 (mirrors PG_COLUMNS).
     *
     * @var list<string>
     */
    private const LEGACY_COLUMNS = [
        'id',
        'df_id', 'df_other',
        'name', 'basin_name', 'ocean_sea_region_name', 'km',
        'dpr_id', 'dpr_other',
        'de_id', 'de_other',
        'depth_m', 'surface',
        'salinity_min', 'salinity_mean', 'salinity_max',
        'spm', 'ph', 'temperature',
        'conductivity', 'doc',
        'carbon', 'hardness',
        'horizon', 'doc_mg_cl', 'o2_m', 'o2_p',
        'dcat_id', 'dcat_other',
        'dtbu_id',
        'p_total',
        'flow', 'sampled_volume',
        'p_po4', 'n_no2', 'n_no3', 'n_total',
        'alkalinity', 'nh4', 'dissolved_o2', 'cod',
        'so42', 'hco3', 'toc', 'cl', 'po43',
        'calcium', 'iron', 'magnesium', 'manganese',
        'chlorides', 'sulfates',
    ];

    /**
     * PG column order for COPY. Must match mapRow() positional output.
     *
     * @var list<string>
     */
    private const PG_COLUMNS = [
        'id',
        'df_id', 'df_other',
        'name', 'basin_name', 'ocean_sea_region_name', 'km',
        'dpr_id', 'dpr_other',
        'de_id', 'de_other',
        'depth_m', 'surface',
        'salinity_min', 'salinity_mean', 'salinity_max',
        'spm', 'ph', 'temperature',
        'conductivity', 'doc',
        'carbon', 'hardness',
        'horizon', 'doc_mg_cl', 'o2_m', 'o2_p',
        'dcat_id', 'dcat_other',
        'dtbu_id',
        'p_total',
        'flow', 'sampled_volume',
        'p_po4', 'n_no2', 'n_no3', 'n_total',
        'alkalinity', 'nh4', 'dissolved_o2', 'cod',
        'so42', 'hco3', 'toc', 'cl', 'po43',
        'calcium', 'iron', 'magnesium', 'manganese',
        'chlorides', 'sulfates',
    ];

    /** Native pgsql connection used only for COPY. Separate from Laravel's PDO. */
    private mixed $pgConn = null;

    public function name(): string
    {
        return 'Import surface-water metadata (dct_analysis_water_surface -> empodat_matrix_water_surface, COPY)';
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
                ->table('dct_analysis_water_surface')
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
     * COPY one chunk into a transient staging table, then INSERT...SELECT...
     * ON CONFLICT DO NOTHING into the target.
     *
     * @param  list<list<int|float|string|null>>  $payload
     */
    private function bulkCopyChunk(array $payload): int
    {
        $colList = implode(', ', array_map(static fn ($c) => '"'.$c.'"', self::PG_COLUMNS));

        $this->pgQueryOrFail('BEGIN');

        try {
            $this->pgQueryOrFail(
                'CREATE TEMP TABLE stg_empodat_matrix_water_surface (LIKE "'.self::TABLE.'" INCLUDING DEFAULTS) ON COMMIT DROP'
            );

            $copyStart = pg_query($this->pgConn, 'COPY stg_empodat_matrix_water_surface ('.$colList.') FROM STDIN');
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
                'INSERT INTO "%s" (%s) SELECT %s FROM stg_empodat_matrix_water_surface ON CONFLICT ("id") DO NOTHING',
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
     * Map one legacy `dct_analysis_water_surface` row to a positional PG row.
     * The order MUST match PG_COLUMNS.
     *
     * @return list<int|float|string|null>
     */
    private function mapRow(object $r): array
    {
        return [
            (int) $r->id,
            $this->intOrNull($r->df_id),
            $r->df_other,
            $r->name,
            $r->basin_name,
            $r->ocean_sea_region_name,
            $r->km,
            $this->intOrNull($r->dpr_id),
            $r->dpr_other,
            $this->intOrNull($r->de_id),
            $r->de_other,
            $r->depth_m,
            $r->surface,
            $r->salinity_min,
            $r->salinity_mean,
            $r->salinity_max,
            $r->spm,
            $r->ph,
            $r->temperature,
            $r->conductivity,
            $r->doc,
            $r->carbon,
            $r->hardness,
            $r->horizon,
            $r->doc_mg_cl,
            $r->o2_m,
            $r->o2_p,
            $this->intOrNull($r->dcat_id),
            $r->dcat_other,
            $this->intOrNull($r->dtbu_id),
            $r->p_total,
            $r->flow,
            $r->sampled_volume,
            $r->p_po4,
            $r->n_no2,
            $r->n_no3,
            $r->n_total,
            $r->alkalinity,
            $r->nh4,
            $r->dissolved_o2,
            $r->cod,
            $r->so42,
            $r->hco3,
            $r->toc,
            $r->cl,
            $r->po43,
            $r->calcium,
            $r->iron,
            $r->magnesium,
            $r->manganese,
            $r->chlorides,
            $r->sulfates,
        ];
    }

    private function intOrNull(mixed $v): ?int
    {
        return $v !== null ? (int) $v : null;
    }
}
