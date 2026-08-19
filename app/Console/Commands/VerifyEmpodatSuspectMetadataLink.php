<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verifies that empodat_suspect_metadata is soundly linked to
 * empodat_suspect_main after a reload.
 *
 * The bulk seeders run under `SET session_replication_role = replica`,
 * which suppresses foreign key triggers - so Postgres will mark
 * fk_esmd_main VALID without ever having actually checked a single loaded
 * row. This command is the real safety net: it re-derives, by query, the
 * same guarantees the (skipped) FK triggers would otherwise have enforced.
 */
class VerifyEmpodatSuspectMetadataLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'empodat-suspect:verify-metadata-link
                            {--deep : Also run the full anti-join checks for orphaned metadata rows and file_id consistency (expensive at 75M/39M rows; forces single-threaded joins)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify empodat_suspect_metadata is soundly linked to empodat_suspect_main after a reload';

    /**
     * @var list<string>
     */
    private const EXPECTED_CONSTRAINTS = ['fk_esmd_main', 'fk_esmd_file'];

    private const EXPECTED_INDEX = 'idx_esmd_file_id';

    /**
     * Files 10001-10008 predate the metadata table and never populate it;
     * used only to label the per-file report, never to assert anything.
     *
     * @var list<int>
     */
    private const LEGACY_FILE_IDS = [10001, 10002, 10003, 10004, 10005, 10006, 10007, 10008];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        $this->info('Empodat Suspect - Verify metadata <-> main link');
        $this->newLine();

        if (! $this->metadataFileIdColumnExists()) {
            $this->error('empodat_suspect_metadata.file_id does not exist - this schema predates the metadata link.');
            $this->line('Run `php artisan migrate` first, then re-run this command.');

            return self::FAILURE;
        }

        $failures = [];
        $failures = [...$failures, ...$this->checkConstraintsAndIndex()];
        $failures = [...$failures, ...$this->checkNullFileIds()];

        $this->reportPerFileCounts();

        if ($this->option('deep')) {
            $failures = [...$failures, ...$this->checkDeepAntiJoin()];
        } else {
            $this->newLine();
            $this->warn('Skipped: orphan-row and file_id-consistency checks need --deep. Constraint/index presence and NULL file_id were still checked above.');
        }

        $this->newLine();

        if ($failures !== []) {
            $this->error(count($failures).' check(s) FAILED:');
            foreach ($failures as $failure) {
                $this->line('  - '.$failure);
            }

            return self::FAILURE;
        }

        $this->info($this->option('deep')
            ? 'PASSED - all checks, including the --deep anti-join checks, succeeded.'
            : 'PASSED - cheap checks succeeded. Run with --deep for the full orphan-row / file_id guarantee.');

        return self::SUCCESS;
    }

    /**
     * Guard against being run on a pre-migration schema. Without this, every
     * later check would fault on a column that does not exist yet and report
     * a raw SQL error instead of an actionable message.
     */
    private function metadataFileIdColumnExists(): bool
    {
        return DB::select("
            SELECT 1
            FROM information_schema.columns
            WHERE table_name = 'empodat_suspect_metadata' AND column_name = 'file_id'
        ") !== [];
    }

    /**
     * Hard failure: fk_esmd_main, fk_esmd_file and idx_esmd_file_id must all
     * exist. A missing index here means a reload forgot to rebuild it.
     *
     * @return list<string>
     */
    private function checkConstraintsAndIndex(): array
    {
        $this->info('-> Checking constraints and index...');

        $failures = [];

        $placeholders = implode(',', array_fill(0, count(self::EXPECTED_CONSTRAINTS), '?'));
        $foundConstraints = collect(DB::select(
            "SELECT DISTINCT conname FROM pg_constraint WHERE contype = 'f' AND conname IN ({$placeholders})",
            self::EXPECTED_CONSTRAINTS
        ))->pluck('conname')->all();

        foreach (self::EXPECTED_CONSTRAINTS as $expected) {
            if (! in_array($expected, $foundConstraints, true)) {
                $failures[] = "Missing foreign key constraint: {$expected}";
            }
        }

        $indexExists = DB::select('SELECT 1 FROM pg_indexes WHERE indexname = ?', [self::EXPECTED_INDEX]) !== [];

        if (! $indexExists) {
            $failures[] = 'Missing index: '.self::EXPECTED_INDEX;
        }

        if ($failures === []) {
            $this->info('   OK - fk_esmd_main, fk_esmd_file and idx_esmd_file_id are all present.');
        } else {
            foreach ($failures as $failure) {
                $this->error('   MISSING - '.$failure);
            }
        }

        return $failures;
    }

    /**
     * Hard failure: no metadata row may have a NULL file_id. A single-table
     * scan with no join, so this is cheap enough to run in default mode.
     *
     * @return list<string>
     */
    private function checkNullFileIds(): array
    {
        $this->info('-> Checking for NULL metadata.file_id...');

        $nullCount = (int) DB::table('empodat_suspect_metadata')->whereNull('file_id')->count();

        if ($nullCount > 0) {
            $this->error('   FOUND - '.number_format($nullCount, 0, '.', ' ').' metadata row(s) with a NULL file_id.');

            return ["{$nullCount} empodat_suspect_metadata row(s) have a NULL file_id"];
        }

        $this->info('   OK - no metadata row has a NULL file_id.');

        return [];
    }

    /**
     * REPORTED, NEVER ASSERTED. The foreign key is child -> parent
     * (metadata -> main), so a main row is never required to have a
     * metadata row - partial coverage (currently ~48% overall, and 0% for
     * 8 of the 15 source files) is legal at any ratio and must never fail
     * this command. Uses one GROUP BY file_id per table - no join - so this
     * runs in seconds even at 75M/39M rows.
     */
    private function reportPerFileCounts(): void
    {
        $this->newLine();
        $this->info('-> Per-file row counts (informational only)...');

        $main = $this->countsByFileId('empodat_suspect_main');
        $metadata = $this->countsByFileId('empodat_suspect_metadata');

        $mainByFile = $main['counts'];
        $metadataByFile = $metadata['counts'];
        $nullSubstanceByFile = $main['null_substance'];

        $fileIds = collect(array_keys($mainByFile))
            ->merge(array_keys($metadataByFile))
            ->unique()
            ->sort()
            ->values();

        $fileNames = DB::table('files')->whereIn('id', $fileIds)->pluck('name', 'id');

        $rows = $fileIds->map(function (int $fileId) use ($mainByFile, $metadataByFile, $nullSubstanceByFile, $fileNames) {
            $main = $mainByFile[$fileId] ?? 0;
            $metadata = $metadataByFile[$fileId] ?? 0;
            $nullSubstance = $nullSubstanceByFile[$fileId] ?? 0;
            $coverage = $main > 0 ? number_format($metadata / $main * 100, 1, '.', ' ').'%' : 'n/a';
            $nullSubstancePct = $main > 0 ? number_format($nullSubstance / $main * 100, 3, '.', ' ').'%' : 'n/a';
            $era = in_array($fileId, self::LEGACY_FILE_IDS, true) ? 'legacy' : 'current';

            return [
                $fileId,
                substr((string) ($fileNames[$fileId] ?? '(unknown file)'), 0, 38),
                $era,
                number_format($main, 0, '.', ' '),
                number_format($metadata, 0, '.', ' '),
                $coverage,
                number_format($nullSubstance, 0, '.', ' '),
                $nullSubstancePct,
            ];
        })->all();

        // Rows with no file_id get their own line so the report still
        // accounts for every row in both tables. A non-zero metadata count
        // here is a symptom of checkNullFileIds() already having failed the
        // run; a non-zero main count is legal but worth seeing.
        $nullMain = $main['nulls'];
        $nullMetadata = $metadata['nulls'];

        if ($nullMain > 0 || $nullMetadata > 0) {
            $rows[] = [
                '(none)', '', '',
                number_format($nullMain, 0, '.', ' '),
                number_format($nullMetadata, 0, '.', ' '),
                'n/a',
                number_format($main['null_substance_no_file'], 0, '.', ' '),
                'n/a',
            ];
        }

        $this->table(
            ['File ID', 'File Name', 'Era', 'Main Rows', 'Metadata Rows', 'Coverage', 'NULL substance_id', '%'],
            $rows
        );

        $totalNullSubstance = array_sum($nullSubstanceByFile) + $main['null_substance_no_file'];
        $this->line('Total main rows with NULL substance_id: '.number_format($totalNullSubstance, 0, '.', ' '));

        $this->line('Coverage is informational only and is never a failure condition - 0% to 100% are all legal.');
        $this->line('Legacy files (10001-10008) are expected to show 0 metadata rows; BlackSea/TerraChem files (10009-10015) carry HRMS metadata.');
        $this->line('NULL substance_id is informational too. A small residue on legacy files is expected: one NORMAN code has no');
        $this->line('entry in empodat_suspect_susdat_code_mappings, so it cannot resolve. A legacy file back at ~2.85% instead of');
        $this->line('~0.001% means the crosswalk table was empty at import time - re-import that file (see PR #90).');
        $this->line('If every file shows a suspiciously uniform row count (e.g. ~10 000 everywhere), a seeder row cap was likely left enabled.');
    }

    /**
     * One pass over the table, counting per file_id and picking up the NULL
     * file_id group at the same time. Deliberately not filtered to
     * `file_id IS NOT NULL` with a separate NULL count afterwards: that would
     * scan empodat_suspect_main (75M rows, and no file_id index on the
     * nonnumeric partition) twice instead of once.
     *
     * Only `empodat_suspect_main` has a `substance_id` column, so the NULL
     * substance tally is collected in the same pass for that table and comes
     * back as zeroes for `empodat_suspect_metadata`. Folding it in here keeps
     * this to one scan of the 75M-row table rather than two.
     *
     * @return array{counts: array<int, int>, nulls: int, null_substance: array<int, int>, null_substance_no_file: int}
     */
    private function countsByFileId(string $table): array
    {
        $nullSubstanceExpr = $table === 'empodat_suspect_main'
            ? 'count(*) FILTER (WHERE substance_id IS NULL)'
            : '0';

        $rows = DB::select("
            SELECT file_id,
                   count(*) AS row_count,
                   {$nullSubstanceExpr} AS null_substance
            FROM {$table}
            GROUP BY file_id
        ");

        $counts = [];
        $nullSubstance = [];
        $nulls = 0;
        $nullSubstanceNoFile = 0;

        foreach ($rows as $row) {
            if ($row->file_id === null) {
                $nulls = (int) $row->row_count;
                $nullSubstanceNoFile = (int) $row->null_substance;

                continue;
            }

            $counts[(int) $row->file_id] = (int) $row->row_count;
            $nullSubstance[(int) $row->file_id] = (int) $row->null_substance;
        }

        return [
            'counts' => $counts,
            'nulls' => $nulls,
            'null_substance' => $nullSubstance,
            'null_substance_no_file' => $nullSubstanceNoFile,
        ];
    }

    /**
     * Hard failures: run only with --deep.
     *
     * - Every metadata row must match a main row on (id, is_numeric_concentration).
     * - Where matched, metadata.file_id must equal that main row's file_id.
     *
     * Both are answered by a single LEFT JOIN pass (two FILTER'd aggregates)
     * rather than two separate anti-joins, halving the I/O this incurs at
     * 75M/39M rows.
     *
     * @return list<string>
     */
    private function checkDeepAntiJoin(): array
    {
        $this->newLine();
        $this->info('-> Running deep anti-join checks (orphaned metadata + file_id consistency)...');
        $this->line('   Scans all of empodat_suspect_metadata against empodat_suspect_main - can take a while at 75M/39M rows.');

        $failures = [];
        $start = microtime(true);

        /**
         * Force single-threaded joins for this query only.
         *
         * A parallel hash join across 39M metadata / 75M main rows makes
         * Postgres try to build its shared hash table in /dev/shm. On this
         * machine the Postgres container's /dev/shm is far smaller than a
         * parallel hash join assumes, and a naive parallel version of this
         * exact query has already failed here with:
         *   "could not resize shared memory segment ... to 1073741824
         *    bytes: No space left on device"
         * Setting max_parallel_workers_per_gather = 0 keeps the join
         * single-process and avoids /dev/shm entirely. Do not "optimise"
         * this back to parallel without first giving the container more
         * /dev/shm.
         */
        DB::statement('SET max_parallel_workers_per_gather = 0');

        try {
            $result = DB::selectOne('
                SELECT
                    count(*) FILTER (WHERE m.id IS NULL) AS orphan_count,
                    count(*) FILTER (WHERE m.id IS NOT NULL AND m.file_id IS DISTINCT FROM md.file_id) AS mismatch_count
                FROM empodat_suspect_metadata md
                LEFT JOIN empodat_suspect_main m
                    ON m.id = md.id
                   AND m.is_numeric_concentration = md.is_numeric_concentration
            ');
        } finally {
            DB::statement('RESET max_parallel_workers_per_gather');
        }

        $duration = round(microtime(true) - $start, 1);
        $orphanCount = (int) ($result?->orphan_count ?? 0);
        $mismatchCount = (int) ($result?->mismatch_count ?? 0);

        if ($orphanCount > 0) {
            $this->error('   FOUND - '.number_format($orphanCount, 0, '.', ' ').' metadata row(s) with no matching main row on (id, is_numeric_concentration).');
            $this->showSample(
                'Sample orphaned metadata rows',
                'SELECT md.id, md.is_numeric_concentration, md.file_id
                 FROM empodat_suspect_metadata md
                 WHERE NOT EXISTS (
                     SELECT 1 FROM empodat_suspect_main m
                     WHERE m.id = md.id AND m.is_numeric_concentration = md.is_numeric_concentration
                 )
                 LIMIT 5'
            );
            $failures[] = "{$orphanCount} empodat_suspect_metadata row(s) have no matching empodat_suspect_main row on (id, is_numeric_concentration)";
        } else {
            $this->info('   OK - every metadata row matches a main row on (id, is_numeric_concentration).');
        }

        if ($mismatchCount > 0) {
            $this->error('   FOUND - '.number_format($mismatchCount, 0, '.', ' ').' metadata row(s) whose file_id disagrees with their main row.');
            $this->showSample(
                'Sample file_id mismatches',
                'SELECT md.id, md.is_numeric_concentration, md.file_id AS metadata_file_id, m.file_id AS main_file_id
                 FROM empodat_suspect_metadata md
                 JOIN empodat_suspect_main m
                     ON m.id = md.id AND m.is_numeric_concentration = md.is_numeric_concentration
                 WHERE m.file_id IS DISTINCT FROM md.file_id
                 LIMIT 5'
            );
            $failures[] = "{$mismatchCount} empodat_suspect_metadata row(s) have a file_id that disagrees with their empodat_suspect_main row";
        } else {
            $this->info('   OK - every matched metadata row agrees with its main row on file_id.');
        }

        $this->line("   (deep checks completed in {$duration}s)");

        return $failures;
    }

    /**
     * Run a small, already-known-to-match query and print it as a table,
     * to make a hard failure above actionable instead of just a count.
     */
    private function showSample(string $label, string $query): void
    {
        $rows = DB::select($query);

        if ($rows === []) {
            return;
        }

        $this->line("   {$label}:");
        $this->table(
            array_keys((array) $rows[0]),
            array_map(static fn (object $row): array => array_values((array) $row), $rows)
        );
    }
}
