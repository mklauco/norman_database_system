<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Factsheet\FactsheetStatisticsController;
use App\Models\Factsheet\FactsheetStatistic;
use App\Services\Factsheet\FactsheetStatisticsBulkBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Computes the factsheet statistics payload for each substance (#26).
 *
 * The "Populate all" button in the UI only inserts placeholder rows with a
 * null `meta_data` — it never computes anything, which is why 4 702 of the
 * 4 705 rows were empty and every occurrence section on the factsheet
 * rendered blank. This command fills them.
 *
 * Each substance costs roughly 2.5 seconds against `empodat_main`, so a full
 * run over ~4 700 substances takes around three hours. It is therefore
 * deliberately resumable and chunked: by default only substances whose
 * statistics are missing or stale are processed, so an interrupted run can be
 * restarted without repeating work.
 */
class GenerateFactsheetStatistics extends Command
{
    protected $signature = 'factsheets:generate-statistics
                            {--substance=* : Substance id to process; repeatable. Implies --per-substance}
                            {--all : Recompute every substance, including those already up to date}
                            {--limit= : Stop after this many substances; implies --per-substance}
                            {--per-substance : Compute one substance at a time instead of in bulk (slow)}';

    protected $description = 'Compute factsheet occurrence statistics for every substance';

    public function handle(
        FactsheetStatisticsController $controller,
        FactsheetStatisticsBulkBuilder $builder,
    ): int {
        // A whole-database rebuild goes through the bulk builder: six queries
        // grouped by substance, rather than six per substance. Measured at
        // about a minute and a half against 2.5 hours for ~7 600 substances.
        // The per-substance path stays for targeted regeneration.
        if (! $this->wantsPerSubstance()) {
            return $this->rebuildInBulk($builder);
        }

        $ids = $this->resolveSubstanceIds();

        if ($ids === []) {
            $this->info('Nothing to do — every requested substance already has current statistics.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Generating statistics for %s substance(s). Roughly %s at ~2.5s each.',
            number_format(count($ids)),
            $this->humanEta(count($ids))
        ));

        $bar = $this->output->createProgressBar(count($ids));
        $bar->start();

        $failed = 0;

        foreach ($ids as $id) {
            try {
                $controller->generateStatisticsForSubstance($id);
            } catch (Throwable $e) {
                $failed++;
                Log::error("factsheets:generate-statistics failed for substance {$id}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($failed > 0) {
            $this->warn("{$failed} substance(s) failed; see the log.");
        }

        $this->info('Done.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * The per-substance path is only for targeted work: specific substances,
     * a capped run, or an explicit request for it.
     */
    private function wantsPerSubstance(): bool
    {
        return (bool) $this->option('per-substance')
            || (array) $this->option('substance') !== []
            || $this->option('limit') !== null;
    }

    private function rebuildInBulk(FactsheetStatisticsBulkBuilder $builder): int
    {
        $this->info('Rebuilding statistics for every substance in EMPODAT (bulk mode).');

        $bar = null;
        $t0 = microtime(true);

        try {
            $written = $builder->rebuildAll(function (int $done, int $total) use (&$bar) {
                if ($bar === null) {
                    $bar = $this->output->createProgressBar($total);
                    $bar->start();
                }

                $bar->setProgress($done);
            });
        } catch (Throwable $e) {
            Log::error('factsheets:generate-statistics bulk rebuild failed: '.$e->getMessage());
            $this->error('Bulk rebuild failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $bar?->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'Wrote statistics for %s substance(s) in %.1f s.',
            number_format($written),
            microtime(true) - $t0
        ));

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function resolveSubstanceIds(): array
    {
        $explicit = array_map('intval', (array) $this->option('substance'));

        if ($explicit !== []) {
            return $explicit;
        }

        // Driven off the substances that actually appear in EMPODAT, NOT off
        // the rows that happen to exist in `factsheet_substance_statistics`.
        // The two sets differ sharply: 7 592 substances carry occurrence data
        // while only 4 705 statistics rows exist, so iterating the statistics
        // table would silently skip 2 888 substances and leave their
        // factsheets blank after a full run.
        $ids = DB::table('empodat_main')
            ->whereNotNull('substance_id')
            ->distinct()
            ->orderBy('substance_id')
            ->pluck('substance_id')
            ->map(fn ($id) => (int) $id);

        if (! $this->option('all')) {
            // Already carrying the surface-water occurrence block, so current.
            // `jsonb_exists()` rather than the `?` operator: Laravel reads a
            // literal `?` in raw SQL as a binding placeholder.
            $current = FactsheetStatistic::whereNotNull('meta_data')
                ->whereRaw("jsonb_exists(meta_data::jsonb, 'surface_water_occurrence')")
                ->pluck('substance_id')
                ->flip();

            $ids = $ids->reject(fn (int $id) => $current->has($id))->values();
        }

        if ($limit = $this->option('limit')) {
            $ids = $ids->take((int) $limit);
        }

        return $ids->all();
    }

    private function humanEta(int $count): string
    {
        $seconds = $count * 2.5;

        return $seconds < 90
            ? round($seconds).' seconds'
            : ($seconds < 5400 ? round($seconds / 60).' minutes' : round($seconds / 3600, 1).' hours');
    }
}
