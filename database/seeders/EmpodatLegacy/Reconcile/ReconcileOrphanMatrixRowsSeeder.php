<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

/**
 * Deletes NDS2.0 matrix rows without an empodat_main row whose id exists
 * nowhere in legacy. Expected: 6 067 rows in empodat_matrix_water_surface,
 * ids 163405660-163411726.
 *
 * Production:
 * docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds -e RECONCILE_EXPECT_COUNT=6067 nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileOrphanMatrixRowsSeeder' --force
 * (dry run: the same without the -e flags)
 */
final class ReconcileOrphanMatrixRowsSeeder extends ReconcileSeeder
{
    private const WINDOW = 5_000_000;

    /** @var array<string, list<int>> NDS2.0 table => orphan ids */
    private array $delete = [];

    protected function plan(): int
    {
        $total = 0;
        $skipped = [];

        foreach (MatrixTables::names() as $table) {
            $range = $this->pg->selectOne('SELECT min(id) AS lo, max(id) AS hi FROM '.$table);
            if ($range === null || $range->lo === null) {
                continue;
            }

            $candidates = [];
            for ($from = (int) $range->lo; $from <= (int) $range->hi; $from += self::WINDOW) {
                $rows = $this->pg->select(
                    'SELECT t.id FROM '.$table.' t WHERE t.id BETWEEN ? AND ? AND NOT EXISTS (SELECT 1 FROM empodat_main m WHERE m.id = t.id)',
                    [$from, $from + self::WINDOW - 1],
                );
                foreach ($rows as $row) {
                    $candidates[] = (int) $row->id;
                }
            }
            if ($candidates === []) {
                continue;
            }

            $known = array_flip([
                ...MatrixTables::legacyHas($this->legacy, 'dct_analysis', $candidates, $this->options->legacyPauseMs),
                ...MatrixTables::legacyHas($this->legacy, MatrixTables::legacyTable($table), $candidates, $this->options->legacyPauseMs),
            ]);

            foreach ($candidates as $id) {
                if (isset($known[$id])) {
                    $skipped[] = $table.' #'.$id;
                } else {
                    $this->delete[$table][] = $id;
                }
            }

            if (isset($this->delete[$table])) {
                $total += count($this->delete[$table]);
                $this->say(MatrixTables::describe($table, $this->delete[$table]));
            }
        }

        $this->say('Skipped (legacy has the id, a copy gap): '.count($skipped).' '.implode(', ', array_slice($skipped, 0, 5)));

        return $total;
    }

    protected function apply(ReconcileAudit $audit): void
    {
        foreach ($this->delete as $table => $ids) {
            MatrixTables::deleteLogged($this->pg, $audit, 'delete orphan matrix rows', $table, $ids);
        }
    }

    protected function notes(): string
    {
        return 'delete matrix rows without empodat_main row and without legacy row';
    }
}
