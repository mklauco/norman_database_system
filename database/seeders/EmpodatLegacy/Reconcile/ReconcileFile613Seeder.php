<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use RuntimeException;

/**
 * LUBOS_ISSUES item 2 (decision 2026-11-26: keep 613, delete 481). Legacy file
 * 613 (v3) reuses the ids 159787056-159788018 of file 481 (v2) with other data;
 * NDS2.0 still holds the v2 rows there under file 481. This seeder deletes those
 * rows from empodat_main, empodat_minor and every empodat_matrix_* table (each
 * delete logged with the full row, so rollback re-inserts them). The legacy
 * backfill then inserts the file 613 rows on the same ids; EmpodatReconcileBatch2Seeder
 * runs both in that order.
 *
 * Refuses to write unless legacy has exactly those ids under list_id 613.
 */
final class ReconcileFile613Seeder extends ReconcileSeeder
{
    public const ID_FROM = 159787056;

    public const ID_TO = 159788018;

    private const STEP = 'Delete file 481 rows replaced by file 613';

    private const OLD_FILE = 481;

    private const NEW_FILE = 613;

    /** @var list<int> */
    private array $ids = [];

    protected function plan(): int
    {
        $this->ids = $this->pg->table('empodat_main')
            ->whereBetween('id', [self::ID_FROM, self::ID_TO])
            ->where('file_id', self::OLD_FILE)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $legacy = (int) $this->legacy->table('dct_analysis')
            ->whereBetween('id', [self::ID_FROM, self::ID_TO])
            ->where('list_id', self::NEW_FILE)
            ->count();
        $legacyTotal = (int) $this->legacy->table('dct_analysis')
            ->whereBetween('id', [self::ID_FROM, self::ID_TO])
            ->count();

        $this->say('NDS2.0 rows of file '.self::OLD_FILE.' in '.self::ID_FROM.'-'.self::ID_TO.': '.count($this->ids));
        $this->say('Legacy rows in that range: '.$legacyTotal.', of them under file '.self::NEW_FILE.': '.$legacy);

        if ($this->ids !== [] && ($legacy !== $legacyTotal || $legacy !== self::ID_TO - self::ID_FROM + 1)) {
            throw new RuntimeException('Legacy does not hold the whole range under file '.self::NEW_FILE.'; nothing written.');
        }

        return count($this->ids);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        foreach ([...MatrixTables::names(), 'empodat_minor', 'empodat_main'] as $table) {
            $ids = $this->pg->table($table)
                ->whereIn('id', $this->ids)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
            if ($ids !== []) {
                MatrixTables::deleteLogged($this->pg, $audit, self::STEP, $table, $ids);
                $this->say('Deleted from '.$table.': '.count($ids));
            }
        }
    }

    protected function notes(): string
    {
        return 'delete the file 481 rows on ids '.self::ID_FROM.'-'.self::ID_TO.' before the backfill loads file 613';
    }
}
