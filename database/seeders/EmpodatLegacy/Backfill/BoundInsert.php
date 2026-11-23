<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use Illuminate\Database\ConnectionInterface;

/**
 * Small bound-parameter INSERTs for the lookup tables and new stations.
 */
final class BoundInsert
{
    /**
     * INSERT ... ON CONFLICT (id) DO NOTHING RETURNING id with explicit ids.
     *
     * @param  list<array<string, mixed>>  $rows  same keys in every row
     * @return list<int> ids actually inserted
     */
    public static function ignoreConflicts(ConnectionInterface $pg, string $table, array $rows): array
    {
        return self::run($pg, $table, $rows, ' ON CONFLICT ("id") DO NOTHING');
    }

    /**
     * INSERT with an auto-generated id; returns the new ids in input order.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<int>
     */
    public static function returningIds(ConnectionInterface $pg, string $table, array $rows): array
    {
        return self::run($pg, $table, $rows, '');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<int>
     */
    private static function run(ConnectionInterface $pg, string $table, array $rows, string $conflictClause): array
    {
        if ($rows === []) {
            return [];
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map(static fn (string $c): string => '"'.$c.'"', $columns));
        $perStatement = max(1, intdiv(60000, count($columns)));
        $placeholders = '('.implode(', ', array_fill(0, count($columns), '?')).')';
        $ids = [];

        foreach (array_chunk($rows, $perStatement) as $chunk) {
            $bindings = [];
            foreach ($chunk as $row) {
                foreach ($columns as $column) {
                    $bindings[] = $row[$column] ?? null;
                }
            }

            $sql = sprintf(
                'INSERT INTO "%s" (%s) VALUES %s%s RETURNING "id"',
                $table,
                $columnList,
                implode(', ', array_fill(0, count($chunk), $placeholders)),
                $conflictClause,
            );

            foreach ($pg->select($sql, $bindings) as $inserted) {
                $ids[] = (int) $inserted->id;
            }
        }

        return $ids;
    }
}
