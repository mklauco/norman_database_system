<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

/**
 * signature => existing empodat_stations.id. When several PG stations share a
 * signature the lowest-id NON-deprecated one wins (a deprecated station is only
 * used if no live one exists). Feed rows in ascending id order.
 */
final class StationIndex
{
    /** @var array<string, int> */
    private array $ids = [];

    /** @var array<string, bool> */
    private array $deprecated = [];

    /** @var array<string, true> */
    private array $ambiguous = [];

    public function add(string $signature, int $id, bool $deprecated = false): void
    {
        if (! isset($this->ids[$signature])) {
            $this->ids[$signature] = $id;
            $this->deprecated[$signature] = $deprecated;

            return;
        }

        $this->ambiguous[$signature] = true;
        if ($this->deprecated[$signature] && ! $deprecated) {
            $this->ids[$signature] = $id;
            $this->deprecated[$signature] = false;
        }
    }

    public function find(string $signature): ?int
    {
        return $this->ids[$signature] ?? null;
    }

    public function count(): int
    {
        return count($this->ids);
    }

    public function ambiguousCount(): int
    {
        return count($this->ambiguous);
    }
}
