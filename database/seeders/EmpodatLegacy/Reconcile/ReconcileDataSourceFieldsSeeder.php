<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use Database\Seeders\EmpodatLegacy\Backfill\BackfillContext;
use Database\Seeders\EmpodatLegacy\Backfill\BackfillOptions;
use Database\Seeders\EmpodatLegacy\Backfill\LookupBackfill;
use Database\Seeders\EmpodatLegacy\Backfill\PlanReport;

/**
 * Fills every empodat_data_sources column of the lookup backfill mapping where
 * NDS2.0 is empty (NULL or blank) and legacy dct_data_source has a value (not
 * blank, and greater than 0 for *_id columns). A non-empty NDS2.0 value is never
 * overwritten. Organisations and laboratories are never created: one missing in
 * NDS2.0 leaves the cell empty.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds -e RECONCILE_EXPECT_COUNT=<n> nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileDataSourceFieldsSeeder' --force
 */
final class ReconcileDataSourceFieldsSeeder extends ReconcileSeeder
{
    private const TABLE = 'empodat_data_sources';

    /** @var array<int, array<string, mixed>> data source id => column => new value */
    private array $changes = [];

    protected function plan(): int
    {
        $columns = $this->columns();

        $pgRows = [];
        foreach ($this->pg->table(self::TABLE)->get(['id', ...$columns]) as $row) {
            $pgRows[(int) $row->id] = $row;
        }

        $legacyIds = $this->legacy->table('dct_data_source')->pluck('id_data')
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
        $mapped = (new LookupBackfill($context))->dataSourceRowsForIds($legacyIds, false);

        $this->changes = [];
        $cells = array_fill_keys($columns, 0);
        foreach ($mapped as $row) {
            $id = (int) $row['id'];
            $set = [];
            foreach ($columns as $column) {
                if ($this->isEmpty($pgRows[$id]->{$column}) && $this->hasValue($column, $row[$column])) {
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
        $this->say('Laboratory cells skipped (laboratory missing in NDS2.0): '.$this->skippedLaboratories($mapped, $pgRows));
        $this->say('Example ids: '.implode(', ', array_slice(array_keys($this->changes), 0, 5)));

        return count($this->changes);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $started = microtime(true);

        $this->pg->transaction(function () use ($audit, $started): void {
            $current = $this->pg->table(self::TABLE)
                ->whereIn('id', array_keys($this->changes))
                ->lockForUpdate()
                ->get(['id', ...$this->columns()])
                ->keyBy('id');

            $ids = [];
            $olds = [];
            $filled = [];
            foreach ($this->changes as $id => $set) {
                $row = $current->get($id);
                if ($row === null) {
                    continue;
                }
                $stillEmpty = array_filter($set, fn (mixed $value, string $column): bool => $this->isEmpty($row->{$column}), ARRAY_FILTER_USE_BOTH);
                if ($stillEmpty === []) {
                    continue;
                }

                $this->pg->table(self::TABLE)->where('id', $id)->update($stillEmpty);
                $ids[] = $id;
                $filled += array_fill_keys(array_keys($stillEmpty), true);
                $olds[$id] = $row;
            }

            $loggedColumns = array_keys($filled);
            $before = [];
            foreach ($olds as $id => $row) {
                $before[] = ['id' => $id] + array_combine($loggedColumns, array_map(static fn (string $column): mixed => $row->{$column}, $loggedColumns));
            }

            $audit->logFill(
                'Fill data source fields from legacy',
                self::TABLE,
                $loggedColumns,
                $ids,
                $before,
                (int) ((microtime(true) - $started) * 1000),
            );
            $this->say('Rows changed: '.count($ids).' of '.count($this->changes).' planned.');
        });
    }

    protected function notes(): string
    {
        return 'Fill empty empodat_data_sources columns from legacy dct_data_source';
    }

    /** @return list<string> */
    private function columns(): array
    {
        return array_values(array_diff(LookupBackfill::SOURCE_COLUMNS, ['id', 'created_at', 'updated_at']));
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function hasValue(string $column, mixed $value): bool
    {
        if ($this->isEmpty($value)) {
            return false;
        }

        return ! str_ends_with($column, '_id') || (int) $value > 0;
    }

    /**
     * @param  list<array<string, mixed>>  $mapped
     * @param  array<int, object>  $pgRows
     */
    private function skippedLaboratories(array $mapped, array $pgRows): int
    {
        $names = [];
        foreach ($this->legacy->table('dct_data_source')->get(['id_data', 'laboratory_name', 'laboratory_name_2']) as $r) {
            $names[(int) $r->id_data] = ['laboratory1_id' => $r->laboratory_name, 'laboratory2_id' => $r->laboratory_name_2];
        }

        $skipped = 0;
        foreach ($mapped as $row) {
            $id = (int) $row['id'];
            foreach ($names[$id] ?? [] as $column => $name) {
                if (! $this->isEmpty($name) && $row[$column] === null && $this->isEmpty($pgRows[$id]->{$column})) {
                    $skipped++;
                }
            }
        }

        return $skipped;
    }
}
