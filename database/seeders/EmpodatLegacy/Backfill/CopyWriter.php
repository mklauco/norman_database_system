<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * Bulk insert through COPY FROM STDIN on the SAME connection (and therefore the
 * same transaction) the caller already opened: COPY into a TEMP staging table,
 * then INSERT ... SELECT ... ON CONFLICT (id) DO NOTHING RETURNING id. Only ids
 * missing from the target are inserted; existing rows are never touched.
 */
final class CopyWriter
{
    /**
     * Must run inside a transaction (the staging table is ON COMMIT DROP).
     *
     * @param  list<string>  $columns  PG columns in line order, id included
     * @param  list<string>  $lines  ValueCoercer::copyLine() output, no trailing newline
     * @return list<int> ids actually inserted
     */
    public static function insertMissing(ConnectionInterface $pg, string $table, array $columns, array $lines): array
    {
        if ($lines === []) {
            return [];
        }

        $quote = static fn (string $c): string => '"'.str_replace('"', '""', $c).'"';
        $columnList = implode(', ', array_map($quote, $columns));
        $staging = 'stg_legacy_backfill';

        $pg->statement(sprintf(
            'CREATE TEMP TABLE %s (LIKE %s INCLUDING DEFAULTS) ON COMMIT DROP',
            $staging,
            $quote($table),
        ));

        $pdo = $pg->getPdo();
        if (! is_callable([$pdo, 'pgsqlCopyFromArray'])) {
            throw new RuntimeException('PDO::pgsqlCopyFromArray is not available (pdo_pgsql missing).');
        }

        $copied = $pdo->pgsqlCopyFromArray($staging, $lines, "\t", '\\\\N', $columnList);
        if ($copied !== true) {
            throw new RuntimeException('COPY into staging table failed for '.$table.': '.implode(' ', $pdo->errorInfo()));
        }

        $rows = $pg->select(sprintf(
            'INSERT INTO %1$s (%2$s) SELECT %2$s FROM %3$s ON CONFLICT ("id") DO NOTHING RETURNING "id"',
            $quote($table),
            $columnList,
            $staging,
        ));

        return array_map(static fn (object $r): int => (int) $r->id, $rows);
    }
}
