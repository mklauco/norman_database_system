<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSequences extends Command
{
    protected $signature = 'db:fix-sequences';

    protected $description = 'Reset every PostgreSQL auto-increment sequence to the current MAX(id), excluding hand-assigned reserved id blocks';

    /**
     * Tables containing hand-assigned ("reserved") id blocks that must be
     * excluded when recalculating the sequence value.
     *
     * Each entry maps a table name to an id threshold: ids strictly below
     * the threshold are genuine auto-increment allocations, while ids at or
     * above it were inserted with an explicit id (e.g. via a seeder) and
     * never advanced the PostgreSQL sequence. For these tables the sequence
     * must be recalculated from `MAX(id) WHERE id < threshold`, never the
     * global `MAX(id)`.
     *
     * `files`: ids 9000-9003 (Literature/TerraChem seed, database_entity_id
     * = 17) and 10001-10015 (EMPODAT Suspect seed, database_entity_id = 18)
     * are inserted via `File::updateOrCreate(['id' => 100xx], ...)` in the
     * seeders under database/seeders/EmpodatSuspect/. Genuine uploads
     * through the web UI (app/Http/Controllers/Backend/FileController.php
     * ::store()) occupy ids below 9000.
     *
     * To reserve a range on another table, add an entry here — no other
     * code in this class needs to change.
     *
     * @var array<string, int>
     */
    private const RESERVED_ID_THRESHOLDS = [
        'files' => 9000,
    ];

    public function handle(): int
    {
        $tables = DB::select("
            SELECT table_name
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND column_name = 'id'
              AND column_default LIKE 'nextval%'
            ORDER BY table_name
        ");

        if (empty($tables)) {
            $this->info('No tables with auto-increment id columns found.');

            return self::SUCCESS;
        }

        $fixed = 0;
        $hadFailure = false;

        foreach ($tables as $table) {
            $name = $table->table_name;

            $result = array_key_exists($name, self::RESERVED_ID_THRESHOLDS)
                ? $this->fixReservedRangeTable($name, self::RESERVED_ID_THRESHOLDS[$name])
                : $this->fixStandardTable($name);

            if ($result === true) {
                $fixed++;
            } elseif ($result === false) {
                $hadFailure = true;
            }
        }

        $this->newLine();
        $this->info("Done. Fixed {$fixed} sequence(s).");

        return $hadFailure ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Reset a plain table's sequence to the global MAX(id).
     *
     * @return bool|null true on success, false on failure, null when
     *                   skipped for a benign reason (empty table or no
     *                   sequence).
     */
    private function fixStandardTable(string $name): ?bool
    {
        $maxId = DB::selectOne("SELECT MAX(id) AS max_id FROM \"{$name}\"")->max_id;

        if ($maxId === null) {
            $this->line("  {$name}: empty table, skipped");

            return null;
        }

        $sequence = $this->resolveSequence($name);

        if ($sequence === null) {
            return null;
        }

        DB::statement("SELECT setval('{$sequence}', {$maxId})");
        $this->info("  {$name}: sequence reset to {$maxId}");

        return true;
    }

    /**
     * Reset a reserved-range table's sequence to MAX(id) below the reserved
     * threshold, ignoring hand-assigned ids at or above it.
     *
     * @return bool|null true on success, false on a refused/failed fix,
     *                   null when skipped for a benign reason (no rows
     *                   below the threshold, or no sequence).
     */
    private function fixReservedRangeTable(string $name, int $threshold): ?bool
    {
        $globalMax = DB::selectOne("SELECT MAX(id) AS max_id FROM \"{$name}\"")->max_id;

        $safeMax = DB::selectOne(
            "SELECT MAX(id) AS max_id FROM \"{$name}\" WHERE id < ?",
            [$threshold]
        )->max_id;

        if ($safeMax === null) {
            $this->warn("  {$name}: no rows below reserved threshold {$threshold} (global max is {$globalMax}), skipped");

            return null;
        }

        // The WHERE clause above guarantees no row can exist strictly
        // between $safeMax and $threshold, but this command mutates a
        // production sequence, so verify that invariant explicitly rather
        // than trust it. If it ever fails, setval-ing to $safeMax could
        // later hand out an id that already exists between the two, so
        // refuse instead of proceeding.
        $conflicts = (int) DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM "'.$name.'" WHERE id > ? AND id < ?',
            [$safeMax, $threshold]
        )->cnt;

        if ($conflicts !== 0) {
            $this->error("  {$name}: found {$conflicts} row(s) between computed max {$safeMax} and reserved threshold {$threshold}; refusing to change the sequence.");

            return false;
        }

        $sequence = $this->resolveSequence($name);

        if ($sequence === null) {
            return null;
        }

        DB::statement("SELECT setval('{$sequence}', {$safeMax})");
        $this->info("  {$name}: global max is {$globalMax}, reserved ids >= {$threshold} excluded, sequence reset to {$safeMax}");

        return true;
    }

    /**
     * Resolve the sequence backing a table's `id` column, warning and
     * returning null when none is found.
     */
    private function resolveSequence(string $name): ?string
    {
        $sequence = DB::selectOne("SELECT pg_get_serial_sequence('{$name}', 'id') AS seq")->seq;

        if ($sequence === null) {
            $this->warn("  {$name}: no sequence found, skipped");

            return null;
        }

        return $sequence;
    }
}
