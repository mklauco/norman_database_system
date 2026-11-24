<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

/**
 * Fills email and author in empodat_data_sources where NDS2.0 is empty (NULL or
 * blank) and legacy dct_data_source has a value (trimmed, blank = none). A
 * non-empty NDS2.0 value is never overwritten.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds -e RECONCILE_EXPECT_COUNT=<n> nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileDataSourceContactsSeeder' --force
 */
final class ReconcileDataSourceContactsSeeder extends ReconcileSeeder
{
    private const COLUMNS = ['email', 'author'];

    private const TABLE = 'empodat_data_sources';

    /** @var array<int, array<string, string>> data source id => column => new value */
    private array $changes = [];

    protected function plan(): int
    {
        $pgRows = [];
        foreach ($this->pg->select('SELECT id, email, author FROM empodat_data_sources') as $row) {
            $pgRows[(int) $row->id] = $row;
        }

        $cells = ['email' => 0, 'author' => 0];
        $conflicts = 0;
        $this->changes = [];

        foreach ($this->legacy->select('SELECT id_data, email, author FROM dct_data_source') as $legacyRow) {
            $id = (int) $legacyRow->id_data;
            if (! isset($pgRows[$id])) {
                continue;
            }

            $set = [];
            foreach (self::COLUMNS as $column) {
                $legacyValue = $this->blankToNull($legacyRow->{$column});
                $pgValue = $this->blankToNull($pgRows[$id]->{$column});
                if ($legacyValue === null) {
                    continue;
                }
                if ($pgValue === null) {
                    $set[$column] = $legacyValue;
                    $cells[$column]++;
                } elseif ($pgValue !== $legacyValue) {
                    $conflicts++;
                }
            }

            if ($set !== []) {
                $this->changes[$id] = $set;
            }
        }

        $this->say('Rows to change: '.count($this->changes));
        $this->say('Cells to fill: email '.$cells['email'].', author '.$cells['author']);
        $this->say('Both sides non-empty but different (not changed): '.$conflicts);
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
                ->get(['id', 'email', 'author'])
                ->keyBy('id');

            $ids = [];
            $before = [];
            foreach ($this->changes as $id => $set) {
                $row = $current->get($id);
                if ($row === null) {
                    continue;
                }
                $stillEmpty = array_filter(
                    $set,
                    fn (string $value, string $column): bool => $this->blankToNull($row->{$column}) === null,
                    ARRAY_FILTER_USE_BOTH,
                );
                if ($stillEmpty === []) {
                    continue;
                }

                $this->pg->table(self::TABLE)->where('id', $id)->update($stillEmpty);
                $ids[] = $id;
                $before[] = ['id' => $id, 'email' => $row->email, 'author' => $row->author];
            }

            $audit->logFill(
                'Fill email/author from legacy',
                self::TABLE,
                self::COLUMNS,
                $ids,
                $before,
                (int) ((microtime(true) - $started) * 1000),
            );
            $this->say('Rows changed: '.count($ids).' of '.count($this->changes).' planned.');
        });
    }

    protected function notes(): string
    {
        return 'Fill empty email/author in empodat_data_sources from legacy';
    }

    private function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
