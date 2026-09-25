<?php

declare(strict_types=1);

namespace Tests\Unit\EmpodatSuspect;

use App\Services\EmpodatSuspect\FixedRangeIdAllocator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The allocator's ceiling is the safety mechanism of the CONNECT 2 re-import:
 * it is what stops an oversized spreadsheet from writing past its own id block
 * and into the next file's. These tests pin that behaviour down.
 */
class FixedRangeIdAllocatorTest extends TestCase
{
    public function test_it_allocates_consecutive_ids_from_the_start_of_the_range(): void
    {
        $allocator = new FixedRangeIdAllocator(100, 109);

        $this->assertSame([100, 101, 102], $allocator->allocate(3));
        $this->assertSame([103, 104], $allocator->allocate(2));
        $this->assertSame(5, $allocator->allocated());
        $this->assertSame(104, $allocator->lastAllocated());
    }

    public function test_it_reports_capacity_and_remaining(): void
    {
        $allocator = new FixedRangeIdAllocator(6414447, 7180350);

        $this->assertSame(765904, $allocator->capacity());
        $this->assertSame(765904, $allocator->remaining());
        $this->assertNull($allocator->lastAllocated());

        $allocator->allocate(4000);

        $this->assertSame(4000, $allocator->allocated());
        $this->assertSame(761904, $allocator->remaining());
    }

    public function test_it_allows_an_allocation_that_exactly_fills_the_range(): void
    {
        $allocator = new FixedRangeIdAllocator(1, 4);

        $this->assertSame([1, 2, 3, 4], $allocator->allocate(4));
        $this->assertSame(0, $allocator->remaining());
    }

    public function test_it_throws_when_an_allocation_would_pass_the_end_of_the_range(): void
    {
        $allocator = new FixedRangeIdAllocator(1, 4);
        $allocator->allocate(3);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ID RANGE EXHAUSTED/');

        $allocator->allocate(2);
    }

    public function test_the_failed_allocation_hands_out_nothing(): void
    {
        $allocator = new FixedRangeIdAllocator(1, 4);
        $allocator->allocate(4);

        try {
            $allocator->allocate(1);
            $this->fail('Expected the allocator to refuse.');
        } catch (RuntimeException) {
            // The cursor must not have moved: a refused allocation is not a
            // partial one, so a caller that catches and retries cannot end up
            // with ids outside the range.
            $this->assertSame(4, $allocator->allocated());
            $this->assertSame(4, $allocator->lastAllocated());
        }
    }

    public function test_a_single_id_range_is_valid(): void
    {
        $allocator = new FixedRangeIdAllocator(7, 7);

        $this->assertSame([7], $allocator->allocate(1));
        $this->assertSame(0, $allocator->remaining());
    }

    public function test_allocating_nothing_is_a_no_op(): void
    {
        $allocator = new FixedRangeIdAllocator(1, 4);

        $this->assertSame([], $allocator->allocate(0));
        $this->assertSame(0, $allocator->allocated());
    }

    public function test_it_rejects_an_inverted_range(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Invalid id range/');

        new FixedRangeIdAllocator(10, 9);
    }
}
