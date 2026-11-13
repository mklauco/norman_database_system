<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Empodat\GenerateEmpodatStatisticsAction;
use App\Jobs\Empodat\GenerateEmpodatStatisticsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateEmpodatStatistics extends Command
{
    protected $signature = 'empodat:generate-statistics
                            {--sync : Run inline instead of dispatching the queued job}';

    protected $description = 'Generate all EMPODAT statistics, public and admin scope, in one run (queued by default; --sync runs inline).';

    public function handle(GenerateEmpodatStatisticsAction $action): int
    {
        if (! $this->option('sync')) {
            GenerateEmpodatStatisticsJob::dispatch();
            $this->info('EMPODAT statistics generation queued.');

            return self::SUCCESS;
        }

        $lock = Cache::lock(GenerateEmpodatStatisticsJob::LOCK_KEY, GenerateEmpodatStatisticsJob::LOCK_TTL);

        if (! $lock->get()) {
            $this->warn('Another statistics generation run holds the lock. Aborting.');

            return self::FAILURE;
        }

        try {
            $this->info('Generating EMPODAT statistics (sync)...');
            $summary = $action->execute();
            $this->table(['metric', 'value'], collect($summary)->map(fn ($value, $key) => [$key, $value])->values()->all());

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Statistics generation failed: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
