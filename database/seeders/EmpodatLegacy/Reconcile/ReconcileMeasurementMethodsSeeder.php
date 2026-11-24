<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

/**
 * Fills empodat_main.method_id where it is NULL and legacy dct_analysis.id_method
 * holds a method. Never overwrites a non-empty value.
 *
 * Skipped and reported: legacy id_method 0 or NULL (no method), and legacy
 * id_method that does not exist in empodat_analytical_methods (dangling).
 *
 * Dry run: the same command without the -e flags.
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds -e RECONCILE_EXPECT_COUNT=<n> nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileMeasurementMethodsSeeder' --force
 */
final class ReconcileMeasurementMethodsSeeder extends ReconcileSeeder
{
    private const STEP = 'Fill measurement method';

    private const TABLE = 'empodat_main';

    private const LEGACY_CHUNK = 1000;

    private const WRITE_CHUNK = 5000;

    private const EXAMPLES = 5;

    /** @var array<int, int> empodat_main.id => method id to write */
    private array $work = [];

    protected function plan(): int
    {
        $this->work = [];

        $emptyIds = array_map(
            static fn (object $row): int => (int) $row->id,
            $this->pg->select('SELECT id FROM '.self::TABLE.' WHERE method_id IS NULL ORDER BY id'),
        );

        $methodIds = [];
        foreach ($this->pg->select('SELECT id FROM empodat_analytical_methods') as $row) {
            $methodIds[(int) $row->id] = true;
        }

        $skips = ['legacy has no method (0/NULL)' => [], 'legacy method not in empodat_analytical_methods' => [], 'id missing in legacy' => []];

        foreach (array_chunk($emptyIds, self::LEGACY_CHUNK) as $chunk) {
            $found = [];
            $rows = $this->legacy->select(
                'SELECT id, id_method FROM dct_analysis WHERE id IN ('.implode(',', array_fill(0, count($chunk), '?')).')',
                $chunk,
            );
            $this->pauseLegacy();

            foreach ($rows as $row) {
                $id = (int) $row->id;
                $method = (int) $row->id_method;
                $found[$id] = true;

                if ($method <= 0) {
                    $skips['legacy has no method (0/NULL)'][] = $id;
                } elseif (! isset($methodIds[$method])) {
                    $skips['legacy method not in empodat_analytical_methods'][] = $id;
                } else {
                    $this->work[$id] = $method;
                }
            }
            foreach ($chunk as $id) {
                if (! isset($found[$id])) {
                    $skips['id missing in legacy'][] = $id;
                }
            }
        }

        $this->say('NDS2.0 empty rows: '.count($emptyIds));
        $this->say('Rows to fill: '.count($this->work));
        foreach ($skips as $reason => $ids) {
            $this->say('Skipped, '.$reason.': '.count($ids).($ids === [] ? '' : ' (e.g. '.implode(', ', array_slice($ids, 0, self::EXAMPLES)).')'));
        }

        return count($this->work);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $written = 0;

        foreach (array_chunk($this->work, self::WRITE_CHUNK, true) as $chunk) {
            $this->pg->transaction(function () use ($audit, $chunk, &$written): void {
                $startedAt = hrtime(true);
                $placeholders = [];
                $bindings = [];
                foreach ($chunk as $id => $method) {
                    $placeholders[] = $bindings === [] ? '(?::bigint, ?::bigint)' : '(?, ?)';
                    array_push($bindings, $id, $method);
                }

                $returned = $this->pg->select(
                    'UPDATE '.self::TABLE.' AS t SET method_id = v.val '
                    .'FROM (VALUES '.implode(', ', $placeholders).') AS v(id, val) '
                    .'WHERE t.id = v.id AND t.method_id IS NULL RETURNING t.id',
                    $bindings,
                );
                $ids = array_map(static fn (object $row): int => (int) $row->id, $returned);

                $audit->logFill(self::STEP, self::TABLE, ['method_id'], $ids, null, (int) ((hrtime(true) - $startedAt) / 1_000_000));
                $written += count($ids);
            });
            $this->say('Written '.$written.' / '.count($this->work));
        }

        if ($this->options->expectCount !== null && $written !== $this->options->expectCount) {
            $this->say('WARNING: wrote '.$written.' rows but the dry run planned '.$this->options->expectCount.'.');
        }
    }

    protected function notes(): string
    {
        return 'empodat_main.method_id from legacy dct_analysis.id_method where NDS2.0 is NULL';
    }
}
