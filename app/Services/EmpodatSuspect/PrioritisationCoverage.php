<?php

declare(strict_types=1);

namespace App\Services\EmpodatSuspect;

use App\Models\EmpodatSuspect\EmpodatSuspectPrioritisationBuild;

/**
 * How many prioritisation rows each EMPODAT Suspect source file currently
 * contributes to `empodat_suspect_prioritisation_dataset`.
 *
 * WHY NOT COUNT THE TABLE
 * -----------------------
 * The obvious implementation — `SELECT count(*) ... GROUP BY file_id` — is
 * wrong for a page render: production holds ~45 million rows across the
 * partitions and the count would run on every page load. `reltuples` from
 * `pg_class` is cheap but is an estimate that reads -1 until a partition has
 * been analysed, which is exactly the case for a partition that was never
 * built.
 *
 * `empodat_suspect_prioritisation_builds` already records an exact row count
 * per rebuild, written by the only process that writes the table. That is the
 * authoritative number and it costs one small indexed read.
 *
 * WHY THE LATEST BUILD AND THE LATEST SUCCESS ARE BOTH REPORTED
 * -------------------------------------------------------------
 * They diverge, and the difference is the whole point of showing this column.
 * A partition can hold a valid row count from a build three weeks ago while
 * the most recent attempt failed or was killed — on production, file 10012
 * sat in `running` for twelve days after a killed run while its partition was
 * empty. Reporting only the last success would hide that; reporting only the
 * last attempt would show nothing at all for a file whose latest attempt
 * failed.
 */
class PrioritisationCoverage
{
    /**
     * Coverage keyed by `files.id`, for the given files only.
     *
     * Files with no build row at all are absent from the result — the caller
     * renders those as "never built" rather than as zero rows, because the two
     * mean different things.
     *
     * @param  list<int>  $fileIds
     * @return array<int, array{
     *     row_count: int|null,
     *     built_at: \Illuminate\Support\Carbon|null,
     *     duration_ms: int|null,
     *     latest_status: string,
     *     is_stale: bool
     * }>
     */
    public function forFiles(array $fileIds): array
    {
        if ($fileIds === []) {
            return [];
        }

        // One read, reduced in PHP. The table carries one row per rebuild ever
        // performed — tens of rows, not thousands — so a window function or a
        // pair of DISTINCT ON queries would buy nothing and cost readability.
        $builds = EmpodatSuspectPrioritisationBuild::query()
            ->select(['id', 'file_id', 'row_count', 'duration_ms', 'finished_at', 'status'])
            ->whereIn('file_id', $fileIds)
            ->orderBy('id')
            ->get();

        $coverage = [];

        foreach ($builds as $build) {
            $fileId = $build->file_id;

            $existing = $coverage[$fileId] ?? [
                'row_count' => null,
                'built_at' => null,
                'duration_ms' => null,
                'latest_status' => $build->status,
                'is_stale' => false,
            ];

            // Ordered by id ascending, so the last row seen for a file is its
            // most recent attempt whatever its outcome.
            $existing['latest_status'] = $build->status;

            if ($build->succeeded()) {
                $existing['row_count'] = $build->row_count;
                $existing['built_at'] = $build->finished_at;
                // Of the successful build only. A failed attempt's duration
                // measures how long it took to break, which says nothing about
                // the data currently in the partition.
                $existing['duration_ms'] = $build->duration_ms;
            }

            $coverage[$fileId] = $existing;
        }

        foreach ($coverage as $fileId => $entry) {
            $coverage[$fileId]['is_stale'] = $entry['latest_status'] !== EmpodatSuspectPrioritisationBuild::STATUS_SUCCESS;
        }

        return $coverage;
    }
}
