<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

/**
 * Phase 3b — populate `empodat_main.file_id` via range UPDATEs.
 *
 * For each row in legacy `dct_list` where list_analysis_to > $sinceId:
 *   UPDATE empodat_main
 *      SET file_id = <list_id>
 *    WHERE file_id IS NULL
 *      AND id BETWEEN <list_analysis_from> AND <list_analysis_to>
 *
 * Uses PG empodat_main PK index — each UPDATE is a range scan. ~27 statements
 * for the current delta. Per v1 doc, this is faster than resolving file_id
 * inline during the main INSERT.
 */
class PopulateFileIdStep extends Step
{
    public function name(): string
    {
        return 'Populate empodat_main.file_id (range UPDATEs from dct_list)';
    }

    public function run(): void
    {
        // TODO: for each new-or-relevant dct_list row, run range UPDATE on empodat_main.
    }
}
