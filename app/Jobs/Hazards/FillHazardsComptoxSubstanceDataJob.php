<?php

namespace App\Jobs\Hazards;

use App\Services\Hazards\ComptoxSubstanceDataFillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FillHazardsComptoxSubstanceDataJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const REQUEST_TOKEN_CACHE_KEY = 'hazards:substance-data:fill:request-token';
    public const REQUEST_TOKEN_TTL_SECONDS = 7200;

    public int $timeout = 3600;
    public int $tries = 1;
    public int $uniqueFor = 7200;

    public string $trigger;
    public ?string $requestToken;
    public ?int $editorUserId;

    public function __construct(string $trigger = 'manual', ?string $requestToken = null, ?int $editorUserId = null)
    {
        $this->trigger = $trigger;
        $this->requestToken = $requestToken;
        $this->editorUserId = $editorUserId;
    }

    public function uniqueId(): string
    {
        return 'hazards-substance-data-fill';
    }

    public function handle(ComptoxSubstanceDataFillService $fillService): void
    {
        $lock = $this->acquireLock();
        if (! $lock) {
            Log::warning('Hazards substance data fill skipped because another run is already in progress.');
            $this->releaseRequestToken();
            return;
        }

        try {
            $summary = $fillService->fillFromParsedData($this->editorUserId);

            Log::info('Hazards substance data fill completed.', [
                'trigger' => $this->trigger,
                'editor_user_id' => $this->editorUserId,
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            Log::error('Hazards substance data fill failed: '.$e->getMessage(), [
                'exception' => get_class($e),
                'trigger' => $this->trigger,
            ]);
        } finally {
            optional($lock)->release();
            $this->releaseRequestToken();
        }
    }

    private function acquireLock(): ?Lock
    {
        try {
            $lock = Cache::lock('hazards:substance-data:fill:lock', 3600);
            if ($lock->get()) {
                return $lock;
            }
        } catch (\Throwable $e) {
            Log::warning('Hazards substance data fill default cache lock failed, trying file lock.', [
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $lock = Cache::store('file')->lock('hazards:substance-data:fill:lock', 3600);
            if ($lock->get()) {
                return $lock;
            }
        } catch (\Throwable $e) {
            Log::warning('Hazards substance data fill file cache lock failed.', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function releaseRequestToken(): void
    {
        if (empty($this->requestToken)) {
            return;
        }

        try {
            $currentToken = Cache::get(self::REQUEST_TOKEN_CACHE_KEY);
            if (is_string($currentToken) && hash_equals($currentToken, $this->requestToken)) {
                Cache::forget(self::REQUEST_TOKEN_CACHE_KEY);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to release Hazards substance data fill request token.', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
