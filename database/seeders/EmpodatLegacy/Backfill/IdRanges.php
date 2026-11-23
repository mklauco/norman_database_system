<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

/**
 * Pure id/bucket arithmetic used by the diff-driven gap finder. No database
 * access. A "run" is `[from, to, count]`: `count` legacy ids that all lack a
 * target row; `from`/`to` are the first and last of them, and no legacy id
 * that DOES exist in the target lies between them.
 */
final class IdRanges
{
    /**
     * Buckets that exist in legacy and differ from the target by row count or
     * id checksum (a missing id plus an extra target-only id inside one bucket
     * keeps the count equal, the checksum catches it).
     *
     * @param  array<int, array{count: int, sum: string}>  $legacy  bucket => stats
     * @param  array<int, array{count: int, sum: string}>  $target  bucket => stats
     * @return list<int> sorted bucket numbers
     */
    public static function differingBuckets(array $legacy, array $target): array
    {
        $differing = [];
        foreach ($legacy as $bucket => $stats) {
            $other = $target[$bucket] ?? null;
            if ($other === null || $other['count'] !== $stats['count'] || $other['sum'] !== $stats['sum']) {
                $differing[] = (int) $bucket;
            }
        }
        sort($differing);

        return $differing;
    }

    /**
     * Legacy ids absent from the target, grouped into runs.
     *
     * @param  list<int>  $legacyIds  ascending
     * @param  list<int>  $targetIds  ascending
     * @return list<array{0: int, 1: int, 2: int}>
     */
    public static function missingRuns(array $legacyIds, array $targetIds): array
    {
        $runs = [];
        $open = null;
        $t = 0;
        $targetCount = count($targetIds);

        foreach ($legacyIds as $id) {
            while ($t < $targetCount && $targetIds[$t] < $id) {
                $t++;
            }
            $present = $t < $targetCount && $targetIds[$t] === $id;

            if ($present) {
                if ($open !== null) {
                    $runs[] = $open;
                    $open = null;
                }

                continue;
            }

            if ($open === null) {
                $open = [$id, $id, 1];
            } else {
                $open[1] = $id;
                $open[2]++;
            }
        }

        if ($open !== null) {
            $runs[] = $open;
        }

        return $runs;
    }

    /**
     * Like missingRuns(), but keeps only the first $cap missing ids (0 = all).
     * Also returns how many ids of the bucket are missing in total, so a capped
     * caller still knows the exact gap of every bucket it drilled into.
     *
     * @param  list<int>  $legacyIds  ascending
     * @param  list<int>  $targetIds  ascending
     * @return array{runs: list<array{0: int, 1: int, 2: int}>, kept: int, missing: int}
     */
    public static function missingRunsCapped(array $legacyIds, array $targetIds, int $cap): array
    {
        $missing = self::missingIds($legacyIds, $targetIds);
        $total = count($missing);

        if ($cap <= 0 || $total <= $cap) {
            return ['runs' => self::missingRuns($legacyIds, $targetIds), 'kept' => $total, 'missing' => $total];
        }

        $lastKept = $missing[$cap - 1];
        $legacyUpTo = [];
        foreach ($legacyIds as $id) {
            if ($id > $lastKept) {
                break;
            }
            $legacyUpTo[] = $id;
        }

        return ['runs' => self::missingRuns($legacyUpTo, $targetIds), 'kept' => $cap, 'missing' => $total];
    }

    /**
     * Rows a bucket lacks judging by counts alone (exact when the target has no
     * extra ids in that bucket, a lower bound otherwise).
     *
     * @param  array{count: int, sum: string}  $legacy
     * @param  array{count: int, sum: string}|null  $target
     */
    public static function countDifference(array $legacy, ?array $target): int
    {
        return max(0, $legacy['count'] - ($target['count'] ?? 0));
    }

    /**
     * @param  list<int>  $legacyIds  ascending
     * @param  list<int>  $targetIds  ascending
     * @return list<int>
     */
    public static function missingIds(array $legacyIds, array $targetIds): array
    {
        $missing = [];
        $t = 0;
        $targetCount = count($targetIds);

        foreach ($legacyIds as $id) {
            while ($t < $targetCount && $targetIds[$t] < $id) {
                $t++;
            }
            if (! ($t < $targetCount && $targetIds[$t] === $id)) {
                $missing[] = $id;
            }
        }

        return $missing;
    }

    /**
     * @param  list<array{0: int, 1: int, 2: int}>  $runs
     */
    public static function countIds(array $runs): int
    {
        $total = 0;
        foreach ($runs as $run) {
            $total += $run[2];
        }

        return $total;
    }

    /**
     * Binary search; $runs must be ascending and non-overlapping.
     *
     * @param  list<array{0: int, 1: int, 2: int}>  $runs
     */
    public static function contains(array $runs, int $id): bool
    {
        $low = 0;
        $high = count($runs) - 1;

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            if ($id < $runs[$mid][0]) {
                $high = $mid - 1;
            } elseif ($id > $runs[$mid][1]) {
                $low = $mid + 1;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{0: int, 1: int, 2: int}>  $runs
     */
    public static function format(array $runs, int $limit = 40): string
    {
        $parts = [];
        foreach (array_slice($runs, 0, $limit) as [$from, $to, $count]) {
            $parts[] = $from === $to ? (string) $from : sprintf('%d-%d (%d ids)', $from, $to, $count);
        }
        if (count($runs) > $limit) {
            $parts[] = sprintf('... and %d more runs', count($runs) - $limit);
        }

        return $parts === [] ? '(none)' : implode(', ', $parts);
    }

    public static function bucketOf(int $id, int $bucketSize): int
    {
        return intdiv($id, $bucketSize);
    }
}
