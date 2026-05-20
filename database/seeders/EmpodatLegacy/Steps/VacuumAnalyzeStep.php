<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Phase 5b — `VACUUM ANALYZE` on every table the delta touched.
 *
 * Refreshes pg_statistic for the planner (2 M+ new rows in empodat_main alters
 * row-count estimates significantly) and reclaims dead tuples from Phase 5a's
 * UPDATEs. Not VACUUM FULL — non-blocking, no exclusive lock, safe to run on
 * a live system.
 *
 * VACUUM cannot run inside an explicit transaction. Each `VACUUM ANALYZE` is
 * dispatched as its own statement via Laravel's PDO connection, which does not
 * wrap single statements in a transaction by default.
 *
 * Re-running is harmless (idempotent in effect — VACUUM just walks the heap
 * again and finds nothing to clean), so the `previouslyCompleted` guard is
 * present for cheap repeat-run short-circuiting only.
 */
class VacuumAnalyzeStep extends Step
{
    private const LOG_TABLE = 'empodat_main_vacuum';

    /**
     * Tables to VACUUM ANALYZE, in roughly descending heap size so the longest
     * operation kicks off first. Order does not otherwise matter.
     *
     * @var list<string>
     */
    private const TARGETS = [
        'empodat_main',
        'empodat_minor',
        'empodat_matrix_biota',
        'empodat_stations',
        'empodat_analytical_methods',
        'empodat_data_sources',
        'list_data_source_organisations',
        'list_matrices',
        'files',
    ];

    public function name(): string
    {
        return 'VACUUM ANALYZE on touched tables';
    }

    public function run(): void
    {
        $start = microtime(true);

        if ($this->previouslyCompleted(self::LOG_TABLE)) {
            $this->note('Already completed for this since_id; skipping (run rollback to re-run).');
            $this->logBulkStep(self::LOG_TABLE, 0, null, null, (int) ((microtime(true) - $start) * 1000));

            return;
        }

        try {
            foreach (self::TARGETS as $table) {
                $tStart = microtime(true);
                DB::statement(sprintf('VACUUM ANALYZE "%s"', $table));
                $this->note(sprintf(
                    '   VACUUM ANALYZE %s — %.2fs',
                    $table,
                    microtime(true) - $tStart,
                ));
            }

            $this->note(sprintf('All targets vacuumed in %.2fs total', microtime(true) - $start));

            $this->logBulkStep(self::LOG_TABLE, 0, null, null, (int) ((microtime(true) - $start) * 1000));
        } catch (Throwable $e) {
            $this->logStepFailed(self::LOG_TABLE, $e);
            throw $e;
        }
    }
}
