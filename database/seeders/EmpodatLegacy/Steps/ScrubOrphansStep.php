<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

/**
 * Phase 5a — NULL-out orphan FK references in newly-inserted `empodat_main` rows.
 *
 * Legacy `dct_analysis` allows references to method_id / data_source_id /
 * sus_id values that do not exist in their dictionary tables. The v1 doc's
 * established treatment is to scrub them to NULL post-import rather than
 * blocking the insert.
 *
 *   UPDATE empodat_main SET method_id      = NULL
 *    WHERE id > $sinceId
 *      AND method_id NOT IN (SELECT id FROM empodat_analytical_methods)
 *
 *   UPDATE empodat_main SET data_source_id = NULL
 *    WHERE id > $sinceId
 *      AND data_source_id NOT IN (SELECT id FROM empodat_data_sources)
 *
 *   UPDATE empodat_main SET substance_id   = NULL  -- (only those still pointing nowhere)
 *    WHERE id > $sinceId
 *      AND substance_id IS NOT NULL
 *      AND substance_id NOT IN (SELECT id FROM susdat_substances)
 *
 *   UPDATE empodat_main SET concentration_indicator_id = NULL
 *    WHERE id > $sinceId
 *      AND concentration_indicator_id = 0
 *
 * Scoped to the delta via `id > $sinceId` so it never touches rows from
 * prior loads. Idempotent.
 */
class ScrubOrphansStep extends Step
{
    public function name(): string
    {
        return 'Scrub orphan FK references (method/data/substance/dic = NULL where dangling)';
    }

    public function run(): void
    {
        // TODO: execute the four scoped UPDATEs above against PG.
    }
}
