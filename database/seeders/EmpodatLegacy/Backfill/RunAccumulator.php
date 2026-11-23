<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

/**
 * Collects the missing-id runs found bucket by bucket and glues a run that
 * continues across a bucket boundary into one run.
 */
final class RunAccumulator
{
    /** @var list<array{0: int, 1: int, 2: int}> */
    private array $runs = [];

    private ?int $lastBucket = null;

    private ?int $lastLegacyId = null;

    /**
     * @param  list<array{0: int, 1: int, 2: int}>  $bucketRuns  runs found inside this bucket
     * @param  int  $firstLegacyId  smallest legacy id of this bucket
     * @param  int  $lastLegacyId  largest legacy id of this bucket
     */
    public function add(int $bucket, array $bucketRuns, int $firstLegacyId, int $lastLegacyId): void
    {
        if ($bucketRuns !== []) {
            $tail = $this->runs === [] ? null : $this->runs[array_key_last($this->runs)];
            $continues = $tail !== null
                && $this->lastBucket === $bucket - 1
                && $tail[1] === $this->lastLegacyId
                && $bucketRuns[0][0] === $firstLegacyId;

            if ($continues) {
                $first = array_shift($bucketRuns);
                $this->runs[array_key_last($this->runs)] = [$tail[0], $first[1], $tail[2] + $first[2]];
            }

            foreach ($bucketRuns as $run) {
                $this->runs[] = $run;
            }
        }

        $this->lastBucket = $bucket;
        $this->lastLegacyId = $lastLegacyId;
    }

    /**
     * @return list<array{0: int, 1: int, 2: int}>
     */
    public function runs(): array
    {
        return $this->runs;
    }
}
