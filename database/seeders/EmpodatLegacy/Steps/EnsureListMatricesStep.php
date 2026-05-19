<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

/**
 * Phase 1d — ensure every matrix id referenced by the delta exists in PG `list_matrices`.
 *
 * Source : legacy `data_matrice` (the matrix dictionary in MariaDB).
 * Target : PG `list_matrices`.
 *
 * Logic:
 *   1. SELECT DISTINCT matrix FROM dct_analysis WHERE id > $sinceId
 *   2. SELECT id FROM list_matrices WHERE id IN (...)  -- in PG
 *   3. For each matrix id not present in PG, look up the row in
 *      legacy data_matrice and INSERT it into PG list_matrices with
 *      this column mapping (verified against existing PG rows 35-38, 76, 77):
 *
 *        matrice_id        -> id
 *        matrice_title1    -> title
 *        matrice_title2    -> subtitle
 *        matrice_title3    -> type
 *        matrice_title     -> name
 *        matrice_dct_name  -> dct_name
 *        matrice_unit      -> unit
 *        (derived)         -> empodat_matrix_link = 'empodat_matrix_'.lower(replace(dct_name, ' ', '_'))
 *
 *   The 'empodat_matrix_link' value mirrors the existing PG convention
 *   (e.g. 'Soil' -> 'empodat_matrix_soil', 'Sewage sludge' -> 'empodat_matrix_sewage_sludge').
 *
 * For the current delta this inserts a single row (id=78, "Soil - Bulk").
 * Idempotent: re-running adds nothing if all referenced ids are present.
 */
class EnsureListMatricesStep extends Step
{
    public function name(): string
    {
        return 'Ensure list_matrices coverage (data_matrice -> list_matrices)';
    }

    public function run(): void
    {
        // TODO: 1) gather distinct matrix ids in legacy delta,
        //       2) diff against PG list_matrices.id,
        //       3) for each missing id, fetch from legacy data_matrice and INSERT into PG.
    }
}
