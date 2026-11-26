<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

/**
 * LUBOS_ISSUES item 9 (decision 2026-11-26: legacy is authoritative). Walks all
 * of empodat_main and, where legacy dct_analysis has the same id, writes the
 * legacy value of five columns wherever it differs:
 *
 * - matrix_id <- matrix, when the legacy matrix exists in list_matrices and is
 *   stored in the same empodat_matrix_* table as the current one (otherwise
 *   counted and skipped, because the matrix details would sit in the wrong table);
 * - sampling_date_year <- sampling_date_y;
 * - concentration_indicator_id <- dic_id, when it exists in list_concentration_indicators;
 * - method_id <- id_method, when it exists in empodat_analytical_methods
 *   (methods that exist nowhere: LUBOS_ISSUES item 8);
 * - substance_id <- sus_id via susdat_substances.code: one match is written; a
 *   code with no substance writes NULL (decision 2026-11-26); a code with several
 *   substances is skipped (LUBOS_ISSUES item 7).
 *
 * A legacy value that is NULL or 0 is never written. Every chunk is one
 * transaction with one audit row holding the old values of all five columns,
 * so `php artisan empodat-reconcile:rollback {run}` restores them. A second run
 * changes nothing. RECONCILE_ID_FROM / RECONCILE_ID_TO limit the walk.
 */
final class ReconcileMeasurementValuesSeeder extends ReconcileSeeder
{
    private const STEP = 'Measurement values from legacy';

    private const TABLE = 'empodat_main';

    private const CHUNK = 20000;

    private const SUB_BATCH = 5000;

    private const PROGRESS_SECONDS = 30.0;

    private const COLUMNS = ['matrix_id', 'sampling_date_year', 'concentration_indicator_id', 'method_id', 'substance_id'];

    /** @var array<int, string> matrix id => empodat_matrix_* table */
    private array $matrixTable = [];

    /** @var array<int, true> */
    private array $indicators = [];

    /** @var array<int, true> */
    private array $methods = [];

    /** @var array<string, list<int>> 8-digit code => substance ids */
    private array $substances = [];

    /** @var array<string, int> */
    private array $counts = [];

    protected function plan(): int
    {
        return $this->walk(null);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $this->walk($audit);
    }

    protected function countsBeforeWriting(): bool
    {
        return false;
    }

    protected function notes(): string
    {
        return 'matrix, year, indicator, method and substance of empodat_main from legacy dct_analysis';
    }

    private function loadLookups(): void
    {
        foreach ($this->pg->table('list_matrices')->get(['id', 'empodat_matrix_link']) as $row) {
            $this->matrixTable[(int) $row->id] = (string) $row->empodat_matrix_link;
        }
        foreach ($this->pg->table('list_concentration_indicators')->pluck('id') as $id) {
            $this->indicators[(int) $id] = true;
        }
        foreach ($this->pg->table('empodat_analytical_methods')->pluck('id') as $id) {
            $this->methods[(int) $id] = true;
        }
        foreach ($this->pg->table('susdat_substances')->get(['id', 'code']) as $row) {
            $code = preg_replace('/\D/', '', (string) $row->code);
            if ($code !== '') {
                $this->substances[str_pad($code, 8, '0', STR_PAD_LEFT)][] = (int) $row->id;
            }
        }
    }

    private function walk(?ReconcileAudit $audit): int
    {
        $this->loadLookups();
        $this->counts = array_fill_keys([
            'rows', 'changed rows', ...self::COLUMNS,
            'matrix skipped (other matrix table)', 'matrix skipped (unknown)', 'indicator skipped (unknown)',
            'method skipped (exists nowhere)', 'substance set NULL (code not in SusDat)', 'substance skipped (ambiguous code)',
        ], 0);

        $bounds = $this->pg->selectOne('SELECT MIN(id) AS lo, MAX(id) AS hi FROM '.self::TABLE);
        if ($bounds === null || $bounds->lo === null) {
            return 0;
        }
        $from = max((int) $bounds->lo, $this->options->idFrom ?? PHP_INT_MIN);
        $to = min((int) $bounds->hi, $this->options->idTo ?? PHP_INT_MAX);
        $this->say(($audit === null ? 'Scanning' : 'Writing').' ids '.$from.' .. '.$to.'.');

        $startedAt = microtime(true);
        $lastReport = $startedAt;
        $after = $from - 1;

        while (true) {
            $pgRows = $this->pg->select(
                'SELECT id, '.implode(', ', self::COLUMNS).' FROM '.self::TABLE.' WHERE id > ? AND id <= ? ORDER BY id LIMIT '.self::CHUNK,
                [$after, $to],
            );
            if ($pgRows === []) {
                break;
            }
            $lo = (int) $pgRows[0]->id;
            $hi = (int) $pgRows[count($pgRows) - 1]->id;
            $after = $hi;
            $this->counts['rows'] += count($pgRows);

            $changes = $this->changes($pgRows, $lo, $hi);
            if ($audit !== null && $changes !== []) {
                $this->write($audit, $changes);
            }

            $now = microtime(true);
            if ($now - $lastReport >= self::PROGRESS_SECONDS) {
                $this->say(sprintf('at id %d (%.1f%%), %d rows/s, changed rows %d', $hi, 100 * ($hi - $from + 1) / max(1, $to - $from + 1), (int) ($this->counts['rows'] / ($now - $startedAt)), $this->counts['changed rows']));
                $lastReport = $now;
            }
        }

        foreach ($this->counts as $label => $count) {
            $this->say(str_pad($label, 42).number_format($count, 0, '.', ' '));
        }

        return $this->counts['changed rows'];
    }

    /**
     * @param  list<object>  $pgRows
     * @return list<array{id: int, before: array<string, mixed>, after: array<string, mixed>}>
     */
    private function changes(array $pgRows, int $lo, int $hi): array
    {
        $legacy = [];
        foreach ($this->legacy->select(
            'SELECT id, sus_id, matrix, dic_id, sampling_date_y, id_method FROM dct_analysis WHERE id BETWEEN ? AND ?',
            [$lo, $hi],
        ) as $row) {
            $legacy[(int) $row->id] = $row;
        }
        $this->pauseLegacy();

        $changes = [];
        foreach ($pgRows as $row) {
            $l = $legacy[(int) $row->id] ?? null;
            if ($l === null) {
                continue;
            }

            $before = [];
            foreach (self::COLUMNS as $column) {
                $before[$column] = $row->{$column} === null ? null : (int) $row->{$column};
            }
            $after = $before;

            $matrix = (int) $l->matrix;
            if ($matrix > 0 && $matrix !== $before['matrix_id']) {
                if (! isset($this->matrixTable[$matrix])) {
                    $this->counts['matrix skipped (unknown)']++;
                } elseif ($before['matrix_id'] !== null && ($this->matrixTable[$before['matrix_id']] ?? null) !== $this->matrixTable[$matrix]) {
                    $this->counts['matrix skipped (other matrix table)']++;
                } else {
                    $after['matrix_id'] = $matrix;
                }
            }

            $year = (int) $l->sampling_date_y;
            if ($year > 0 && $year !== $before['sampling_date_year']) {
                $after['sampling_date_year'] = $year;
            }

            $indicator = (int) $l->dic_id;
            if ($indicator > 0 && $indicator !== $before['concentration_indicator_id']) {
                if (isset($this->indicators[$indicator])) {
                    $after['concentration_indicator_id'] = $indicator;
                } else {
                    $this->counts['indicator skipped (unknown)']++;
                }
            }

            $method = (int) $l->id_method;
            if ($method > 0 && $method !== $before['method_id']) {
                if (isset($this->methods[$method])) {
                    $after['method_id'] = $method;
                } else {
                    $this->counts['method skipped (exists nowhere)']++;
                }
            }

            $susId = (int) $l->sus_id;
            if ($susId > 0) {
                $candidates = $this->substances[str_pad((string) $susId, 8, '0', STR_PAD_LEFT)] ?? [];
                if ($candidates === []) {
                    if ($before['substance_id'] !== null) {
                        $after['substance_id'] = null;
                        $this->counts['substance set NULL (code not in SusDat)']++;
                    }
                } elseif (count($candidates) > 1) {
                    if (! in_array($before['substance_id'], $candidates, true)) {
                        $this->counts['substance skipped (ambiguous code)']++;
                    }
                } elseif ($candidates[0] !== $before['substance_id']) {
                    $after['substance_id'] = $candidates[0];
                }
            }

            if ($after === $before) {
                continue;
            }
            foreach (self::COLUMNS as $column) {
                if ($after[$column] !== $before[$column]) {
                    $this->counts[$column]++;
                }
            }
            $this->counts['changed rows']++;
            $changes[] = ['id' => (int) $row->id, 'before' => $before, 'after' => $after];
        }

        return $changes;
    }

    /**
     * @param  list<array{id: int, before: array<string, mixed>, after: array<string, mixed>}>  $changes
     */
    private function write(ReconcileAudit $audit, array $changes): void
    {
        $this->pg->transaction(function () use ($audit, $changes): void {
            $started = hrtime(true);
            $ids = [];
            $before = [];

            foreach (array_chunk($changes, self::SUB_BATCH) as $batch) {
                $placeholders = [];
                $bindings = [];
                foreach ($batch as $index => $change) {
                    $placeholders[] = $index === 0
                        ? '(?::bigint, ?::bigint, ?::smallint, ?::bigint, ?::bigint, ?::bigint)'
                        : '(?, ?, ?, ?, ?, ?)';
                    $bindings[] = $change['id'];
                    foreach (self::COLUMNS as $column) {
                        $bindings[] = $change['after'][$column];
                    }
                }

                $returned = $this->pg->select(
                    'UPDATE '.self::TABLE.' AS t SET matrix_id = v.matrix_id, sampling_date_year = v.sampling_date_year, '
                    .'concentration_indicator_id = v.concentration_indicator_id, method_id = v.method_id, substance_id = v.substance_id '
                    .'FROM (VALUES '.implode(', ', $placeholders).') AS v(id, '.implode(', ', self::COLUMNS).') '
                    .'WHERE t.id = v.id RETURNING t.id',
                    $bindings,
                );
                $written = array_flip(array_map(static fn (object $row): int => (int) $row->id, $returned));

                foreach ($batch as $change) {
                    if (isset($written[$change['id']])) {
                        $ids[] = $change['id'];
                        $before[] = ['id' => $change['id'], ...$change['before']];
                    }
                }
            }

            $audit->logFill(self::STEP, self::TABLE, self::COLUMNS, $ids, $before, (int) ((hrtime(true) - $started) / 1_000_000));
        });
    }
}
