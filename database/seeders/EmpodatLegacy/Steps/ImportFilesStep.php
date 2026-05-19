<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

/**
 * Phase 1a — import `files` rows for the delta.
 *
 * Source : legacy `dct_list` where `list_analysis_to >= $sinceId`
 *          AND `id` not already in PG `files`.
 * Target : PG `files` (id = legacy list_id, no remapping).
 *
 * Column mapping legacy -> PG: see LEGACY_MIGRATION_PLAN.md "File-id convention".
 * PG-only columns (file_path, file_size, mime_type, etc.) stay NULL for
 * legacy-imported rows; expert users fill them via the frontend later.
 */
class ImportFilesStep extends Step
{
    public function name(): string
    {
        return 'Import files (dct_list -> files)';
    }

    public function run(): void
    {
        // TODO: stream rows from legacy dct_list, INSERT into PG files
        // with id = list_id, skipping ids already present.
    }
}
