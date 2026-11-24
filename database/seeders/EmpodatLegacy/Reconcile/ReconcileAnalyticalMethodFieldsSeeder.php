<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use Database\Seeders\EmpodatLegacy\Backfill\BackfillContext;
use Database\Seeders\EmpodatLegacy\Backfill\BackfillOptions;
use Database\Seeders\EmpodatLegacy\Backfill\LookupBackfill;
use Database\Seeders\EmpodatLegacy\Backfill\PlanReport;

/**
 * Fills every empodat_analytical_methods column of the lookup backfill mapping
 * where NDS2.0 is empty (NULL or blank) and legacy dct_analytical_method has a
 * value (not blank and not numeric zero: legacy stores 0 for "not given"). A
 * non-empty NDS2.0 value is never overwritten.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds -e RECONCILE_EXPECT_COUNT=<n> nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileAnalyticalMethodFieldsSeeder' --force
 */
final class ReconcileAnalyticalMethodFieldsSeeder extends ReconcileSeeder
{
    private const TABLE = 'empodat_analytical_methods';

    private const CHUNK = 1000;

    /** @var array<int, array<string, mixed>> method id => column => new value */
    private array $changes = [];

    protected function plan(): int
    {
        $columns = $this->columns();

        $pgRows = [];
        foreach ($this->pg->table(self::TABLE)->get(['id', ...$columns]) as $row) {
            $pgRows[(int) $row->id] = $row;
        }

        $legacyIds = $this->legacy->table('dct_analytical_method')->pluck('id_method')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => isset($pgRows[$id]))
            ->values()
            ->all();

        $context = new BackfillContext(
            BackfillOptions::fromEnvironment((array) config('empodat_legacy.backfill', [])),
            $this->legacy,
            $this->pg,
            new PlanReport($this->options->mode, false),
        );
        $mapped = (new LookupBackfill($context))->methodRowsForIds($legacyIds);

        $this->changes = [];
        $cells = array_fill_keys($columns, 0);
        foreach ($mapped as $row) {
            $id = (int) $row['id'];
            $set = [];
            foreach ($columns as $column) {
                if ($this->isEmpty($pgRows[$id]->{$column}) && $this->hasValue($row[$column])) {
                    $set[$column] = $row[$column];
                    $cells[$column]++;
                }
            }
            if ($set !== []) {
                $this->changes[$id] = $set;
            }
        }

        $this->say('Rows to change: '.count($this->changes));
        foreach (array_filter($cells) as $column => $count) {
            $this->say('  '.$column.': '.$count);
        }
        $this->say('Example ids: '.implode(', ', array_slice(array_keys($this->changes), 0, 5)));

        return count($this->changes);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $changed = 0;

        foreach (array_chunk($this->changes, self::CHUNK, true) as $chunk) {
            $started = microtime(true);

            $changed += $this->pg->transaction(function () use ($audit, $chunk, $started): int {
                $current = $this->pg->table(self::TABLE)
                    ->whereIn('id', array_keys($chunk))
                    ->lockForUpdate()
                    ->get(['id', ...$this->columns()])
                    ->keyBy('id');

                $olds = [];
                $filled = [];
                foreach ($chunk as $id => $set) {
                    $row = $current->get($id);
                    if ($row === null) {
                        continue;
                    }
                    $stillEmpty = array_filter($set, fn (mixed $value, string $column): bool => $this->isEmpty($row->{$column}), ARRAY_FILTER_USE_BOTH);
                    if ($stillEmpty === []) {
                        continue;
                    }

                    $this->pg->table(self::TABLE)->where('id', $id)->update($stillEmpty);
                    $filled += array_fill_keys(array_keys($stillEmpty), true);
                    $olds[$id] = $row;
                }

                if ($olds === []) {
                    return 0;
                }

                $loggedColumns = array_keys($filled);
                $before = [];
                foreach ($olds as $id => $row) {
                    $before[] = ['id' => $id] + array_combine($loggedColumns, array_map(static fn (string $column): mixed => $row->{$column}, $loggedColumns));
                }

                $audit->logFill(
                    'Fill analytical method fields from legacy',
                    self::TABLE,
                    $loggedColumns,
                    array_keys($olds),
                    $before,
                    (int) ((microtime(true) - $started) * 1000),
                );

                return count($olds);
            });
        }

        $this->say('Rows changed: '.$changed.' of '.count($this->changes).' planned.');
    }

    protected function notes(): string
    {
        return 'Fill empty empodat_analytical_methods columns from legacy dct_analytical_method';
    }

    /** @return list<string> */
    private function columns(): array
    {
        return array_values(array_diff(LookupBackfill::METHOD_COLUMNS, ['id', 'created_at', 'updated_at']));
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function hasValue(mixed $value): bool
    {
        return ! $this->isEmpty($value) && ! (is_numeric($value) && (float) $value === 0.0);
    }
}
