<?php

declare(strict_types=1);

namespace App\Services\EmpodatSuspect;

use RuntimeException;

/**
 * Hands out `empodat_suspect_main` ids from a FIXED, pre-existing id range
 * instead of from the table's BIGSERIAL sequence.
 *
 * WHY
 * ---
 * Re-importing one source file normally appends its rows at the end of the
 * shared sequence, which moves that file's id block to the tail and breaks the
 * ordered, non-overlapping per-file ranges that
 * {@see \Database\Seeders\EmpodatSuspect\EmpodatSuspectResetAndReseedSeeder}
 * exists to establish. When a file's rows are deleted and regenerated in place,
 * its ids can instead be re-used: the block is free, contiguous, and owned by
 * exactly that file.
 *
 * THE CEILING IS THE POINT
 * ------------------------
 * Re-using a range is only safe while the new import fits inside it. If the
 * source file grew, the next id would collide with the NEXT file's block and
 * silently corrupt two files at once. {@see allocate()} therefore throws the
 * moment an allocation would pass `$to`, rather than clamping or wrapping. The
 * caller is expected to let that exception abort the whole import — the seeder
 * runs inside one transaction so nothing is left half-written.
 *
 * Allocating FEWER ids than the range holds is fine and expected: the unused
 * tail is simply a gap, which costs nothing (a later rescan records the shorter
 * range) and never overlaps a neighbouring file.
 *
 * This class touches no database state. It hands out numbers; whether they are
 * actually free is established by the caller having deleted that range first.
 */
final class FixedRangeIdAllocator
{
    /**
     * Next id to hand out.
     */
    private int $cursor;

    /**
     * @param  int  $from  first id of the range, inclusive
     * @param  int  $to  last id of the range, inclusive
     *
     * @throws RuntimeException if the range is empty or inverted
     */
    public function __construct(
        private readonly int $from,
        private readonly int $to,
    ) {
        if ($from > $to) {
            throw new RuntimeException("Invalid id range: {$from}..{$to} (from is greater than to).");
        }

        $this->cursor = $from;
    }

    /**
     * @return list<int>
     *
     * @throws RuntimeException if the range cannot satisfy the request
     */
    public function allocate(int $count): array
    {
        if ($count < 1) {
            return [];
        }

        $last = $this->cursor + $count - 1;

        if ($last > $this->to) {
            throw new RuntimeException(sprintf(
                'ID RANGE EXHAUSTED: this import needs id %s but its range ends at %s '
                .'(range %s..%s, %s id(s) already allocated). The source file produces MORE rows '
                .'than the range it is being written back into. Nothing has been committed — the '
                .'import must be aborted and re-planned (import at the end of the sequence instead, '
                .'accepting that this file moves out of id order).',
                number_format($last),
                number_format($this->to),
                number_format($this->from),
                number_format($this->to),
                number_format($this->allocated()),
            ));
        }

        $ids = range($this->cursor, $last);
        $this->cursor = $last + 1;

        return $ids;
    }

    public function allocated(): int
    {
        return $this->cursor - $this->from;
    }

    public function capacity(): int
    {
        return $this->to - $this->from + 1;
    }

    public function remaining(): int
    {
        return $this->capacity() - $this->allocated();
    }

    public function from(): int
    {
        return $this->from;
    }

    public function to(): int
    {
        return $this->to;
    }

    /**
     * Last id actually handed out, or null when nothing has been allocated.
     */
    public function lastAllocated(): ?int
    {
        return $this->cursor === $this->from ? null : $this->cursor - 1;
    }
}
