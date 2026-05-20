<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Phase 5a — NULL-scrub orphan FK references in newly-inserted `empodat_main` rows.
 *
 * Legacy `dct_analysis` allows references to method_id / data_source_id values
 * that do not exist in their dictionary tables. The v1 doc's established
 * treatment is to scrub them to NULL post-import rather than blocking the
 * insert. This step performs four targeted UPDATEs, scoped to `id > $sinceId`
 * so existing rows from prior loads are never touched.
 *
 * Currently expected counts (measured 2026-05-20 on the delta):
 *   - method_id orphans:                 203 256 rows
 *   - data_source_id orphans:            6 rows (legacy `id_data=161 140 671`)
 *   - substance_id orphans:              0 (Phase 3a already mapped via sus_id;
 *                                          24 545 NULLs landed at insert time
 *                                          for the 71 missing sus_id codes)
 *   - concentration_indicator_id == 0:   0 (no zero-sentinel rows in this delta)
 *
 * Two of the four UPDATEs are no-ops on the current delta but are kept as
 * defensive — they protect against future deltas where the orphan profile may
 * differ. Each UPDATE is cheap when its WHERE matches nothing.
 *
 * Re-run safety: every UPDATE is naturally idempotent (rows that were already
 * NULL'd in a prior run don't match the `IS NOT NULL` predicate). The
 * `previouslyCompleted()` guard short-circuits successful re-runs anyway.
 *
 * Rollback note: this step UPDATEs, never inserts. The audit log carries no
 * `inserted_ids` / id range, so `empodat-legacy:rollback` treats it as a
 * no-op. To undo the effect, roll back Phase 3a (which deletes the rows).
 */
class ScrubOrphansStep extends Step
{
    private const TABLE = 'empodat_main';

    public function name(): string
    {
        return 'Scrub orphan FK references (method/data/substance/dic = NULL where dangling)';
    }

    public function run(): void
    {
        $start = microtime(true);

        if ($this->previouslyCompleted(self::TABLE.'_orphan_scrub')) {
            $this->note('Already completed for this since_id; skipping (run rollback to re-run).');
            $this->logBulkStep(self::TABLE.'_orphan_scrub', 0, null, null, (int) ((microtime(true) - $start) * 1000));

            return;
        }

        try {
            $totalUpdated = 0;
            $perColumn = [];

            DB::transaction(function () use (&$totalUpdated, &$perColumn): void {
                $perColumn['method_id'] = $this->scrubColumn(
                    'method_id',
                    'empodat_analytical_methods',
                );
                $perColumn['data_source_id'] = $this->scrubColumn(
                    'data_source_id',
                    'empodat_data_sources',
                );
                $perColumn['substance_id'] = $this->scrubColumn(
                    'substance_id',
                    'susdat_substances',
                );
                $perColumn['concentration_indicator_id_zero'] = $this->scrubZeroSentinel(
                    'concentration_indicator_id',
                );

                $totalUpdated = array_sum($perColumn);
            });

            foreach ($perColumn as $col => $n) {
                $this->note(sprintf('   %s: %d rows scrubbed', $col, $n));
            }
            $this->note(sprintf('Total rows updated=%d', $totalUpdated));

            $this->logBulkStep(self::TABLE.'_orphan_scrub', $totalUpdated, null, null, (int) ((microtime(true) - $start) * 1000));
        } catch (Throwable $e) {
            $this->logStepFailed(self::TABLE.'_orphan_scrub', $e);
            throw $e;
        }
    }

    /**
     * NULL out `empodat_main.<column>` where the referenced row is missing from
     * `<dictionaryTable>`. Delta-scoped.
     */
    private function scrubColumn(string $column, string $dictionaryTable): int
    {
        $sql = sprintf(
            'UPDATE "%s" SET "%s" = NULL
              WHERE id > ?
                AND "%s" IS NOT NULL
                AND NOT EXISTS (SELECT 1 FROM "%s" d WHERE d.id = "%s"."%s")',
            self::TABLE,
            $column,
            $column,
            $dictionaryTable,
            self::TABLE,
            $column,
        );

        return $this->pg()->update($sql, [$this->sinceId]);
    }

    /**
     * NULL out `empodat_main.<column>` where it equals the legacy zero sentinel
     * (PG semantic: 0 is not a valid foreign key). Delta-scoped.
     */
    private function scrubZeroSentinel(string $column): int
    {
        $sql = sprintf(
            'UPDATE "%s" SET "%s" = NULL WHERE id > ? AND "%s" = 0',
            self::TABLE,
            $column,
            $column,
        );

        return $this->pg()->update($sql, [$this->sinceId]);
    }
}
