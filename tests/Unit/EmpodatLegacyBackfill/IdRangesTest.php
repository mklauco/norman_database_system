<?php

declare(strict_types=1);

namespace Tests\Unit\EmpodatLegacyBackfill;

use Database\Seeders\EmpodatLegacy\Backfill\IdRanges;
use Database\Seeders\EmpodatLegacy\Backfill\RunAccumulator;
use PHPUnit\Framework\TestCase;

class IdRangesTest extends TestCase
{
    public function test_differing_buckets_flags_count_checksum_and_absent_buckets_only_for_legacy_buckets(): void
    {
        $legacy = [
            0 => ['count' => 3, 'sum' => '6'],
            1 => ['count' => 2, 'sum' => '30'],
            2 => ['count' => 1, 'sum' => '7'],
            3 => ['count' => 4, 'sum' => '40'],
        ];
        $target = [
            0 => ['count' => 3, 'sum' => '6'],
            1 => ['count' => 2, 'sum' => '31'],
            3 => ['count' => 3, 'sum' => '40'],
            9 => ['count' => 5, 'sum' => '99'],
        ];

        $this->assertSame([1, 2, 3], IdRanges::differingBuckets($legacy, $target));
    }

    public function test_identical_buckets_do_not_differ(): void
    {
        $same = [0 => ['count' => 3, 'sum' => '6']];

        $this->assertSame([], IdRanges::differingBuckets($same, $same));
    }

    public function test_missing_runs_groups_consecutive_missing_legacy_ids(): void
    {
        $runs = IdRanges::missingRuns([1, 2, 3, 5, 6, 9], [2, 5]);

        $this->assertSame([[1, 1, 1], [3, 3, 1], [6, 9, 2]], $runs);
    }

    public function test_missing_runs_ignores_target_only_ids(): void
    {
        $runs = IdRanges::missingRuns([10, 11, 12], [1, 2, 11, 99]);

        $this->assertSame([[10, 10, 1], [12, 12, 1]], $runs);
    }

    public function test_missing_runs_is_empty_when_nothing_is_missing(): void
    {
        $this->assertSame([], IdRanges::missingRuns([1, 2, 3], [1, 2, 3, 4]));
        $this->assertSame([], IdRanges::missingRuns([], [1, 2]));
    }

    public function test_missing_ids_lists_every_absent_id(): void
    {
        $this->assertSame([1, 3, 6, 9], IdRanges::missingIds([1, 2, 3, 5, 6, 9], [2, 5]));
    }

    public function test_count_contains_and_format(): void
    {
        $runs = [[10, 20, 11], [30, 30, 1], [100, 105, 6]];

        $this->assertSame(18, IdRanges::countIds($runs));
        $this->assertTrue(IdRanges::contains($runs, 10));
        $this->assertTrue(IdRanges::contains($runs, 15));
        $this->assertTrue(IdRanges::contains($runs, 30));
        $this->assertTrue(IdRanges::contains($runs, 105));
        $this->assertFalse(IdRanges::contains($runs, 9));
        $this->assertFalse(IdRanges::contains($runs, 21));
        $this->assertFalse(IdRanges::contains($runs, 106));
        $this->assertFalse(IdRanges::contains([], 1));
        $this->assertSame('10-20 (11 ids), 30, ... and 1 more runs', IdRanges::format($runs, 2));
        $this->assertSame('(none)', IdRanges::format([]));
    }

    public function test_bucket_of_uses_integer_division(): void
    {
        $this->assertSame(163, IdRanges::bucketOf(163_702_564, 1_000_000));
        $this->assertSame(0, IdRanges::bucketOf(999_999, 1_000_000));
    }

    public function test_accumulator_glues_a_run_across_adjacent_buckets(): void
    {
        $accumulator = new RunAccumulator;
        $accumulator->add(0, [[7, 9, 3]], 1, 9);
        $accumulator->add(1, [[10, 12, 3], [20, 20, 1]], 10, 25);

        $this->assertSame([[7, 12, 6], [20, 20, 1]], $accumulator->runs());
    }

    public function test_accumulator_does_not_glue_when_the_next_bucket_starts_with_a_present_id(): void
    {
        $accumulator = new RunAccumulator;
        $accumulator->add(0, [[7, 9, 3]], 1, 9);
        $accumulator->add(1, [[12, 12, 1]], 10, 12);

        $this->assertSame([[7, 9, 3], [12, 12, 1]], $accumulator->runs());
    }

    public function test_accumulator_does_not_glue_across_a_skipped_bucket(): void
    {
        $accumulator = new RunAccumulator;
        $accumulator->add(0, [[7, 9, 3]], 1, 9);
        $accumulator->add(2, [[2_000_000, 2_000_001, 2]], 2_000_000, 2_000_005);

        $this->assertSame([[7, 9, 3], [2_000_000, 2_000_001, 2]], $accumulator->runs());
    }

    public function test_accumulator_does_not_glue_when_the_previous_bucket_ended_with_a_present_id(): void
    {
        $accumulator = new RunAccumulator;
        $accumulator->add(0, [[7, 8, 2]], 1, 9);
        $accumulator->add(1, [[10, 12, 3]], 10, 12);

        $this->assertSame([[7, 8, 2], [10, 12, 3]], $accumulator->runs());
    }

    public function test_capped_runs_without_a_cap_equal_the_full_runs(): void
    {
        $found = IdRanges::missingRunsCapped([1, 2, 3, 5, 6, 9], [2, 5], 0);

        $this->assertSame(IdRanges::missingRuns([1, 2, 3, 5, 6, 9], [2, 5]), $found['runs']);
        $this->assertSame(4, $found['kept']);
        $this->assertSame(4, $found['missing']);
    }

    public function test_capped_runs_keep_only_the_first_missing_ids_but_count_all_of_them(): void
    {
        $found = IdRanges::missingRunsCapped([1, 2, 3, 5, 6, 9], [2, 5], 2);

        $this->assertSame([[1, 1, 1], [3, 3, 1]], $found['runs']);
        $this->assertSame(2, $found['kept']);
        $this->assertSame(4, $found['missing']);
    }

    public function test_capped_runs_cut_inside_a_run(): void
    {
        $found = IdRanges::missingRunsCapped([10, 11, 12, 13, 14], [], 3);

        $this->assertSame([[10, 12, 3]], $found['runs']);
        $this->assertSame(3, IdRanges::countIds($found['runs']));
        $this->assertSame(5, $found['missing']);
    }

    public function test_capped_runs_with_a_cap_above_the_gap_keep_everything(): void
    {
        $found = IdRanges::missingRunsCapped([10, 11, 12], [11], 50);

        $this->assertSame([[10, 10, 1], [12, 12, 1]], $found['runs']);
        $this->assertSame(2, $found['kept']);
    }

    public function test_count_difference_never_goes_negative(): void
    {
        $this->assertSame(3, IdRanges::countDifference(['count' => 5, 'sum' => '1'], ['count' => 2, 'sum' => '1']));
        $this->assertSame(5, IdRanges::countDifference(['count' => 5, 'sum' => '1'], null));
        $this->assertSame(0, IdRanges::countDifference(['count' => 2, 'sum' => '1'], ['count' => 7, 'sum' => '1']));
    }
}
