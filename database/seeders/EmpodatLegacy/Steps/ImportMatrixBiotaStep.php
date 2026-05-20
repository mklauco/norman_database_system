<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use RuntimeException;
use Throwable;

/**
 * Phase 4b — bulk insert delta biota rows into `empodat_matrix_biota`.
 *
 * Source : legacy `dct_analysis_biota` WHERE id > $sinceId.
 * Target : PG `empodat_matrix_biota` (53 cols: original 36 + 17 from
 *          migration 2026_05_19_132538_add_terrestrial_biota_columns_*).
 *
 * Row alignment: 1:1 with `empodat_main` by id, but only the subset of main
 * rows that have a biota matrix. Delta = 1 054 477 rows (~half of 2 155 268
 * in main). Zero orphans verified across the delta.
 *
 * Mechanism: same COPY-based pattern as `ImportEmpodatMinorStep`. Per-chunk
 * `COPY FROM STDIN` into a TEMP staging table, then `INSERT … SELECT … FROM stg
 * ON CONFLICT (id) DO NOTHING` into the target. The staging-table indirection
 * preserves idempotency (COPY itself does not support ON CONFLICT). Each chunk
 * is wrapped in its own PG transaction.
 *
 * Type policy:
 *   - Legacy tinyint(1|2) FK-style ids -> PG smallint/bigint, cast to int.
 *   - Legacy int(11) ids (dord_id, dfam_id, dht_id, dspc_id, nutrition_condition)
 *     -> PG bigint/integer, cast to int.
 *   - All text/varchar -> PG character varying / text, verbatim.
 *
 * No FK constraints enforced yet — the lookup tables (data_kingdom, data_phylum,
 * data_class, data_order, data_family, data_species, data_individual_or_pooled,
 * data_category, data_habitat_type) live in legacy and are not migrated. Raw
 * integer ids are stored; constraints land in a follow-up migration once the
 * support tables are imported.
 */
class ImportMatrixBiotaStep extends Step
{
    private const TABLE = 'empodat_matrix_biota';

    private const CHUNK_SIZE = 10000;

    private const PROGRESS_EVERY_ROWS = 100000;

    /**
     * Legacy columns pulled from MariaDB, in the exact order produced by
     * mapRow() and consumed by COPY. 52 cols + id = 53 (mirrors PG_COLUMNS).
     *
     * @var list<string>
     */
    private const LEGACY_COLUMNS = [
        'id',
        'name', 'basin_name', 'km',
        'dpr_id', 'dsgr_id', 'dsgr_other',
        'species', 'species_name', 'species_alive',
        'dmeas_id', 'dmeas_other',
        'dtiel_id', 'dtiel_other',
        'biota_size', 'biota_length', 'biota_weight', 'biota_weight_dry',
        'biota_sex', 'biota_age', 'agegroup',
        'number_organisms', 'water_content', 'dry_wet', 'fat_content',
        'nutrition_condition', 'no_pooled_individuals',
        'standardised_protocols', 'time_freezing', 'storage_temperature',
        'packing_material', 'geographic_range',
        'was_species_alive', 'receive_medical_treatment', 'was_species_euthanised',
        'cause_death', 'year_death',
        'dki_id', 'dph_id', 'dcla_id', 'dord_id', 'dfam_id', 'dspc_id',
        'diop_id', 'dcat_id', 'dcat_other', 'dht_id', 'eunis_habitat_type',
        'head_length', 'bill_length', 'wing_length', 'tarsus_length', 'tarsus_width',
    ];

    /**
     * PG column order for COPY. Must match mapRow() positional output.
     *
     * @var list<string>
     */
    private const PG_COLUMNS = [
        'id',
        'name', 'basin_name', 'km',
        'dpr_id', 'dsgr_id', 'dsgr_other',
        'species', 'species_name', 'species_alive',
        'dmeas_id', 'dmeas_other',
        'dtiel_id', 'dtiel_other',
        'biota_size', 'biota_length', 'biota_weight', 'biota_weight_dry',
        'biota_sex', 'biota_age', 'agegroup',
        'number_organisms', 'water_content', 'dry_wet', 'fat_content',
        'nutrition_condition', 'no_pooled_individuals',
        'standardised_protocols', 'time_freezing', 'storage_temperature',
        'packing_material', 'geographic_range',
        'was_species_alive', 'receive_medical_treatment', 'was_species_euthanised',
        'cause_death', 'year_death',
        'dki_id', 'dph_id', 'dcla_id', 'dord_id', 'dfam_id', 'dspc_id',
        'diop_id', 'dcat_id', 'dcat_other', 'dht_id', 'eunis_habitat_type',
        'head_length', 'bill_length', 'wing_length', 'tarsus_length', 'tarsus_width',
    ];

    /** Native pgsql connection used only for COPY. Separate from Laravel's PDO. */
    private mixed $pgConn = null;

    public function name(): string
    {
        return 'Import biota metadata (dct_analysis_biota -> empodat_matrix_biota, COPY)';
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
                ->table('dct_analysis_biota')
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
                'CREATE TEMP TABLE stg_empodat_matrix_biota (LIKE "'.self::TABLE.'" INCLUDING DEFAULTS) ON COMMIT DROP'
            );

            $copyStart = pg_query($this->pgConn, 'COPY stg_empodat_matrix_biota ('.$colList.') FROM STDIN');
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
                'INSERT INTO "%s" (%s) SELECT %s FROM stg_empodat_matrix_biota ON CONFLICT ("id") DO NOTHING',
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
     * Map one legacy `dct_analysis_biota` row to a positional PG row.
     * The order MUST match PG_COLUMNS.
     *
     * @return list<int|float|string|null>
     */
    private function mapRow(object $r): array
    {
        return [
            (int) $r->id,
            $r->name,
            $r->basin_name,
            $r->km,
            $this->intOrNull($r->dpr_id),
            $this->intOrNull($r->dsgr_id),
            $r->dsgr_other,
            $r->species,
            $r->species_name,
            $r->species_alive,
            $this->intOrNull($r->dmeas_id),
            $r->dmeas_other,
            $this->intOrNull($r->dtiel_id),
            $r->dtiel_other,
            $r->biota_size,
            $r->biota_length,
            $r->biota_weight,
            $r->biota_weight_dry,
            $r->biota_sex,
            $r->biota_age,
            $r->agegroup,
            $r->number_organisms,
            $r->water_content,
            $r->dry_wet,
            $r->fat_content,
            $this->intOrNull($r->nutrition_condition),
            $r->no_pooled_individuals,
            $r->standardised_protocols,
            $r->time_freezing,
            $r->storage_temperature,
            $r->packing_material,
            $r->geographic_range,
            $r->was_species_alive,
            $r->receive_medical_treatment,
            $r->was_species_euthanised,
            $r->cause_death,
            $r->year_death,
            $this->intOrNull($r->dki_id),
            $this->intOrNull($r->dph_id),
            $this->intOrNull($r->dcla_id),
            $this->intOrNull($r->dord_id),
            $this->intOrNull($r->dfam_id),
            $this->intOrNull($r->dspc_id),
            $this->intOrNull($r->diop_id),
            $this->intOrNull($r->dcat_id),
            $r->dcat_other,
            $this->intOrNull($r->dht_id),
            $r->eunis_habitat_type,
            $r->head_length,
            $r->bill_length,
            $r->wing_length,
            $r->tarsus_length,
            $r->tarsus_width,
        ];
    }

    private function intOrNull(mixed $v): ?int
    {
        return $v !== null ? (int) $v : null;
    }
}
