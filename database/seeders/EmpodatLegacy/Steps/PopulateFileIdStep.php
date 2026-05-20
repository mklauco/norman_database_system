<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Throwable;

/**
 * Phase 3b — populate `empodat_main.file_id` via range UPDATEs.
 *
 * For each legacy `dct_list` row covering the delta:
 *   UPDATE empodat_main
 *      SET file_id = <list_id>
 *    WHERE id BETWEEN <list_analysis_from> AND <list_analysis_to>
 *      AND id > $sinceId
 *
 * Uses PG `empodat_main` PK index — each UPDATE is a fast range scan. 39
 * statements for the current delta (list_ids 597..636 covering the delta's
 * id range). Iterates in `list_id` ASC order; overlapping ranges resolve
 * via last-wins (matches v1 doc behaviour).
 *
 * Re-run: idempotent in effect (SET file_id = X to the same value),
 * burns only a few seconds.
 *
 * Rollback note: this step UPDATEs, never inserts. The audit-log entry
 * carries no `inserted_ids` / id range, so the rollback command treats it
 * as a no-op. To undo the effect, roll back Phase 3a (which deletes the
 * rows entirely).
 */
class PopulateFileIdStep extends Step
{
    private const TABLE = 'empodat_main';

    public function name(): string
    {
        return 'Populate empodat_main.file_id (range UPDATEs from dct_list)';
    }

    public function run(): void
    {
        $start = microtime(true);

        try {
            $files = $this->legacy()
                ->table('dct_list')
                ->where('list_analysis_to', '>', $this->sinceId)
                ->whereNotNull('list_analysis_from')
                ->whereNotNull('list_analysis_to')
                ->orderBy('list_id')
                ->get(['list_id', 'list_analysis_from', 'list_analysis_to']);

            if ($files->isEmpty()) {
                $this->note('No legacy file ranges cover the delta.');
                $this->logBulkStep(self::TABLE, 0, null, null, (int) ((microtime(true) - $start) * 1000));

                return;
            }

            $totalUpdated = 0;
            foreach ($files as $f) {
                $count = $this->pg()
                    ->table(self::TABLE)
                    ->where('id', '>', $this->sinceId)
                    ->whereBetween('id', [(int) $f->list_analysis_from, (int) $f->list_analysis_to])
                    ->update(['file_id' => (int) $f->list_id]);
                $totalUpdated += $count;
            }

            $this->note(sprintf(
                'Ranges processed=%d  rows updated=%d',
                $files->count(),
                $totalUpdated,
            ));

            $this->logBulkStep(self::TABLE, $totalUpdated, null, null, (int) ((microtime(true) - $start) * 1000));
        } catch (Throwable $e) {
            $this->logStepFailed(self::TABLE, $e);
            throw $e;
        }
    }
}
