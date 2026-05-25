<?php

declare(strict_types=1);

namespace App\Jobs\EmpodatSuspect;

use App\Actions\EmpodatSuspect\GenerateStatisticsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateEmpodatSuspectStatisticsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes

    public int $tries = 1;

    /**
     * Lock kept long enough to cover the worst-case run, but well under timeout
     * so a crashed job's lock auto-expires before the next scheduled attempt.
     */
    private const LOCK_KEY = 'empodat_suspect.statistics.generation';

    private const LOCK_TTL = 1700;

    /**
     * Ensures the job is unique in the queue (prevents double-clicks from
     * stacking duplicate jobs while one is already pending/running).
     */
    public function uniqueId(): string
    {
        return self::LOCK_KEY;
    }

    public function uniqueFor(): int
    {
        return self::LOCK_TTL;
    }

    public function handle(GenerateStatisticsAction $action): void
    {
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

        if (! $lock->get()) {
            Log::info('Empodat Suspect statistics generation skipped: another run holds the lock.');

            return;
        }

        try {
            Log::info('Empodat Suspect statistics generation started.');
            $summary = $action->execute();
            Log::info('Empodat Suspect statistics generation finished.', $summary);
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('Empodat Suspect statistics generation failed.', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        Cache::lock(self::LOCK_KEY)->forceRelease();
    }
}
