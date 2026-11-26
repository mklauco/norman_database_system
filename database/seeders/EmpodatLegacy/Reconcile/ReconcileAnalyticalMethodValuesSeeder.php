<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use RuntimeException;

/**
 * Copies 45 legacy values into empodat_analytical_methods (sub-problem 1b.11):
 * - uncertainty_loq of 40 methods (files 266, 276), rounded to two decimals by
 *   the old numeric(8,2) column; needs migration 2026_11_26_100133 (double precision);
 * - rating of 5 methods of file 681 (legacy 24, NDS2.0 30);
 * - uncertainty_loq_range_max of method 176971937 (legacy 17, NDS2.0 15).
 *
 * A value is written only where it still equals the listed old value; a value
 * already equal to the legacy one counts as done. A missing method or any other
 * value stops the run before anything is written. Rollback restores the old values.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileAnalyticalMethodValuesSeeder' --force
 */
final class ReconcileAnalyticalMethodValuesSeeder extends ReconcileSeeder
{
    private const STEP = 'Analytical method values';

    private const TABLE = 'empodat_analytical_methods';

    /**
     * Column => method id => [old NDS2.0 value, legacy value].
     *
     * @var array<string, array<int, array{0: float|int, 1: float|int}>>
     */
    private const VALUES = [
        'uncertainty_loq' => [
            13290433 => [0.00, 0.003],
            13290434 => [0.03, 0.025],
            13290435 => [0.02, 0.023],
            13290436 => [0.01, 0.007],
            13290437 => [0.01, 0.013],
            13290438 => [0.01, 0.005],
            13290439 => [0.01, 0.009],
            13290441 => [0.01, 0.012],
            13290442 => [0.02, 0.016],
            13290443 => [0.10, 0.097],
            13290444 => [0.20, 0.195],
            13290445 => [0.07, 0.068],
            13290446 => [0.04, 0.044],
            13290449 => [0.58, 0.577],
            13359242 => [0.00, 0.003],
            13359243 => [0.03, 0.025],
            13359244 => [0.02, 0.023],
            13359245 => [0.01, 0.007],
            13359246 => [0.01, 0.013],
            13359247 => [0.01, 0.005],
            13359248 => [0.01, 0.009],
            13359250 => [0.01, 0.012],
            13359251 => [0.02, 0.016],
            13359252 => [0.01, 0.005],
            13359253 => [0.10, 0.097],
            13359254 => [0.20, 0.195],
            13359255 => [0.07, 0.068],
            13359256 => [0.04, 0.044],
            13359257 => [0.10, 0.097],
            13359261 => [0.58, 0.577],
            13359262 => [0.01, 0.013],
            13359263 => [0.20, 0.195],
            13359265 => [0.00, 0.003],
            13359266 => [0.07, 0.068],
            13359267 => [0.04, 0.044],
            13359269 => [0.58, 0.577],
            13359270 => [0.01, 0.009],
            13359271 => [0.01, 0.013],
            13359272 => [0.58, 0.577],
            13359273 => [0.02, 0.023],
        ],
        'rating' => [
            176971934 => [30, 24],
            176971935 => [30, 24],
            176971936 => [30, 24],
            176971937 => [30, 24],
            176971938 => [30, 24],
        ],
        'uncertainty_loq_range_max' => [
            176971937 => [15, 17],
        ],
    ];

    /** @var array<string, list<int>> */
    private array $toChange = [];

    protected function plan(): int
    {
        [$this->toChange, $done, $problems] = $this->classify(false);

        $planned = array_sum(array_map('count', $this->toChange));
        foreach (self::VALUES as $column => $values) {
            $this->say($column.': listed '.count($values).', to change '.count($this->toChange[$column]).', already done '.$done[$column]);
        }
        if ($problems !== []) {
            $this->say('Missing or other value: '.implode(', ', $problems));
        }

        return $planned;
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $this->pg->transaction(function () use ($audit): void {
            [$toChange, , $problems] = $this->classify(true);
            if ($problems !== []) {
                throw new RuntimeException('Methods missing or with another value than listed, nothing written: '.implode(', ', $problems));
            }

            $written = 0;
            foreach ($toChange as $column => $ids) {
                $started = microtime(true);
                $before = [];
                foreach ($ids as $id) {
                    [$old, $legacy] = self::VALUES[$column][$id];
                    $before[] = ['id' => $id, $column => $old];
                    $written += $this->pg->table(self::TABLE)->where('id', $id)->update([$column => $legacy]);
                }
                if ($ids !== []) {
                    $audit->logFill(self::STEP, self::TABLE, [$column], $ids, $before, (int) ((microtime(true) - $started) * 1000));
                }
            }

            $this->say('Values changed: '.$written);
        });
    }

    protected function notes(): string
    {
        return 'uncertainty_loq, rating and uncertainty_loq_range_max of 45 analytical methods from legacy';
    }

    /**
     * @return array{0: array<string, list<int>>, 1: array<string, int>, 2: list<string>}
     */
    private function classify(bool $lock): array
    {
        $ids = [];
        foreach (self::VALUES as $values) {
            $ids = array_merge($ids, array_keys($values));
        }

        $query = $this->pg->table(self::TABLE)->whereIn('id', array_unique($ids));
        if ($lock) {
            $query->lockForUpdate();
        }
        $current = $query->get(['id', ...array_keys(self::VALUES)])->keyBy('id');

        $toChange = [];
        $done = [];
        $problems = [];
        foreach (self::VALUES as $column => $values) {
            $toChange[$column] = [];
            $done[$column] = 0;
            foreach ($values as $id => [$old, $legacy]) {
                $row = $current->get($id);
                $value = $row?->{$column};
                if ($row !== null && $this->same($value, $old)) {
                    $toChange[$column][] = $id;
                } elseif ($row !== null && $this->same($value, $legacy)) {
                    $done[$column]++;
                } else {
                    $problems[] = $id.' '.$column.'='.var_export($value, true);
                }
            }
        }

        return [$toChange, $done, $problems];
    }

    private function same(mixed $value, float|int $expected): bool
    {
        return $value !== null && is_numeric($value) && abs((float) $value - $expected) < 1e-9;
    }
}
