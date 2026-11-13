<?php

declare(strict_types=1);

namespace App\Jobs\Empodat;

use App\Actions\Empodat\GenerateEmpodatStatisticsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateEmpodatStatisticsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const LOCK_KEY = 'empodat.statistics.generation';

    public const LOCK_TTL = 1700;

    public int $timeout = 1800;

    public int $tries = 1;

    public function uniqueId(): string
    {
        return self::LOCK_KEY;
    }

    public function uniqueFor(): int
    {
        return self::LOCK_TTL;
    }

    public function handle(GenerateEmpodatStatisticsAction $action): void
    {
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

        if (! $lock->get()) {
            Log::info('EMPODAT statistics generation skipped: another run holds the lock.');

            return;
        }

        try {
            Log::info('EMPODAT statistics generation started.');
            $summary = $action->execute();
            Log::info('EMPODAT statistics generation finished.', $summary);
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('EMPODAT statistics generation failed.', [
            'exception' => $e->getMessage(),
        ]);

        Cache::lock(self::LOCK_KEY)->forceRelease();
    }
}
