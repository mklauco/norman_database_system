<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use Database\Seeders\EmpodatLegacy\Backfill\BackfillTables;
use Illuminate\Database\Connection;

/**
 * Shared helpers of the matrix reconcile seeders. Table names only ever come
 * from BackfillTables::MATRIX_TABLES, never from data.
 */
final class MatrixTables
{
    private const CHUNK = 1000;

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(BackfillTables::MATRIX_TABLES);
    }

    public static function legacyTable(string $table): string
    {
        return BackfillTables::MATRIX_TABLES[$table][0];
    }

    /**
     * @param  list<int>  $ids
     * @return list<int> the ids that exist in the given legacy table
     */
    public static function legacyHas(Connection $legacy, string $legacyTable, array $ids, int $pauseMs = 0): array
    {
        $found = [];
        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $rows = $legacy->select('SELECT id FROM '.$legacyTable.' WHERE id IN ('.$placeholders.')', $chunk);
            foreach ($rows as $row) {
                $found[] = (int) $row->id;
            }
            if ($pauseMs > 0) {
                usleep($pauseMs * 1000);
            }
        }

        return $found;
    }

    /**
     * @param  list<int>  $ids
     */
    public static function describe(string $table, array $ids): string
    {
        sort($ids);

        return $table.': '.count($ids).' rows, ids '.$ids[0].'-'.$ids[count($ids) - 1]
            .', e.g. '.implode(', ', array_slice($ids, 0, 5));
    }

    /**
     * Deletes the ids in chunks of 1 000, each chunk in its own transaction
     * together with its before-image and audit entry.
     *
     * @param  list<int>  $ids
     */
    public static function deleteLogged(Connection $pg, ReconcileAudit $audit, string $step, string $table, array $ids): void
    {
        sort($ids);
        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            $pg->transaction(static function () use ($pg, $audit, $step, $table, $chunk): void {
                $started = hrtime(true);
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $before = array_map(
                    static fn (object $row): array => (array) $row,
                    $pg->select('DELETE FROM '.$table.' WHERE id IN ('.$placeholders.') RETURNING *', $chunk),
                );
                $audit->logDelete($step, $table, $before, (int) ((hrtime(true) - $started) / 1_000_000));
            });
        }
    }
}
