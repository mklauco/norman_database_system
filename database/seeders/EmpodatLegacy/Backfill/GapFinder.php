<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use Illuminate\Database\ConnectionInterface;

/**
 * Diff-driven gap detection. Never hard-codes id ranges.
 *
 *   1. One GROUP BY pass per side: row count and id checksum per id bucket
 *      (legacy: covering index scan; PG: HashAggregate with enable_sort off).
 *      Restricted to the id window when one is set.
 *   2. Only buckets that exist in legacy and differ are drilled into: the ids
 *      of that bucket are read from both sides and diffed in PHP.
 *   3. Missing ids come back as runs (see IdRanges) glued across buckets.
 *
 * With a cap (dry run), drilling stops once `cap` missing ids are collected;
 * buckets not drilled contribute their count difference to `estimatedMissing`
 * and `complete` is false.
 *
 * Results are cached per (legacy table, pg table, cap) for the life of the
 * object; use a fresh instance for the post-run verification.
 */
final class GapFinder
{
    /** @var array<string, array<int, array{count: int, sum: string}>> */
    private array $legacyBuckets = [];

    /** @var array<string, array<int, array{count: int, sum: string}>> */
    private array $pgBuckets = [];

    /** @var array<string, array{runs: list<array{0: int, 1: int, 2: int}>, missing: int, estimatedMissing: int, complete: bool, legacyRows: int, targetRows: int, differing: int}> */
    private array $cache = [];

    public function __construct(private readonly BackfillContext $ctx) {}

    /**
     * @param  int  $cap  stop after this many missing ids (0 = find them all)
     * @return array{runs: list<array{0: int, 1: int, 2: int}>, missing: int, estimatedMissing: int, complete: bool, legacyRows: int, targetRows: int, differing: int}
     */
    public function missing(string $legacyTable, string $pgTable, int $cap = 0): array
    {
        $key = $legacyTable.'>'.$pgTable.'#'.$cap;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $legacy = $this->legacyBuckets($legacyTable);
        $target = $this->pgBuckets($pgTable);
        $differing = IdRanges::differingBuckets($legacy, $target);
        $size = $this->ctx->options->bucketSize;

        $this->ctx->progress(sprintf(
            '%s -> %s: %d legacy buckets, %d differ',
            $legacyTable,
            $pgTable,
            count($legacy),
            count($differing),
        ));

        $accumulator = new RunAccumulator;
        $collected = 0;
        $estimated = 0;
        $complete = true;

        foreach ($differing as $bucket) {
            if ($cap > 0 && $collected >= $cap) {
                $complete = false;
                $estimated += IdRanges::countDifference($legacy[$bucket], $target[$bucket] ?? null);

                continue;
            }

            $range = $this->ctx->options->clampHalfOpen($bucket * $size, ($bucket + 1) * $size);
            if ($range === null) {
                continue;
            }
            [$lo, $hi] = $range;

            $legacyIds = $this->fetchIds($this->ctx->legacy, $this->ctx->quoteLegacy($legacyTable), $lo, $hi);
            $this->ctx->pauseLegacy();
            if ($legacyIds === []) {
                continue;
            }
            $targetIds = $this->fetchIds($this->ctx->pg, $this->ctx->quotePg($pgTable), $lo, $hi);

            $found = IdRanges::missingRunsCapped($legacyIds, $targetIds, $cap > 0 ? $cap - $collected : 0);
            $collected += $found['kept'];
            $estimated += $found['missing'];
            if ($found['kept'] < $found['missing']) {
                $complete = false;
            }

            $accumulator->add($bucket, $found['runs'], $legacyIds[0], $legacyIds[array_key_last($legacyIds)]);
        }

        $runs = $accumulator->runs();

        return $this->cache[$key] = [
            'runs' => $runs,
            'missing' => IdRanges::countIds($runs),
            'estimatedMissing' => $estimated,
            'complete' => $complete,
            'legacyRows' => $this->sumCounts($legacy),
            'targetRows' => $this->sumCounts($target),
            'differing' => count($differing),
        ];
    }

    /**
     * @return array<int, array{count: int, sum: string}>
     */
    public function legacyBuckets(string $table): array
    {
        if (isset($this->legacyBuckets[$table])) {
            return $this->legacyBuckets[$table];
        }

        [$where, $bindings] = $this->windowClause();
        $size = $this->ctx->options->bucketSize;
        $rows = $this->ctx->legacy->select(sprintf(
            'SELECT id DIV %1$d AS bucket, COUNT(*) AS cnt, CAST(SUM(id) AS CHAR) AS idsum FROM %2$s%3$s GROUP BY id DIV %1$d',
            $size,
            $this->ctx->quoteLegacy($table),
            $where,
        ), $bindings);
        $this->ctx->pauseLegacy();

        return $this->legacyBuckets[$table] = $this->normalise($rows);
    }

    /**
     * @return array<int, array{count: int, sum: string}>
     */
    public function pgBuckets(string $table): array
    {
        if (isset($this->pgBuckets[$table])) {
            return $this->pgBuckets[$table];
        }

        [$where, $bindings] = $this->windowClause();
        $size = $this->ctx->options->bucketSize;
        $this->ctx->pg->statement('SET enable_sort = off');
        try {
            $rows = $this->ctx->pg->select(sprintf(
                'SELECT id / %1$d AS bucket, count(*) AS cnt, sum(id)::text AS idsum FROM %2$s%3$s GROUP BY id / %1$d',
                $size,
                $this->ctx->quotePg($table),
                $where,
            ), $bindings);
        } finally {
            $this->ctx->pg->statement('RESET enable_sort');
        }

        return $this->pgBuckets[$table] = $this->normalise($rows);
    }

    /**
     * WHERE clause (with leading space) restricting a scan to the id window.
     *
     * @return array{0: string, 1: list<int>}
     */
    private function windowClause(): array
    {
        $conditions = [];
        $bindings = [];
        if ($this->ctx->options->idFrom !== null) {
            $conditions[] = 'id >= ?';
            $bindings[] = $this->ctx->options->idFrom;
        }
        if ($this->ctx->options->idTo !== null) {
            $conditions[] = 'id <= ?';
            $bindings[] = $this->ctx->options->idTo;
        }

        return $conditions === [] ? ['', []] : [' WHERE '.implode(' AND ', $conditions), $bindings];
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, array{count: int, sum: string}>
     */
    private function normalise(array $rows): array
    {
        $buckets = [];
        foreach ($rows as $row) {
            $buckets[(int) $row->bucket] = ['count' => (int) $row->cnt, 'sum' => (string) $row->idsum];
        }
        ksort($buckets);

        return $buckets;
    }

    /**
     * @param  array<int, array{count: int, sum: string}>  $buckets
     */
    private function sumCounts(array $buckets): int
    {
        $total = 0;
        foreach ($buckets as $stats) {
            $total += $stats['count'];
        }

        return $total;
    }

    /**
     * @return list<int> ascending ids in [lo, hi)
     */
    private function fetchIds(ConnectionInterface $connection, string $quotedTable, int $lo, int $hi): array
    {
        $ids = [];
        foreach ($connection->cursor(
            'SELECT id FROM '.$quotedTable.' WHERE id >= ? AND id < ? ORDER BY id',
            [$lo, $hi],
        ) as $row) {
            $ids[] = (int) $row->id;
        }

        return $ids;
    }
}
