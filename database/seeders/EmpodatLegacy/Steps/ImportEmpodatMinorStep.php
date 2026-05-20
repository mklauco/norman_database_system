<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use RuntimeException;
use Throwable;

/**
 * Phase 4a — bulk insert delta into `empodat_minor` (1:1 by id with empodat_main).
 *
 * Source : legacy `dct_analysis` WHERE id > $sinceId.
 * Target : PG `empodat_minor` (40 cols carrying date/aggregation/precision metadata
 *          not in empodat_main or empodat_stations).
 *
 * Mechanism: per-chunk `COPY FROM STDIN` into a TEMP staging table, then
 * `INSERT INTO empodat_minor (...) SELECT ... FROM stg ON CONFLICT (id) DO NOTHING`.
 * COPY bypasses PDO bind overhead entirely — ~5–10× faster than parameterised
 * INSERT for 41-col wide rows. The staging-table indirection preserves the
 * idempotency guarantee (re-runs skip already-inserted rows safely).
 *
 * Safety: each chunk runs in its own PG transaction. Mid-run crash leaves
 * committed chunks intact; the next run resumes via ON CONFLICT.
 *
 * No FK resolution needed — id-for-id verbatim copy. Most PG cols are varchar/text,
 * so zero-datetimes ('0000-00-00 00:00:00'), zero-filled tinyints ('00'), and year
 * sentinels ('0000') pass through unchanged.
 *
 * Dropped legacy cols (not in PG minor): `sampling_date_m`, `sampling_date_d`
 * (year already in empodat_main.sampling_date_year), `dtos_other`, `dplu_other`,
 * `east_west`, `north_south`, lat/lon dms components, `lod`, `lad`, station codes,
 * `id_method`, `id_data`, `dic_id`, `concentration_value`, `country*`, `matrix`,
 * `sus_id` (all in main/stations), `u_jds5`, `u_parc_pfas` (not modelled in v2).
 */
class ImportEmpodatMinorStep extends Step
{
    private const TABLE = 'empodat_minor';

    private const CHUNK_SIZE = 10000;

    private const PROGRESS_EVERY_ROWS = 100000;

    /**
     * Legacy columns we actually pull from MariaDB. Order must match
     * PG_COLUMNS (the COPY uses positional values, not bind names).
     *
     * @var list<string>
     */
    private const LEGACY_COLUMNS = [
        'id',
        'dpc_id', 'altitude', 'matrix_other', 'compound', 'dcod_id',
        'unit_extra', 'tier', 'sampling_technique',
        'sampling_date', 'sampling_date_t',
        'sampling_date1', 'sampling_date1_y', 'sampling_date1_m', 'sampling_date1_d', 'sampling_date1_t',
        'analysis_date_y', 'analysis_date_m', 'analysis_date_d',
        'sampling_duration_day', 'sampling_duration_hour',
        'description', 'remark', 'remark_add',
        'show_date', 'dtod_id', 'dtod_other',
        'agg_uncertainty', 'dmm_id',
        'agg_max', 'agg_min', 'agg_number', 'agg_deviation',
        'dtl_id', 'dtl_other',
        'dst_id', 'dst_other',
        'dtos_id', 'dplu_id', 'noexport', 'list_id',
    ];

    /**
     * PG column order for COPY. Must match the row-array order produced by mapRow().
     *
     * @var list<string>
     */
    private const PG_COLUMNS = [
        'id',
        'dpc_id', 'altitude', 'matrix_other', 'compound', 'dcod_id',
        'unit_extra', 'tier', 'sampling_technique',
        'sampling_date', 'sampling_date_t',
        'sampling_date1', 'sampling_date1_y', 'sampling_date1_m', 'sampling_date1_d', 'sampling_date1_t',
        'analysis_date_y', 'analysis_date_m', 'analysis_date_d',
        'sampling_duration_day', 'sampling_duration_hour',
        'description', 'remark', 'remark_add',
        'show_date', 'dtod_id', 'dtod_other',
        'agg_uncertainty', 'dmm_id',
        'agg_max', 'agg_min', 'agg_number', 'agg_deviation',
        'dtl_id', 'dtl_other',
        'dst_id', 'dst_other',
        'dtos_id', 'dplu_id', 'noexport', 'list_id',
    ];

    /** Native pgsql connection used only for COPY. Separate from Laravel's PDO. */
    private mixed $pgConn = null;

    public function name(): string
    {
        return 'Import empodat_minor (dct_analysis -> empodat_minor, COPY)';
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
                ->table('dct_analysis')
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
     * @param  list<list<int|float|string|null>>  $payload  rows as positional arrays
     *                                                      matching PG_COLUMNS order
     * @return int number of rows actually inserted (excludes ON CONFLICT skips)
     */
    private function bulkCopyChunk(array $payload): int
    {
        $colList = implode(', ', array_map(static fn ($c) => '"'.$c.'"', self::PG_COLUMNS));

        $this->pgQueryOrFail('BEGIN');

        try {
            $this->pgQueryOrFail(
                'CREATE TEMP TABLE stg_empodat_minor (LIKE "'.self::TABLE.'" INCLUDING DEFAULTS) ON COMMIT DROP'
            );

            $copyStart = pg_query($this->pgConn, 'COPY stg_empodat_minor ('.$colList.') FROM STDIN');
            if ($copyStart === false) {
                throw new RuntimeException('COPY FROM STDIN failed to start: '.pg_last_error($this->pgConn));
            }

            foreach ($payload as $row) {
                $line = $this->encodeCopyLine($row);
                if (pg_put_line($this->pgConn, $line) === false) {
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
                'INSERT INTO "%s" (%s) SELECT %s FROM stg_empodat_minor ON CONFLICT ("id") DO NOTHING',
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

    /**
     * Open a parallel native pgsql connection using the same params as the
     * Laravel default PDO connection. Separate connection is required because
     * PHP's PDO/pgsql driver does not expose the COPY sub-protocol.
     */
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
     * Encode one row as a PG text-format COPY line. PG_COLUMNS-positional.
     * Escapes the four control chars that PG text format reserves.
     *
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
     * Map one legacy `dct_analysis` row to a positional PG `empodat_minor` row.
     * The order MUST match PG_COLUMNS.
     *
     * @return list<int|float|string|null>
     */
    private function mapRow(object $r): array
    {
        return [
            (int) $r->id,
            (int) $r->dpc_id,
            (string) $r->altitude,
            (string) $r->matrix_other,
            $r->compound !== null ? (int) $r->compound : null,
            (int) $r->dcod_id,
            (string) ($r->unit_extra ?? ''),
            (int) $r->tier,
            (int) $r->sampling_technique,
            (string) $r->sampling_date,
            (string) $r->sampling_date_t,
            (string) $r->sampling_date1,
            (string) $r->sampling_date1_y,
            (string) $r->sampling_date1_m,
            (string) $r->sampling_date1_d,
            (string) $r->sampling_date1_t,
            (string) $r->analysis_date_y,
            (string) $r->analysis_date_m,
            (string) $r->analysis_date_d,
            (string) $r->sampling_duration_day,
            (string) $r->sampling_duration_hour,
            (string) $r->description,
            (string) $r->remark,
            (string) $r->remark_add,
            (string) $r->show_date,
            (int) $r->dtod_id,
            (string) $r->dtod_other,
            (string) $r->agg_uncertainty,
            (int) $r->dmm_id,
            (string) $r->agg_max,
            (string) $r->agg_min,
            (string) $r->agg_number,
            (string) $r->agg_deviation,
            $r->dtl_id !== null ? (int) $r->dtl_id : null,
            (string) $r->dtl_other,
            (int) $r->dst_id,
            (string) ($r->dst_other ?? ''),
            (int) $r->dtos_id,
            $r->dplu_id !== null ? (int) $r->dplu_id : null,
            (int) $r->noexport,
            (int) $r->list_id,
        ];
    }
}
