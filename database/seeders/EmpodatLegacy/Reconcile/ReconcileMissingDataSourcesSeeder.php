<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use Database\Seeders\EmpodatLegacy\Backfill\BackfillContext;
use Database\Seeders\EmpodatLegacy\Backfill\BackfillOptions;
use Database\Seeders\EmpodatLegacy\Backfill\LookupBackfill;
use Database\Seeders\EmpodatLegacy\Backfill\PlanReport;

/**
 * Inserts the legacy data sources (dct_data_source) that empodat_data_sources
 * lacks, with the same id and the same mapping as the lookup backfill. Never
 * creates organisations or laboratories (throws if one would be needed) and
 * relinks no measurement; that waits for Lubos.
 *
 * Explicit ids can leave the id sequence behind: run `php artisan db:fix-sequences`
 * afterwards.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds -e RECONCILE_EXPECT_COUNT=4 nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileMissingDataSourcesSeeder' --force
 */
final class ReconcileMissingDataSourcesSeeder extends ReconcileSeeder
{
    private const STEP = 'Insert missing data sources';

    private const TABLE = 'empodat_data_sources';

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    protected function plan(): int
    {
        $legacyIds = $this->legacy->table('dct_data_source')->pluck('id_data')->map(static fn ($id): int => (int) $id)->all();
        $pgIds = $this->pg->table(self::TABLE)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $missing = array_values(array_diff($legacyIds, $pgIds));
        sort($missing);

        $context = new BackfillContext(
            BackfillOptions::fromEnvironment((array) config('empodat_legacy.backfill', [])),
            $this->legacy,
            $this->pg,
            new PlanReport($this->options->mode, false),
        );
        $this->rows = (new LookupBackfill($context))->dataSourceRowsForIds($missing);

        $this->say('Legacy data sources missing in NDS2.0: '.count($this->rows));
        foreach ($this->rows as $row) {
            $this->say(sprintf(
                '  %d  organisation_id=%s laboratory1_id=%s laboratory2_id=%s',
                $row['id'],
                $row['organisation_id'] ?? 'null',
                $row['laboratory1_id'] ?? 'null',
                $row['laboratory2_id'] ?? 'null',
            ));
        }

        return count($this->rows);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $started = microtime(true);

        $this->pg->transaction(function () use ($audit, $started): void {
            $this->pg->table(self::TABLE)->insert($this->rows);
            $ids = array_map(static fn (array $row): int => (int) $row['id'], $this->rows);
            $audit->logInsert(self::STEP, self::TABLE, $ids, (int) ((microtime(true) - $started) * 1000));
        });
    }

    protected function notes(): string
    {
        return 'Insert legacy data sources missing in empodat_data_sources';
    }
}
