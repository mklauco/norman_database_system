<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

/**
 * Deletes NDS2.0 matrix rows whose measurement also has a row in a second
 * matrix table, when legacy keeps it in only the other table's source.
 * Expected: 826 rows in empodat_matrix_water_surface and 43 in
 * empodat_matrix_water_waste (869).
 *
 * Production:
 * docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds -e RECONCILE_EXPECT_COUNT=869 nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileDuplicateMatrixRowsSeeder' --force
 * (dry run: the same without the -e flags)
 */
final class ReconcileDuplicateMatrixRowsSeeder extends ReconcileSeeder
{
    /** @var array<string, list<int>> NDS2.0 table => surplus ids */
    private array $delete = [];

    protected function plan(): int
    {
        $tables = MatrixTables::names();
        $skipped = [];

        foreach ($tables as $i => $a) {
            foreach (array_slice($tables, $i + 1) as $b) {
                $ids = array_map(
                    static fn (object $row): int => (int) $row->id,
                    $this->pg->select('SELECT a.id FROM '.$a.' a JOIN '.$b.' b ON b.id = a.id'),
                );
                if ($ids === []) {
                    continue;
                }

                $inA = array_flip(MatrixTables::legacyHas($this->legacy, MatrixTables::legacyTable($a), $ids, $this->options->legacyPauseMs));
                $inB = array_flip(MatrixTables::legacyHas($this->legacy, MatrixTables::legacyTable($b), $ids, $this->options->legacyPauseMs));

                foreach ($ids as $id) {
                    if (isset($inA[$id]) && ! isset($inB[$id])) {
                        $this->delete[$b][$id] = $id;
                    } elseif (isset($inB[$id]) && ! isset($inA[$id])) {
                        $this->delete[$a][$id] = $id;
                    } else {
                        $skipped[] = $a.'/'.$b.' #'.$id;
                    }
                }
            }
        }

        $total = 0;
        foreach ($this->delete as $table => $ids) {
            $this->delete[$table] = array_values($ids);
            $total += count($ids);
            $this->say(MatrixTables::describe($table, $this->delete[$table]));
        }
        $this->say('Skipped (legacy has both or neither): '.count($skipped).' '.implode(', ', array_slice($skipped, 0, 5)));

        return $total;
    }

    protected function apply(ReconcileAudit $audit): void
    {
        foreach ($this->delete as $table => $ids) {
            MatrixTables::deleteLogged($this->pg, $audit, 'delete duplicate matrix rows', $table, $ids);
        }
    }

    protected function notes(): string
    {
        return 'delete matrix rows duplicated in a second table that legacy does not have';
    }
}
