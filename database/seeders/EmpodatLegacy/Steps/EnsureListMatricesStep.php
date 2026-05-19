<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Throwable;

/**
 * Phase 1d — ensure every matrix id referenced by the delta exists in PG `list_matrices`.
 *
 * Source : legacy `data_matrice` (the matrix dictionary in MariaDB).
 * Target : PG `list_matrices`.
 *
 * Logic:
 *   1. SELECT DISTINCT matrix FROM legacy.dct_analysis WHERE id > $sinceId
 *   2. SELECT id FROM pg.list_matrices WHERE id IN (...)
 *   3. For each missing id, fetch the row from legacy data_matrice and INSERT
 *      it into pg.list_matrices via the audited insertOnConflictDoNothing()
 *      helper.
 *
 * Column mapping (verified against existing PG rows 35-38, 76, 77):
 *   matrice_id        -> id
 *   matrice_title1    -> title
 *   matrice_title2    -> subtitle
 *   matrice_title3    -> type
 *   matrice_title     -> name
 *   matrice_dct_name  -> dct_name
 *   matrice_unit      -> unit
 *   (derived)         -> empodat_matrix_link =
 *                        'empodat_matrix_' . str_replace(' ', '_', lower(dct_name))
 *
 * Idempotent: re-running adds nothing if all referenced ids are present.
 */
class EnsureListMatricesStep extends Step
{
    private const TABLE = 'list_matrices';

    public function name(): string
    {
        return 'Ensure list_matrices coverage (data_matrice -> list_matrices)';
    }

    public function run(): void
    {
        $start = microtime(true);

        try {
            $referenced = $this->legacy()
                ->table('dct_analysis')
                ->where('id', '>=', $this->sinceId)
                ->distinct()
                ->pluck('matrix')
                ->map(fn ($v) => (int) $v)
                ->all();

            if ($referenced === []) {
                $this->note('No matrix values in delta — nothing to ensure.');
                $this->logSmallStep(self::TABLE, [], (int) ((microtime(true) - $start) * 1000));

                return;
            }

            $present = $this->pg()
                ->table(self::TABLE)
                ->whereIn('id', $referenced)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $missing = array_values(array_diff($referenced, $present));

            if ($missing === []) {
                $this->note('All '.count($referenced).' referenced matrix ids already in PG.');
                $this->logSmallStep(self::TABLE, [], (int) ((microtime(true) - $start) * 1000));

                return;
            }

            $rows = $this->legacy()
                ->table('data_matrice')
                ->whereIn('matrice_id', $missing)
                ->get(['matrice_id', 'matrice_title1', 'matrice_title2', 'matrice_title3', 'matrice_title', 'matrice_dct_name', 'matrice_unit']);

            $insertable = $rows->map(fn ($r) => [
                'id' => (int) $r->matrice_id,
                'title' => (string) $r->matrice_title1,
                'subtitle' => (string) $r->matrice_title2,
                'type' => (string) $r->matrice_title3,
                'name' => (string) $r->matrice_title,
                'dct_name' => (string) $r->matrice_dct_name,
                'unit' => (string) $r->matrice_unit,
                'empodat_matrix_link' => 'empodat_matrix_'.strtolower(str_replace(' ', '_', (string) $r->matrice_dct_name)),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            $insertedIds = $this->insertOnConflictDoNothing(self::TABLE, $insertable);

            $this->note('Referenced='.count($referenced).'  missing='.count($missing).'  inserted='.count($insertedIds));
            $this->logSmallStep(self::TABLE, $insertedIds, (int) ((microtime(true) - $start) * 1000));
        } catch (Throwable $e) {
            $this->logStepFailed(self::TABLE, $e);
            throw $e;
        }
    }
}
