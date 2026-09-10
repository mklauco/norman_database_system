<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Factsheet\FactsheetStatisticsController;
use App\Models\Factsheet\FactsheetStatistic;
use Illuminate\Console\Command;
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
                            {--substance=* : Substance id to process; repeatable. Omit to process all}
                            {--all : Recompute every substance, including those already up to date}
                            {--limit= : Stop after this many substances}';

    protected $description = 'Compute factsheet occurrence statistics per substance (slow; ~2.5s each)';

    public function handle(FactsheetStatisticsController $controller): int
    {
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
     * @return list<int>
     */
    private function resolveSubstanceIds(): array
    {
        $explicit = array_map('intval', (array) $this->option('substance'));

        if ($explicit !== []) {
            return $explicit;
        }

        $query = FactsheetStatistic::query();

        if (! $this->option('all')) {
            // A row is stale when it predates the surface-water occurrence
            // block, which is what this command was written to backfill.
            // `jsonb_exists()` rather than the `?` operator: Laravel reads a
            // literal `?` in raw SQL as a binding placeholder.
            $query->where(function ($q) {
                $q->whereNull('meta_data')
                    ->orWhereRaw("not jsonb_exists(meta_data::jsonb, 'surface_water_occurrence')");
            });
        }

        $ids = $query->orderBy('substance_id')->pluck('substance_id');

        if ($limit = $this->option('limit')) {
            $ids = $ids->take((int) $limit);
        }

        return $ids->map(fn ($id) => (int) $id)->all();
    }

    private function humanEta(int $count): string
    {
        $seconds = $count * 2.5;

        return $seconds < 90
            ? round($seconds).' seconds'
            : ($seconds < 5400 ? round($seconds / 60).' minutes' : round($seconds / 3600, 1).' hours');
    }
}
