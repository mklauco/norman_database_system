<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

/**
 * Phase 4b — bulk insert delta biota rows into `empodat_matrix_biota`.
 *
 * Source : legacy `dct_analysis_biota` WHERE id > $sinceId.
 * Target : PG `empodat_matrix_biota` (53 cols: original 36 + new 17 from
 *          migration 2026_05_19_132538_add_terrestrial_biota_columns_*).
 *
 * 1:1 with empodat_main by id (every biota row corresponds to a biota-matrix
 * row in main). 0 orphans verified across the delta.
 *
 * Type convention: tinyint(N) -> smallint, int(11) -> integer (for the
 * legacy taxonomic FK-style cols; bigint via foreignId() for the 17 new
 * cols introduced in the schema migration).
 *
 * No FK constraints enforced yet (lookup tables data_kingdom/_phylum/_class/
 * _order/_family/_species/_individual_or_pooled/_category/_habitat_type are
 * not in PG). Insert raw integers; constraints land in a follow-up migration.
 */
class ImportMatrixBiotaStep extends Step
{
    public function name(): string
    {
        return 'Import biota metadata (dct_analysis_biota -> empodat_matrix_biota)';
    }

    public function run(): void
    {
        // TODO: stream legacy dct_analysis_biota delta, INSERT into PG empodat_matrix_biota
        // with id = legacy id. Skip ids already present.
    }
}
