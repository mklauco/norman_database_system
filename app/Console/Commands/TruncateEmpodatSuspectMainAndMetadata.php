<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Truncates empodat_suspect_main and empodat_suspect_metadata together, ready
 * for a full data reload.
 *
 * Both tables are PARTITION BY LIST (is_numeric_concentration). Once
 * fk_esmd_main exists (empodat_suspect_metadata (id, is_numeric_concentration)
 * -> empodat_suspect_main (id, is_numeric_concentration)), PostgreSQL requires
 * the referencing table to be truncated together with its parent in the same
 * TRUNCATE statement, which is why this command always issues exactly one
 * statement naming both tables rather than truncating them separately.
 */
class TruncateEmpodatSuspectMainAndMetadata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * The signature deliberately spells out its full blast radius (both
     * tables, by name) so it can never be invoked under a vaguer assumption
     * about what it clears.
     *
     * @var string
     */
    protected $signature = 'empodat-suspect:truncate-main-and-metadata
                            {--force : Skip the confirmation prompt and truncate immediately}
                            {--force-production : Required in production; still demands a typed confirmation phrase}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate empodat_suspect_main and empodat_suspect_metadata together, ready for a full reload';

    /**
     * The two tables this command truncates. Truncating the parent of a
     * PARTITION BY LIST table implicitly truncates all of its partitions in
     * the same statement, so the partitions themselves are never named here.
     *
     * @var list<string>
     */
    private const TARGET_TABLES = [
        'empodat_suspect_main',
        'empodat_suspect_metadata',
    ];

    /**
     * The two target tables plus every partition of either, used only for
     * the pre-flight foreign key guard below.
     *
     * @var list<string>
     */
    private const TARGET_TABLES_AND_PARTITIONS = [
        'empodat_suspect_main',
        'empodat_suspect_metadata',
        'empodat_suspect_main_numeric',
        'empodat_suspect_main_nonnumeric',
        'empodat_suspect_metadata_numeric',
        'empodat_suspect_metadata_nonnumeric',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        if (app()->isProduction() && ! $this->productionRunApproved()) {
            return self::FAILURE;
        }

        $this->info('Empodat Suspect - Truncate main + metadata');
        $this->newLine();

        $blockers = $this->findOutsideForeignKeys();

        if ($blockers !== []) {
            $this->error('Refusing to truncate: other tables still hold foreign keys into empodat_suspect_main / empodat_suspect_metadata.');
            $this->newLine();
            $this->table(['Constraint', 'Referencing table', 'Referenced table', 'Definition'], $blockers);
            $this->line('Resolve or drop these constraints first. Without CASCADE, TRUNCATE would simply fail here anyway (see below) - this check only turns that failure into a readable message before we try.');

            return self::FAILURE;
        }

        $this->table(['Table', 'Approx. rows'], [
            ['empodat_suspect_main', number_format($this->estimateRows('empodat_suspect_main'), 0, '.', ' ')],
            ['empodat_suspect_metadata', number_format($this->estimateRows('empodat_suspect_metadata'), 0, '.', ' ')],
        ]);

        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                'This will PERMANENTLY delete every row in both tables above. Continue?',
                false
            );

            if (! $confirmed) {
                $this->info('Aborted. No rows were deleted.');

                return self::SUCCESS;
            }
        }

        $this->warn('Truncating empodat_suspect_main and empodat_suspect_metadata...');

        /**
         * Both tables MUST be named in one statement - see class docblock.
         *
         * CASCADE is deliberately never added here. This is a safety
         * property, not a style choice: without CASCADE, PostgreSQL
         * physically cannot truncate a table that isn't named in this
         * statement. If some outside table held a foreign key into either
         * of these two, TRUNCATE raises an error and truncates nothing at
         * all - the pre-flight guard above exists only to make that failure
         * readable ahead of time. CASCADE would instead silently widen the
         * blast radius to include whatever that outside table is, which is
         * exactly the failure mode this command must never have.
         */
        DB::statement('TRUNCATE TABLE empodat_suspect_main, empodat_suspect_metadata RESTART IDENTITY');

        $this->info('Done. Both tables are now empty.');
        $this->line(
            'Note: RESTART IDENTITY only resets empodat_suspect_main_id_seq - verified to be the only '.
            'sequence owned by a column of either table (empodat_suspect_metadata.id is a plain BIGINT '.
            'by design, populated by the seeder rather than a SERIAL). No other sequence in the database is touched.'
        );

        return self::SUCCESS;
    }

    /**
     * Production requires two deliberate acts: the --force-production flag and
     * a typed confirmation phrase. Mirrors the "MIGRATE PRODUCTION" gate on the
     * Migrate Production DB workflow, so the muscle memory is the same.
     */
    private function productionRunApproved(): bool
    {
        $this->error('PRODUCTION ENVIRONMENT DETECTED.');
        $this->error('This permanently deletes every row from empodat_suspect_main and empodat_suspect_metadata.');

        if (! $this->option('force-production')) {
            $this->error('Refusing to run. Re-run with --force-production if this is genuinely intended.');

            return false;
        }

        $typed = (string) $this->ask('Type TRUNCATE PRODUCTION to confirm');

        if ($typed !== 'TRUNCATE PRODUCTION') {
            $this->error('Confirmation phrase did not match. Aborted. No rows were deleted.');

            return false;
        }

        return true;
    }

    /**
     * Planner row estimate for a partitioned table, summed over its partitions.
     *
     * Deliberately an estimate, not `count(*)`: these tables hold ~75M and ~39M
     * rows, so an exact count means a full sequential scan of both before the
     * confirmation prompt can even be shown - minutes of waiting to print a
     * number that is purely informational. `reltuples` is maintained by
     * ANALYZE/autovacuum and is read instantly from the catalog.
     */
    private function estimateRows(string $table): int
    {
        $row = DB::selectOne('
            SELECT COALESCE(SUM(c.reltuples), 0) AS estimate
            FROM pg_class c
            JOIN pg_inherits i ON i.inhrelid = c.oid
            WHERE i.inhparent = ?::regclass
        ', [$table]);

        $estimate = (int) ($row?->estimate ?? 0);

        if ($estimate > 0) {
            return $estimate;
        }

        // Not partitioned (or no partitions): fall back to the table's own estimate.
        $own = DB::selectOne('SELECT reltuples AS estimate FROM pg_class WHERE oid = ?::regclass', [$table]);

        return max(0, (int) ($own?->estimate ?? 0));
    }

    /**
     * Find foreign keys where a table other than the two being truncated
     * references empodat_suspect_main or empodat_suspect_metadata, directly
     * or via one of their partitions. Today this always returns empty; the
     * check exists to keep that guarantee true as the schema evolves.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function findOutsideForeignKeys(): array
    {
        $targetPlaceholders = implode(',', array_fill(0, count(self::TARGET_TABLES_AND_PARTITIONS), '?'));
        $ownTablePlaceholders = implode(',', array_fill(0, count(self::TARGET_TABLES), '?'));

        // conparentid = 0 keeps only the top-level constraint declaration.
        // A foreign key declared on a partitioned table (either side) is
        // cloned by Postgres onto every partition as its own pg_constraint
        // row - conrelid pointing at the partition, conparentid pointing
        // back at the parent constraint's oid (verified against the
        // existing fk_esm_file constraint: 1 row with conparentid = 0 plus
        // 2 partition-level clones). Without this filter, fk_esmd_main and
        // fk_esmd_file would each surface 2 extra partition-level rows whose
        // conrelid is a partition name rather than 'empodat_suspect_main' /
        // 'empodat_suspect_metadata', which would falsely trip this guard
        // on every run once those constraints exist.
        $rows = DB::select('
            SELECT
                con.conname,
                con.conrelid::regclass::text AS referencing_table,
                con.confrelid::regclass::text AS referenced_table,
                pg_get_constraintdef(con.oid) AS definition
            FROM pg_constraint con
            WHERE con.contype = \'f\'
              AND con.conparentid = 0
              AND con.confrelid::regclass::text IN ('.$targetPlaceholders.')
              AND con.conrelid::regclass::text NOT IN ('.$ownTablePlaceholders.')
            ORDER BY con.conname
        ', [...self::TARGET_TABLES_AND_PARTITIONS, ...self::TARGET_TABLES]);

        return array_map(
            static fn (object $row): array => [
                $row->conname,
                $row->referencing_table,
                $row->referenced_table,
                $row->definition,
            ],
            $rows
        );
    }
}
