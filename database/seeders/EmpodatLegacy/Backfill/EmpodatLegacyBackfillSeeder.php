<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Copies EMPODAT rows that exist in the legacy MariaDB but are missing in the
 * NDS PostgreSQL. Insert-only, diff-driven (no hard-coded id ranges), safe to
 * re-run (a second run inserts nothing). Never updates or deletes an existing
 * NDS row. Dry run is the default.
 *
 * Modes (LEGACY_BACKFILL_MODE): preflight | dry-run (default) | execute | verify.
 * Writing needs ALL of: mode=execute, LEGACY_BACKFILL_EXECUTE=true and
 * LEGACY_BACKFILL_EXPECT_PG_DATABASE equal to the live current_database().
 *
 * Limits:
 *   - LEGACY_BACKFILL_ID_FROM / LEGACY_BACKFILL_ID_TO restrict EVERY mode,
 *     execute included, to an id window. Stage a real run with it: a small
 *     window first, verify, then widen.
 *   - A dry run reads and simulates at most LEGACY_BACKFILL_DRY_RUN_MAX_ROWS
 *     missing ids per table (default 50 000; 0 = no limit). It runs the exact
 *     execute pipeline (same reads, same conversion, same station matching,
 *     same file_id) and only replaces the final INSERT with a count. It prints
 *     LEGACY_BACKFILL_SAMPLE_ROWS example rows per table as they would be
 *     written.
 *
 * Order: files, data sources (+organisations, laboratories), analytical
 * methods, list_matrices, stations + empodat_main (stations reused by
 * signature, file_id computed before insert), empodat_minor, empodat_matrix_*.
 * One transaction and one audit row per chunk, so
 * `php artisan empodat-legacy:rollback {run_id}` undoes exactly this run.
 *
 * empodat_minor.sampling_date_m / sampling_date_d are NOT written (left NULL):
 * migration 2026_09_09_090000 reserves them for an external migrator.
 *
 * Run:
 *   php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Backfill\EmpodatLegacyBackfillSeeder' --force
 */
class EmpodatLegacyBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $options = BackfillOptions::fromEnvironment((array) config('empodat_legacy.backfill', []));

        ini_set('memory_limit', $options->memoryLimit);
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();
        DB::connection('legacy_empodat')->disableQueryLog();

        $report = new PlanReport($options->mode, false);
        $ctx = new BackfillContext($options, DB::connection('legacy_empodat'), DB::connection(), $report, $this->command);
        $specs = array_values(array_filter(
            BackfillTables::all($options->columnAliases),
            static fn (TableSpec $spec): bool => $options->includesTable($spec->pgTable),
        ));
        $preflight = new Preflight($ctx, $specs);

        $ctx->command?->warn($report->banner());
        $caught = null;

        try {
            $ctx->heading('Read-only locks (before anything else is read)');
            $ctx->pgSessionBeforeLock = $ctx->pg->selectOne(
                "SELECT current_setting('transaction_read_only') AS ro, current_setting('default_transaction_read_only') AS dro, pg_is_in_recovery() AS recovery"
            );
            $ctx->emit('legacy MariaDB: '.ReadOnlyGuard::lock(DB::connection('legacy_empodat')));
            if ($options->mode === BackfillOptions::MODE_EXECUTE) {
                $ctx->emit('PostgreSQL: NOT locked (execute mode; writes still need the execution guard below)');
            } else {
                $ctx->emit('PostgreSQL: '.ReadOnlyGuard::lock(DB::connection()));
            }

            $ctx->heading('Mode, limits and target');
            $identity = $preflight->pgIdentity();
            $ctx->emit('mode='.$options->mode.'  bucket_size='.$options->bucketSize.'  chunk_rows='.$options->chunkRows.'  scan_window='.$options->scanWindow);
            $ctx->emit('id window: '.$options->windowLabel());
            if ($options->mode === BackfillOptions::MODE_DRY_RUN) {
                $ctx->emit($options->dryRunMaxRows > 0
                    ? sprintf('dry-run limit: at most %d missing ids per table are read and simulated; totals beyond that are estimates from bucket counts', $options->dryRunMaxRows)
                    : 'dry-run limit: NONE (LEGACY_BACKFILL_DRY_RUN_MAX_ROWS=0), reads as much legacy data as the real run');
            }
            $ctx->emit(sprintf('PG target: database=%s user=%s host=%s addr=%s port=%s', $identity['database'], $identity['user'], $identity['host'] ?: '-', $identity['addr'] ?? '-', $identity['port'] ?? '-'));

            $guardIdentity = ['database' => $identity['database'], 'host' => $identity['host'], 'addr' => $identity['addr']];
            $writes = ExecutionGuard::writesAllowed($options, $guardIdentity);
            if (ExecutionGuard::mustAbort($options, $guardIdentity)) {
                throw new RuntimeException('Refusing to write: '.implode('; ', ExecutionGuard::violations($options, $guardIdentity)));
            }
            if ($writes) {
                $ctx->emit('Execution guard: PASSED (execute flag set, target database matches LEGACY_BACKFILL_EXPECT_PG_DATABASE).');
                if ($options->closeStaleRunId !== null) {
                    $closed = Audit::closeStaleRun($ctx->pg, $options->closeStaleRunId);
                    $ctx->emit('Closed stale BACKFILL run #'.$options->closeStaleRunId.': '.$closed.' row(s) updated.');
                }
            } else {
                $ctx->emit('Execution guard: writes are OFF ('.(implode('; ', ExecutionGuard::violations($options, $guardIdentity)) ?: 'n/a').').');
            }

            $ctx->heading('Preflight');
            $preflight->run($writes);
            $report->set('preflight_sizes', $preflight->sizes());

            match ($options->mode) {
                BackfillOptions::MODE_PREFLIGHT => $ctx->emit('Preflight only: nothing else was read or written.'),
                BackfillOptions::MODE_DRY_RUN => $this->dryRun($ctx, $specs, $preflight->sizes()),
                BackfillOptions::MODE_VERIFY => $this->verify($ctx, $specs),
                default => $this->execute($ctx, $specs),
            };

            if ($options->mode === BackfillOptions::MODE_DRY_RUN) {
                $this->printTodo($ctx, false);
            }
        } catch (Throwable $e) {
            $caught = $e;
            $ctx->emit('ABORTED: '.$e->getMessage());
            if ($ctx->audit !== null) {
                $ctx->emit('Run #'.$ctx->audit->runId.' is marked failed. Inspect with: php artisan empodat-legacy:rollback '.$ctx->audit->runId.' --dry-run');
            }
        } finally {
            [$txt, $json] = $report->write($options->reportDirectory);
            $ctx->command?->info('Report written to '.$txt);
            $ctx->command?->info('Machine-readable report: '.$json);
            $ctx->command?->warn($report->banner());
        }

        if ($caught !== null) {
            throw $caught;
        }
    }

    /**
     * Read-only rehearsal of execute(): same steps, same code, no INSERT.
     *
     * @param  list<TableSpec>  $specs
     * @param  array<string, array{bytes: int, rows: int}>  $sizes
     */
    private function dryRun(BackfillContext $ctx, array $specs, array $sizes): void
    {
        $gaps = new GapFinder($ctx);
        $cap = $ctx->options->rowCap();

        $ctx->heading('Gap detection: dct_analysis vs empodat_main');
        $main = $gaps->missing('dct_analysis', 'empodat_main', $cap);
        $this->printGap($ctx, 'empodat_main', $main);
        $ctx->report->set('main_gap', $main);

        $ctx->heading('Reading what the missing legacy rows reference (read-only)');
        $scan = LegacyMainScan::run($ctx, $main['runs']);
        $ctx->emit(sprintf(
            '%d legacy rows scanned%s: %d distinct SUSDAT codes, %d methods, %d data sources, %d matrices, %d countries',
            $scan['rows'],
            $main['complete'] ? '' : ' (dry-run sample only)',
            count($scan['sus']),
            count($scan['method']),
            count($scan['dataSource']),
            count($scan['matrix']),
            count($scan['country']),
        ));

        $ctx->heading('1. files (dct_list -> files)');
        $filesBackfill = new FilesBackfill($ctx);
        $files = $filesBackfill->plan();
        $ctx->emit(sprintf('legacy list rows in scope: %d; already in PG: %d; to insert: %d', $files['legacyRows'], $files['present'], count($files['toInsert'])));
        foreach ($files['toInsertTitles'] as $id => $title) {
            $ctx->emit(sprintf('  would insert files.id=%d  %s', $id, $title));
        }
        foreach ($files['conflicts'] as $conflict) {
            $ctx->emit(sprintf('  CONFLICT: files.id=%d exists in PG with database_entity_id=%s (not empodat); NOT inserted, NOT touched, never used as file_id', $conflict['id'], $conflict['entity'] ?? 'NULL'));
        }
        $ctx->emit('files metadata that differs from legacy (REPORT ONLY, nothing is updated): '.count($files['mismatches']));
        foreach (array_slice($files['mismatches'], 0, 60) as $m) {
            $ctx->emit(sprintf('  files.id=%d %s: PG=%s legacy=%s', $m['id'], $m['column'], var_export($m['pg'], true), var_export($m['legacy'], true)));
        }
        $ctx->report->set('files', $files);

        $ctx->heading('2-4. Lookups referenced by PG empodat_main or by the rows to copy');
        $lookups = (new LookupBackfill($ctx))->plan($scan);
        foreach (['methods' => 'empodat_analytical_methods', 'dataSources' => 'empodat_data_sources', 'matrices' => 'list_matrices'] as $key => $table) {
            $l = $lookups[$key];
            $ctx->emit(sprintf('%s: %d referenced, %d missing in PG, %d exist in legacy (would be inserted), %d missing in legacy too (%d rows to copy reference them)', $table, $l['candidates'], $l['missingInPg'], count($l['toInsert']), count($l['unresolvable']), $l['unresolvableRows']));
            if ($l['toInsert'] !== []) {
                $ctx->emit('  would insert ids: '.implode(', ', array_slice($l['toInsert'], 0, 40)).(count($l['toInsert']) > 40 ? ', ...' : ''));
            }
            if ($l['unresolvable'] !== []) {
                $ctx->emit('  not in legacy either: '.implode(', ', array_slice($l['unresolvable'], 0, 40)).(count($l['unresolvable']) > 40 ? ', ...' : ''));
            }
        }
        $o = $lookups['organisations'];
        $ctx->emit(sprintf('data source organisations: %d referenced by data sources to insert; reused by legacy id %d, reused by unique (name, acronym, city) %d, new rows %d', $o['requested'], $o['byLegacyId'], $o['bySignature'], $o['new']));
        $lab = $lookups['laboratories'];
        $ctx->emit(sprintf('data source laboratories: %d references; reused %d, new rows %d (PG has %d laboratory signatures shared by several rows; lowest id reused)', $lab['references'], $lab['matched'], $lab['new'], $lab['ambiguousInPg']));
        if (! $main['complete']) {
            $ctx->emit('  (lookups of the rows to copy cover the dry-run sample only; lookups referenced by existing PG rows are complete)');
        }
        $ctx->report->set('lookups', $lookups);

        $ctx->heading('5. Stations and empodat_main (execute pipeline, no INSERT)');
        $availableFiles = $filesBackfill->availableFileIds($files['toInsert']);
        $mainStats = (new MainBackfill($ctx))->run($main['runs'], $availableFiles, false);
        $this->printMainStats($ctx, $mainStats, false);
        $ctx->emit('estimated added size of empodat_main: '.$this->estimate($sizes, 'empodat_main', $main['estimatedMissing']));
        $ctx->report->set('main', $mainStats);

        $ctx->heading('6. empodat_minor and empodat_matrix_* (execute pipeline, no INSERT)');
        $sidecar = new SidecarBackfill($ctx, $gaps);
        $sidecarStats = [];
        foreach ($specs as $spec) {
            $stats = $sidecar->run($spec, false);
            $sidecarStats[$spec->pgTable] = $stats;
            $this->printSidecarStats($ctx, $stats, false);
            $ctx->emit('    estimated added size: '.$this->estimate($sizes, $spec->pgTable, $stats['gap']['estimatedMissing']));
        }
        $ctx->report->set('sidecars', $sidecarStats);

        $ctx->heading('Summary');
        $ctx->emit($main['complete']
            ? 'DRY RUN COMPLETE over every missing id in scope. Nothing was written to PostgreSQL or to MariaDB.'
            : 'DRY RUN COMPLETE over a SAMPLE (dry-run limit). Nothing was written to PostgreSQL or to MariaDB. Counts marked "estimated" come from bucket counts.');
        $ctx->emit('To write: set LEGACY_BACKFILL_MODE=execute, LEGACY_BACKFILL_EXECUTE=true and LEGACY_BACKFILL_EXPECT_PG_DATABASE=<database name>; keep the id window to stage the run.');
    }

    /**
     * @param  list<TableSpec>  $specs
     */
    private function execute(BackfillContext $ctx, array $specs): void
    {
        $audit = Audit::start($ctx->pg, sprintf(
            'execute window=%s bucket=%d chunk=%d',
            $ctx->options->windowLabel(),
            $ctx->options->bucketSize,
            $ctx->options->chunkRows,
        ));
        $ctx->audit = $audit;
        $ctx->report->setWrites(true);
        $ctx->command?->warn($ctx->report->banner());

        $ctx->heading('EXECUTE run #'.$audit->runId.' ('.$ctx->options->windowLabel().')');
        $ctx->emit('Roll back this run with: php artisan empodat-legacy:rollback '.$audit->runId.' --dry-run   (then without --dry-run)');

        try {
            $gaps = new GapFinder($ctx);

            $ctx->heading('Gap detection: dct_analysis vs empodat_main');
            $main = $gaps->missing('dct_analysis', 'empodat_main');
            $this->printGap($ctx, 'empodat_main', $main);

            $ctx->heading('1. files');
            $filesBackfill = new FilesBackfill($ctx);
            $insertedFiles = $filesBackfill->execute();
            $ctx->emit('files inserted: '.count($insertedFiles).' ('.implode(', ', $insertedFiles).')');

            $ctx->heading('2-4. data sources, analytical methods, list_matrices');
            $scan = LegacyMainScan::run($ctx, $main['runs']);
            (new LookupBackfill($ctx))->execute($scan);

            $ctx->heading('5. stations and empodat_main');
            $mainStats = (new MainBackfill($ctx))->run($main['runs'], $filesBackfill->availableFileIds([]), true);
            $this->printMainStats($ctx, $mainStats, true);
            $ctx->report->set('main_stats', $mainStats);

            $ctx->heading('6. empodat_minor and empodat_matrix_*');
            $sidecar = new SidecarBackfill($ctx, $gaps);
            $sidecarStats = [];
            foreach ($specs as $spec) {
                $stats = $sidecar->run($spec, true);
                $sidecarStats[$spec->pgTable] = $stats;
                $this->printSidecarStats($ctx, $stats, true);
            }
            $ctx->report->set('sidecar_stats', $sidecarStats);

            $audit->complete();
            $ctx->emit('Run #'.$audit->runId.' completed.');
        } catch (Throwable $e) {
            $audit->fail($e);
            throw $e;
        }

        $this->verify($ctx, $specs);
        $this->printTodo($ctx, true);
    }

    /**
     * Fresh bucket diff after (or without) a run: what is still missing.
     *
     * @param  list<TableSpec>  $specs
     */
    private function verify(BackfillContext $ctx, array $specs): void
    {
        $ctx->heading('Verification: bucket diff re-run, what is still missing ('.$ctx->options->windowLabel().')');
        $gaps = new GapFinder($ctx);
        $main = $gaps->missing('dct_analysis', 'empodat_main');
        $this->printGap($ctx, 'empodat_main', $main);
        $ctx->report->set('verify_main', $main);

        $left = [];
        foreach ($specs as $spec) {
            $gap = $gaps->missing($spec->legacyTable, $spec->pgTable);
            $left[$spec->pgTable] = $gap;
            $this->printGap($ctx, $spec->pgTable, $gap);
        }
        $ctx->report->set('verify_sidecars', $left);

        $files = (new FilesBackfill($ctx))->plan();
        $ctx->emit('files still missing: '.(implode(', ', $files['toInsert']) ?: 'none'));
        $ctx->emit('Leftovers in the side tables are expected only for legacy ids that have no empodat_main row (see "skipped" in the execute output).');
    }

    /**
     * @param  array{runs: list<array{0: int, 1: int, 2: int}>, missing: int, estimatedMissing: int, complete: bool, legacyRows: int, targetRows: int, differing: int}  $gap
     */
    private function printGap(BackfillContext $ctx, string $label, array $gap): void
    {
        $ctx->emit(sprintf(
            '%s: legacy %d rows, PG %d rows, %d differing buckets, %s ids missing%s',
            $label,
            $gap['legacyRows'],
            $gap['targetRows'],
            $gap['differing'],
            $gap['complete'] ? (string) $gap['missing'] : 'about '.$gap['estimatedMissing'],
            $gap['complete'] ? ' in '.count($gap['runs']).' runs' : sprintf(' (estimated; the dry-run sample takes the first %d)', $gap['missing']),
        ));
        $ctx->emit('  '.($gap['complete'] ? 'runs' : 'sampled runs').': '.IdRanges::format($gap['runs'], 30));
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function printMainStats(BackfillContext $ctx, array $stats, bool $written): void
    {
        $ctx->emit(sprintf(
            'empodat_main: %d legacy rows read, %d %s%s',
            $stats['rowsRead'],
            $written ? $stats['inserted'] : $stats['wouldInsert'],
            $written ? 'inserted' : 'would be inserted',
            $stats['alreadyInPg'] > 0 ? sprintf(', %d already in PG (skipped)', $stats['alreadyInPg']) : '',
        ));
        $ctx->emit(sprintf(
            'stations: %d rows reuse an existing PG station; %d new stations %s',
            $stats['rowsWithExistingStation'],
            $stats['newStations'],
            $written ? 'inserted' : 'would be inserted',
        ));
        $ctx->emit(sprintf('substance_id NULL (no susdat_substances.code): %d codes, %d rows %s', count($stats['noSubstance']), array_sum($stats['noSubstance']), json_encode($stats['noSubstance'])));
        if ($stats['noCountry'] !== []) {
            $ctx->emit('country_id NULL (code unknown to list_countries): '.json_encode($stats['noCountry']));
        }
        $ctx->emit(sprintf('file_id: %d rows outside every legacy file range (NULL); rows per file: %s', $stats['noFile'], json_encode($stats['files'])));
        $this->printSamples($ctx, 'empodat_main', array_map(
            static fn (array $s): array => ['# '.$s['legacy'].' | station: '.$s['station'] => null] + $s['values'],
            $stats['samples'],
        ));
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function printSidecarStats(BackfillContext $ctx, array $stats, bool $written): void
    {
        $gap = $stats['gap'];
        $ctx->emit(sprintf(
            '%s <- %s: legacy %d rows, PG %d rows, %s missing; read %d, %s %d (main exists: %d, main added by this run: %d); skipped, no main row anywhere: %d',
            $stats['pgTable'],
            $stats['legacyTable'],
            $gap['legacyRows'],
            $gap['targetRows'],
            $gap['complete'] ? (string) $gap['missing'] : 'about '.$gap['estimatedMissing'].' (sampled '.$gap['missing'].')',
            $stats['rowsRead'],
            $written ? 'inserted' : 'would insert',
            $written ? $stats['inserted'] : $stats['wouldInsert'],
            $stats['eligibleMainExists'],
            $stats['eligibleMainAddedByRun'],
            $stats['skippedNoMain'],
        ));
        $ctx->emit('    '.($gap['complete'] ? 'missing runs' : 'sampled runs').': '.IdRanges::format($gap['runs'], 12));
        if ($stats['nullFilledColumns'] !== []) {
            $ctx->emit('    NULL-filled (no legacy source): '.implode(', ', $stats['nullFilledColumns']));
        }
        if ($stats['nulledValues'] !== []) {
            $ctx->emit('    legacy values that cannot be stored and become NULL (e.g. text in a numeric column), per column: '.json_encode($stats['nulledValues']));
        }
        $this->printSamples($ctx, $stats['pgTable'], $stats['samples']);
    }

    /**
     * Prints example rows exactly as they would be written (column=value).
     *
     * @param  list<array<string, string|null>>  $samples
     */
    private function printSamples(BackfillContext $ctx, string $table, array $samples): void
    {
        if ($samples === []) {
            return;
        }

        $ctx->emit(sprintf('    %d example row(s) of %s as they would be written:', count($samples), $table));
        foreach ($samples as $sample) {
            $parts = [];
            foreach ($sample as $column => $value) {
                $parts[] = $value === null && str_starts_with($column, '# ') ? $column : $column.'='.($value ?? 'NULL');
            }
            $ctx->emit('      '.implode('  ', $parts));
        }
    }

    /**
     * @param  array<string, array{bytes: int, rows: int}>  $sizes
     */
    private function estimate(array $sizes, string $table, int $rows): string
    {
        $size = $sizes[$table] ?? null;
        if ($size === null || $size['rows'] <= 0) {
            return 'n/a';
        }
        $bytes = (int) round($size['bytes'] / $size['rows'] * $rows);

        return sprintf('%.1f GB (table plus indexes, from current average row size)', $bytes / 1024 ** 3);
    }

    private function printTodo(BackfillContext $ctx, bool $afterExecute): void
    {
        $ctx->heading($afterExecute ? 'POST-RUN TODO (printed, NOT executed)' : 'POST-RUN TODO after a real run (printed, NOT executed)');
        foreach ([
            '1. php artisan db:fix-sequences                       (files, empodat_data_sources, empodat_main, stations, organisation and laboratory sequences; ids were inserted explicitly)',
            '2. psql: VACUUM (ANALYZE) on empodat_main, empodat_minor, empodat_stations, empodat_matrix_*, files, empodat_data_sources, empodat_analytical_methods, list_matrices, list_data_source_organisations, list_data_source_laboratories',
            '3. php artisan empodat:generate-statistics            (public + admin EMPODAT statistics; queued, add --sync to run inline)',
            '4. php artisan factsheets:generate-statistics         (factsheet statistics read empodat_main)',
            '5. php artisan empodat-suspect:refresh-filters --create, THEN php artisan empodat-suspect:refresh-matrix-metadata --create   (materialized views join empodat_main / empodat_matrix_*; filters first, always with --create)',
            '6. php artisan empodat-suspect:refresh-prioritisation  (materialized view reads the latest empodat_main row per station)',
            '7. Admin UI file rescan for the new files rows (number_of_records stays 0; rescan also rewrites main_id_from/to, so do it deliberately)',
            '8. Review: stations created by this run may duplicate legacy-canonical stations (empodat-stations:merge-duplicates / empodat_station_merge_log is the later canonicalisation pass)',
        ] as $line) {
            $ctx->emit($line);
        }
    }
}
