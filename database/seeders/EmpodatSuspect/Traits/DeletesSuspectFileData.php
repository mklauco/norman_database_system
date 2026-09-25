<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect\Traits;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Removes ONE source file's EMPODAT Suspect data, and nothing else, so that
 * file can be re-imported from an updated spreadsheet.
 *
 * SCOPE IS ENFORCED, NOT ASSUMED
 * ------------------------------
 * Every statement is filtered on a single `file_id`, and the caller must also
 * state the id range that file is known to occupy. Before anything is deleted:
 *
 *   - the row count and MIN/MAX id for that file_id are read back and must fall
 *     exactly inside the declared range. A row outside it means the caller's
 *     picture of the file is wrong, and the delete is refused rather than
 *     guessed at;
 *   - the mapping rows about to be removed are checked against
 *     `empodat_suspect_main`: if ANY other file's rows reference them, the
 *     delete is refused. The mapping seeders are skip-if-exists, so a heading
 *     shared between two files belongs to whichever file inserted it first,
 *     and deleting it would orphan the other file.
 *
 * ORDER MATTERS (foreign keys)
 * ----------------------------
 *   substances -> metadata -> main -> mapping
 *
 *   - `fk_esmd_main` (metadata -> main, ON DELETE NO ACTION, not deferrable)
 *     rejects the main delete while a metadata row still points at it, and that
 *     check runs per statement, not at COMMIT — so order matters even inside a
 *     single transaction.
 *   - `fk_esm_xlsx_mapping` (main -> mapping) rejects the mapping delete while
 *     a main row still references it, so mapping rows go last.
 *
 * WHY MAIN IS DELETED BY ID RANGE
 * -------------------------------
 * `empodat_suspect_main` is partitioned on `is_numeric_concentration`, and only
 * the numeric partition carries an index on `file_id`; the non-numeric
 * partition (~30M rows) does not. `WHERE file_id = ?` therefore sequentially
 * scans it. Deleting `WHERE id BETWEEN ? AND ? AND file_id = ?` uses the
 * primary key on both partitions instead — the `file_id` predicate stays as the
 * scope guarantee, the id range makes it fast.
 *
 * The whole teardown runs in one transaction: either the file is fully cleared
 * or nothing changed.
 */
trait DeletesSuspectFileData
{
    /**
     * @param  int  $fileId  the ONLY file_id any statement here may touch
     * @param  int  $idFrom  first `empodat_suspect_main.id` this file is known to occupy
     * @param  int  $idTo  last `empodat_suspect_main.id` this file is known to occupy
     *
     * @throws RuntimeException if the file's rows do not sit exactly inside the declared range,
     *                          or if another file references this file's mapping rows
     */
    protected function deleteSuspectFileData(int $fileId, int $idFrom, int $idTo): void
    {
        $this->assertMainRowsInsideRange($fileId, $idFrom, $idTo);
        $this->assertMappingRowsUnshared($fileId);

        DB::transaction(function () use ($fileId, $idFrom, $idTo): void {
            $substances = DB::table('empodat_suspect_substances')
                ->where('file_id', $fileId)
                ->delete();

            $metadata = DB::table('empodat_suspect_metadata')
                ->where('file_id', $fileId)
                ->delete();

            $main = DB::table('empodat_suspect_main')
                ->whereBetween('id', [$idFrom, $idTo])
                ->where('file_id', $fileId)
                ->delete();

            $mapping = DB::table('empodat_suspect_xlsx_stations_mapping')
                ->where('file_id', $fileId)
                ->delete();

            $this->command->info(sprintf(
                '  Deleted for file_id=%d: %s main, %s metadata, %s substance, %s mapping row(s).',
                $fileId,
                number_format($main),
                number_format($metadata),
                number_format($substances),
                number_format($mapping),
            ));
        });

        $this->assertFileIsEmpty($fileId);
    }

    /**
     * The file must own rows, and every one of them must sit inside the
     * declared range — otherwise the range-scoped delete would leave some
     * behind and the re-import would double them.
     */
    private function assertMainRowsInsideRange(int $fileId, int $idFrom, int $idTo): void
    {
        $stats = DB::table('empodat_suspect_main')
            ->where('file_id', $fileId)
            ->selectRaw('count(*) AS rows, min(id) AS id_min, max(id) AS id_max')
            ->first();

        $rows = (int) ($stats->rows ?? 0);

        if ($rows === 0) {
            $this->command->warn("  file_id={$fileId} has no empodat_suspect_main rows — nothing to delete there.");

            return;
        }

        $min = (int) $stats->id_min;
        $max = (int) $stats->id_max;

        if ($min < $idFrom || $max > $idTo) {
            throw new RuntimeException(sprintf(
                'ABORTED: file_id=%d occupies ids %s..%s, which is not inside the declared range %s..%s. '
                .'Nothing was deleted. Re-check files.main_id_from / main_id_to before re-running.',
                $fileId,
                number_format($min),
                number_format($max),
                number_format($idFrom),
                number_format($idTo),
            ));
        }

        $this->command->info(sprintf(
            '  file_id=%d: %s main row(s) spanning %s..%s — inside the declared range %s..%s.',
            $fileId,
            number_format($rows),
            number_format($min),
            number_format($max),
            number_format($idFrom),
            number_format($idTo),
        ));
    }

    /**
     * Refuse to delete mapping rows that another file's main rows still use.
     */
    private function assertMappingRowsUnshared(int $fileId): void
    {
        $foreign = DB::table('empodat_suspect_xlsx_stations_mapping AS m')
            ->join('empodat_suspect_main AS s', 's.xlsx_station_mapping_id', '=', 'm.id')
            ->where('m.file_id', $fileId)
            ->where('s.file_id', '!=', $fileId)
            ->selectRaw('s.file_id, count(*) AS rows')
            ->groupBy('s.file_id')
            ->get();

        if ($foreign->isEmpty()) {
            return;
        }

        $detail = $foreign
            ->map(fn (object $row): string => 'file_id='.$row->file_id.' ('.number_format((int) $row->rows).' rows)')
            ->implode(', ');

        throw new RuntimeException(
            "ABORTED: mapping rows owned by file_id={$fileId} are referenced by other files: {$detail}. "
            .'Nothing was deleted. Those mapping rows must be re-assigned before this file can be re-imported.'
        );
    }

    /**
     * Post-condition: the file owns nothing any more, in any of the four tables.
     */
    private function assertFileIsEmpty(int $fileId): void
    {
        $leftovers = [
            'empodat_suspect_main' => DB::table('empodat_suspect_main')->where('file_id', $fileId)->count(),
            'empodat_suspect_metadata' => DB::table('empodat_suspect_metadata')->where('file_id', $fileId)->count(),
            'empodat_suspect_substances' => DB::table('empodat_suspect_substances')->where('file_id', $fileId)->count(),
            'empodat_suspect_xlsx_stations_mapping' => DB::table('empodat_suspect_xlsx_stations_mapping')->where('file_id', $fileId)->count(),
        ];

        $remaining = array_filter($leftovers);

        if ($remaining === []) {
            $this->command->info("  Verified: file_id={$fileId} is now empty in all four tables.");

            return;
        }

        $detail = [];
        foreach ($remaining as $table => $count) {
            $detail[] = $table.'='.number_format($count);
        }

        throw new RuntimeException(
            "ABORTED: file_id={$fileId} still has rows after the scoped delete: ".implode(', ', $detail).'. '
            .'Do NOT re-import on top of this — investigate first.'
        );
    }
}
