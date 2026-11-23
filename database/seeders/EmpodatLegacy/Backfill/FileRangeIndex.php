<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

/**
 * Maps an empodat id to its `files.id`, reproducing `PopulateFileIdStep`: the
 * ranges are applied in ascending list_id order and later ranges overwrite
 * earlier ones, so where ranges overlap the HIGHEST list_id wins. Ids outside
 * every range have no file (NULL), which is correct for old rows.
 */
final class FileRangeIndex
{
    /** @var list<array{0: int, 1: int, 2: int}> disjoint [from, to, fileId] ascending */
    private array $segments = [];

    /**
     * @param  list<array{0: int, 1: int, 2: int}>  $ranges  [from, to, fileId]
     */
    public function __construct(array $ranges)
    {
        $ranges = array_values(array_filter($ranges, static fn (array $r): bool => $r[0] <= $r[1]));
        if ($ranges === []) {
            return;
        }

        $points = [];
        foreach ($ranges as [$from, $to]) {
            $points[$from] = true;
            $points[$to + 1] = true;
        }
        $points = array_keys($points);
        sort($points);

        $last = count($points) - 1;
        for ($i = 0; $i < $last; $i++) {
            $start = $points[$i];
            $end = $points[$i + 1] - 1;
            $winner = null;
            foreach ($ranges as [$from, $to, $fileId]) {
                if ($from <= $start && $to >= $end && ($winner === null || $fileId > $winner)) {
                    $winner = $fileId;
                }
            }
            if ($winner === null) {
                continue;
            }

            $tail = $this->segments === [] ? null : $this->segments[array_key_last($this->segments)];
            if ($tail !== null && $tail[2] === $winner && $start === $tail[1] + 1) {
                $this->segments[array_key_last($this->segments)][1] = $end;
            } else {
                $this->segments[] = [$start, $end, $winner];
            }
        }
    }

    public function fileIdFor(int $id): ?int
    {
        $low = 0;
        $high = count($this->segments) - 1;

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            if ($id < $this->segments[$mid][0]) {
                $high = $mid - 1;
            } elseif ($id > $this->segments[$mid][1]) {
                $low = $mid + 1;
            } else {
                return $this->segments[$mid][2];
            }
        }

        return null;
    }

    /**
     * Distinct winning file ids that overlap [from, to].
     *
     * @return list<int>
     */
    public function winnersWithin(int $from, int $to): array
    {
        $winners = [];
        foreach ($this->segments as [$segFrom, $segTo, $fileId]) {
            if ($segTo >= $from && $segFrom <= $to) {
                $winners[$fileId] = true;
            }
        }

        return array_keys($winners);
    }

    public function segmentCount(): int
    {
        return count($this->segments);
    }
}
