<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\EmpodatSuspect\GenerateStatisticsAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateEmpodatSuspectStatistics extends Command
{
    protected $signature = 'empodat-suspect:generate-statistics
                            {--sync : Run inline instead of dispatching the queued job}';

    protected $description = 'Generate all statistics for the Empodat Suspect module (queued by default; --sync runs inline).';

    private const LOCK_KEY = 'empodat_suspect.statistics.generation';

    private const LOCK_TTL = 1700;

    public function handle(GenerateStatisticsAction $action): int
    {
        if (! $this->option('sync')) {
            \App\Jobs\EmpodatSuspect\GenerateEmpodatSuspectStatisticsJob::dispatch();
            $this->info('Empodat Suspect statistics generation queued.');

            return self::SUCCESS;
        }

        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

        if (! $lock->get()) {
            $this->warn('Another statistics generation run already holds the lock — aborting.');

            return self::FAILURE;
        }

        try {
            $this->info('Generating Empodat Suspect statistics (sync)…');
            $summary = $action->execute();
            $this->info('Done.');
            $this->table(['metric', 'value'], collect($summary)->map(fn ($v, $k) => [$k, $v])->values()->all());

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Statistics generation failed: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
