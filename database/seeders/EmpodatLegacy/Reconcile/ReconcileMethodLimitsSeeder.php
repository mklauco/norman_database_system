<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

/**
 * LUBOS_ISSUES item 9 (decision 2026-11-26: legacy is authoritative). Copies
 * lod and loq of every analytical method from legacy dct_analytical_method
 * (am_lod, am_loq) wherever the legacy value is set and differs. A legacy NULL
 * is never written (only counted); a legacy 0 is skipped, as the import maps it to NULL. Rollback restores the old values.
 */
final class ReconcileMethodLimitsSeeder extends ReconcileSeeder
{
    private const STEP = 'Method LoD and LoQ from legacy';

    private const TABLE = 'empodat_analytical_methods';

    private const COLUMNS = ['lod' => 'am_lod', 'loq' => 'am_loq'];

    /** @var array<string, array<int, array{0: float|null, 1: float}>> column => id => [old, legacy] */
    private array $changes = [];

    protected function plan(): int
    {
        $legacy = [];
        foreach ($this->legacy->table('dct_analytical_method')->get(['id_method', 'am_lod', 'am_loq']) as $row) {
            $legacy[(int) $row->id_method] = $row;
        }
        $this->pauseLegacy();

        $this->changes = ['lod' => [], 'loq' => []];
        $legacyNull = ['lod' => 0, 'loq' => 0];
        foreach ($this->pg->table(self::TABLE)->get(['id', 'lod', 'loq']) as $row) {
            $l = $legacy[(int) $row->id] ?? null;
            if ($l === null) {
                continue;
            }
            foreach (self::COLUMNS as $column => $legacyColumn) {
                $old = $row->{$column} === null ? null : (float) $row->{$column};
                $new = $l->{$legacyColumn} === null ? null : (float) $l->{$legacyColumn};
                if ($new === 0.0) {
                    continue;
                }
                if ($new === null) {
                    if ($old !== null) {
                        $legacyNull[$column]++;
                    }

                    continue;
                }
                if ($old === null || abs($old - $new) > 1e-12 * max(1.0, abs($new))) {
                    $this->changes[$column][(int) $row->id] = [$old, $new];
                }
            }
        }

        foreach (self::COLUMNS as $column => $legacyColumn) {
            $this->say($column.': to change '.count($this->changes[$column]).', legacy NULL but NDS2.0 set (left) '.$legacyNull[$column]);
        }

        return count($this->changes['lod']) + count($this->changes['loq']);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $this->pg->transaction(function () use ($audit): void {
            foreach ($this->changes as $column => $changes) {
                if ($changes === []) {
                    continue;
                }
                $started = microtime(true);
                $before = [];
                foreach ($changes as $id => [$old, $new]) {
                    $this->pg->table(self::TABLE)->where('id', $id)->update([$column => $new]);
                    $before[] = ['id' => $id, $column => $old];
                }
                $audit->logFill(self::STEP, self::TABLE, [$column], array_keys($changes), $before, (int) ((microtime(true) - $started) * 1000));
                $this->say($column.' changed: '.count($changes));
            }
        });
    }

    protected function notes(): string
    {
        return 'lod and loq of empodat_analytical_methods from legacy dct_analytical_method';
    }
}
