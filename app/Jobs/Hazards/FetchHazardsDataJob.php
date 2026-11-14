<?php

namespace App\Jobs\Hazards;

use App\Mail\Hazards\ApiFetchEnd;
use App\Mail\Hazards\ApiFetchStart;
use App\Jobs\Hazards\ParseHazardsComptoxDataJob;
use App\Models\Hazards\ApiRun;
use App\Models\Hazards\ComptoxPayload;
use App\Models\Susdat\Substance;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchHazardsDataJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const REQUEST_TOKEN_CACHE_KEY = 'hazards:fetch:request-token';
    public const REQUEST_TOKEN_TTL_SECONDS = 7200;

    public int $timeout = 3600;
    public int $tries = 1;
    public int $uniqueFor = 7200;
    private const BATCH_SIZE = 25;
    private const HTTP_TIMEOUT_SECONDS = 60;
    private const HTTP_RETRY_TIMES = 2;
    private const HTTP_RETRY_SLEEP_MS = 500;

    // Testing limit: set to null for full run.
    private const FETCH_LIMIT = 120;
    public string $trigger;
    public ?string $requestToken;

    public function __construct(string $trigger = 'manual', ?string $requestToken = null)
    {
        $this->trigger = $trigger;
        $this->requestToken = $requestToken;
    }

    public function uniqueId(): string
    {
        return 'hazards-fetch';
    }

    public function handle(): void
    {
        $lock = $this->acquireLock();
        if (! $lock) {
            Log::warning('Hazards fetch skipped because another run is already in progress.');
            $this->releaseRequestToken();
            return;
        }

        Log::info('Hazards fetch job started.', ['trigger' => $this->trigger]);

        $startedAt = Carbon::now();
        $run = ApiRun::create([
            'trigger' => $this->trigger,
            'status' => 'running',
            'started_at' => $startedAt,
        ]);

        $notifyTo = config('services.hazards_comptox.notify_to');
        $this->sendMailSafely($notifyTo, new ApiFetchStart($run), 'start', $run->id);

        try {
            $query = Substance::query()
                ->whereNotNull('dtxid')
                ->where('dtxid', '!=', '')
                ->orderBy('id')
                ->select(['id', 'dtxid']);

            if (self::FETCH_LIMIT !== null) {
                $query->limit(self::FETCH_LIMIT);
            }

            $substances = $query->get();
            $totalDtxids = $substances->count();

            if ($totalDtxids === 0) {
                $run->update([
                    'status' => 'finished',
                    'total_dtxids' => 0,
                    'processed_dtxids' => 0,
                    'successful_dtxids' => 0,
                    'failed_dtxids' => 0,
                    'new_payloads' => 0,
                    'updated_payloads' => 0,
                    'failed_endpoints' => [],
                    'duration_seconds' => $this->elapsedSeconds($startedAt),
                    'ended_at' => Carbon::now(),
                    'notes' => 'No dtxid values found in susdat_substances.',
                ]);

                $this->sendMailSafely($notifyTo, new ApiFetchEnd($run->fresh()), 'end', $run->id);
                return;
            }

            $run->update(['total_dtxids' => $totalDtxids]);

            $baseUrl = rtrim((string) config('services.hazards_comptox.base_url'), '/');
            $apiKey = (string) config('services.hazards_comptox.api_key');
            if ($apiKey === '') {
                throw new \RuntimeException('Missing HAZARDS_COMPTOX_API_KEY.');
            }

            $headers = [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-api-key' => $apiKey,
            ];

            $endpoints = [
                'fate' => $baseUrl.'/chemical/fate/search/by-dtxsid/',
                'detail' => $baseUrl.'/chemical/detail/search/by-dtxsid/',
                'property_experimental' => $baseUrl.'/chemical/property/experimental/search/by-dtxsid/',
                'property_predicted' => $baseUrl.'/chemical/property/predicted/search/by-dtxsid/',
                'synonym' => $baseUrl.'/chemical/synonym/search/by-dtxsid/',
            ];

            $processed = 0;
            $successful = 0;
            $failed = 0;
            $newPayloads = 0;
            $updatedPayloads = 0;
            $failedEndpointsByRun = [];
            $firstEndpointErrorMessage = null;

            $batches = array_chunk($substances->all(), self::BATCH_SIZE);

            foreach ($batches as $batch) {
                $batchDtxids = array_values(array_filter(array_unique(array_map(
                    static fn ($substance) => (string) $substance->dtxid,
                    $batch
                ))));

                if (empty($batchDtxids)) {
                    continue;
                }

                $responsesByEndpoint = [];
                $endpointStatus = [];

                foreach ($endpoints as $endpointKey => $url) {
                    try {
                        $response = Http::withHeaders($headers)
                            ->timeout(self::HTTP_TIMEOUT_SECONDS)
                            ->retry(self::HTTP_RETRY_TIMES, self::HTTP_RETRY_SLEEP_MS)
                            ->post($url, $batchDtxids);

                        if ($response->successful()) {
                            $responsesByEndpoint[$endpointKey] = $response->json() ?? [];
                            $endpointStatus[$endpointKey] = 'ok';
                        } else {
                            $responsesByEndpoint[$endpointKey] = null;
                            $endpointStatus[$endpointKey] = 'failed:'.$response->status();
                            $failedEndpointsByRun[$endpointKey] = ($failedEndpointsByRun[$endpointKey] ?? 0) + 1;
                            if ($firstEndpointErrorMessage === null) {
                                $firstEndpointErrorMessage = "Endpoint {$endpointKey} returned HTTP ".$response->status().'.';
                            }
                        }
                    } catch (\Throwable $e) {
                        $responsesByEndpoint[$endpointKey] = null;
                        $endpointStatus[$endpointKey] = 'failed:exception';
                        $failedEndpointsByRun[$endpointKey] = ($failedEndpointsByRun[$endpointKey] ?? 0) + 1;
                        if ($firstEndpointErrorMessage === null) {
                            $firstEndpointErrorMessage = $e->getMessage();
                        }
                        Log::warning('Hazards endpoint request failed', [
                            'endpoint' => $endpointKey,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                $allEndpointsFailed = ! in_array('ok', $endpointStatus, true);

                $indexedByEndpoint = [];
                foreach (['fate', 'detail', 'property_experimental', 'property_predicted', 'synonym'] as $endpointKey) {
                    $indexedByEndpoint[$endpointKey] = [];
                    if (! is_array($responsesByEndpoint[$endpointKey])) {
                        continue;
                    }

                    foreach ($responsesByEndpoint[$endpointKey] as $row) {
                        $dtxid = $row['dtxsid'] ?? $row['dtxid'] ?? null;
                        if (! is_string($dtxid) || $dtxid === '') {
                            continue;
                        }

                        if (! isset($indexedByEndpoint[$endpointKey][$dtxid])) {
                            $indexedByEndpoint[$endpointKey][$dtxid] = [];
                        }
                        $indexedByEndpoint[$endpointKey][$dtxid][] = $row;
                    }
                }

                foreach ($batch as $substance) {
                    $dtxid = (string) $substance->dtxid;
                    if ($dtxid === '') {
                        continue;
                    }

                    $processed++;

                    // If all endpoints failed for this batch, do not touch payload rows.
                    // This preserves existing data and avoids false "updated" counts.
                    if ($allEndpointsFailed) {
                        $failed++;
                        continue;
                    }

                    $payload = ComptoxPayload::where('dtxid', $dtxid)->first();
                    $isNew = $payload === null;

                    if ($isNew) {
                        $payload = new ComptoxPayload();
                        $payload->dtxid = $dtxid;
                        $payload->susdat_substance_id = $substance->id;
                    } else {
                        // Keep FK in sync in case mapping changed.
                        $payload->susdat_substance_id = $substance->id;
                    }

                    // Preserve existing endpoint data when endpoint request failed.
                    if (is_array($responsesByEndpoint['fate'])) {
                        $payload->fate = $indexedByEndpoint['fate'][$dtxid] ?? [];
                    }
                    if (is_array($responsesByEndpoint['detail'])) {
                        $payload->detail = $indexedByEndpoint['detail'][$dtxid] ?? [];
                    }
                    if (
                        is_array($responsesByEndpoint['property_experimental']) ||
                        is_array($responsesByEndpoint['property_predicted'])
                    ) {
                        $payload->property = $this->mergePropertyPayload(
                            existingPropertyRows: $payload->property,
                            experimentalRows: is_array($responsesByEndpoint['property_experimental'])
                                ? ($indexedByEndpoint['property_experimental'][$dtxid] ?? [])
                                : null,
                            predictedRows: is_array($responsesByEndpoint['property_predicted'])
                                ? ($indexedByEndpoint['property_predicted'][$dtxid] ?? [])
                                : null
                        );
                    }
                    if (is_array($responsesByEndpoint['synonym'])) {
                        $payload->synonym = $indexedByEndpoint['synonym'][$dtxid] ?? [];
                    }

                    $payload->api_run_id = $run->id;
                    $payload->fetched_at = Carbon::now();
                    $payload->endpoint_status = $endpointStatus;
                    $payload->save();

                    if ($isNew) {
                        $newPayloads++;
                    } else {
                        $updatedPayloads++;
                    }

                    // Mark per-DTXID success/fail based on whether any endpoint succeeded.
                    $anySuccess = in_array('ok', $endpointStatus, true);
                    if ($anySuccess) {
                        $successful++;
                    } else {
                        $failed++;
                    }
                }
            }

            $allProcessedFailed = ($processed > 0) && ($failed === $processed);
            $status = $allProcessedFailed ? 'failed' : 'finished';
            $notes = $this->buildCompletionNotes(
                $allProcessedFailed,
                $firstEndpointErrorMessage
            );

            $run->update([
                'status' => $status,
                'processed_dtxids' => $processed,
                'successful_dtxids' => $successful,
                'failed_dtxids' => $failed,
                'new_payloads' => $newPayloads,
                'updated_payloads' => $updatedPayloads,
                'failed_endpoints' => $failedEndpointsByRun,
                'duration_seconds' => $this->elapsedSeconds($startedAt),
                'ended_at' => Carbon::now(),
                'notes' => $notes,
            ]);

            ParseHazardsComptoxDataJob::dispatch($run->id, $this->trigger);
        } catch (\Throwable $e) {
            Log::error('Hazards API fetch failed: '.$e->getMessage(), [
                'exception' => get_class($e),
            ]);

            try {
                $run->update([
                    'status' => 'failed',
                    'duration_seconds' => $this->elapsedSeconds($startedAt),
                    'ended_at' => Carbon::now(),
                    'notes' => $e->getMessage(),
                ]);
            } catch (\Throwable $updateError) {
                Log::error('Hazards run status update failed: '.$updateError->getMessage());
            }
        } finally {
            try {
                $this->sendMailSafely($notifyTo ?? null, new ApiFetchEnd($run->fresh()), 'end', $run->id);
            } finally {
                optional($lock)->release();
                $this->releaseRequestToken();
            }
        }
    }

    private function acquireLock(): ?Lock
    {
        try {
            $lock = Cache::lock('hazards:fetch:lock', 3600);
            if ($lock->get()) {
                return $lock;
            }
        } catch (\Throwable $e) {
            Log::warning('Hazards default cache lock failed, trying file lock.', [
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $lock = Cache::store('file')->lock('hazards:fetch:lock', 3600);
            if ($lock->get()) {
                return $lock;
            }
        } catch (\Throwable $e) {
            Log::warning('Hazards file cache lock failed.', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function mergePropertyPayload(
        mixed $existingPropertyRows,
        ?array $experimentalRows,
        ?array $predictedRows
    ): array {
        $existingRows = $this->normalizeItems($existingPropertyRows);

        $existingExperimental = [];
        $existingPredicted = [];
        $existingUnknown = [];

        foreach ($existingRows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $bucket = $this->resolvePropertyBucket($row);
            if ($bucket === 'experimental') {
                $existingExperimental[] = $row;
            } elseif ($bucket === 'predicted') {
                $existingPredicted[] = $row;
            } else {
                $existingUnknown[] = $row;
            }
        }

        $mergedExperimental = $experimentalRows !== null
            ? $this->annotatePropertyRows($experimentalRows, 'experimental')
            : $existingExperimental;

        $mergedPredicted = $predictedRows !== null
            ? $this->annotatePropertyRows($predictedRows, 'predicted')
            : $existingPredicted;

        return array_values(array_merge($mergedExperimental, $mergedPredicted, $existingUnknown));
    }

    private function annotatePropertyRows(array $rows, string $propertyType): array
    {
        return array_values(array_map(static function ($row) use ($propertyType) {
            if (! is_array($row)) {
                return $row;
            }

            if (! isset($row['propType']) || $row['propType'] === null || $row['propType'] === '') {
                $row['propType'] = $propertyType;
            }

            if (! isset($row['prop_type']) || $row['prop_type'] === null || $row['prop_type'] === '') {
                $row['prop_type'] = $propertyType;
            }

            return $row;
        }, $rows));
    }

    private function resolvePropertyBucket(array $row): ?string
    {
        $value = strtolower(trim((string) ($row['propType'] ?? $row['prop_type'] ?? '')));

        if (in_array($value, ['experimental', 'exp', '2'], true)) {
            return 'experimental';
        }

        if (in_array($value, ['predicted', 'pred', '3'], true)) {
            return 'predicted';
        }

        return null;
    }

    private function normalizeItems(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        if (array_keys($value) !== range(0, count($value) - 1)) {
            return [$value];
        }

        return $value;
    }

    private function sendMailSafely(?string $notifyTo, object $mailable, string $phase, int $runId): void
    {
        if (empty($notifyTo)) {
            return;
        }

        $maxAttempts = 5;
        $sleepSeconds = [2, 4, 8, 12];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                Mail::to($notifyTo)->send($mailable);
                return;
            } catch (\Throwable $e) {
                if ($attempt >= $maxAttempts) {
                    Log::warning("Hazards {$phase} email failed: ".$e->getMessage(), [
                        'run_id' => $runId,
                        'phase' => $phase,
                        'attempt' => $attempt,
                    ]);
                    return;
                }

                $wait = $sleepSeconds[$attempt - 1] ?? 15;
                if (str_contains(strtolower($e->getMessage()), 'too many emails per second')) {
                    $wait = max($wait, 6);
                }
                sleep($wait);
            }
        }
    }

    private function elapsedSeconds(Carbon $startedAt): int
    {
        return max(0, Carbon::now()->getTimestamp() - $startedAt->getTimestamp());
    }

    private function buildCompletionNotes(
        bool $allProcessedFailed,
        ?string $firstEndpointErrorMessage
    ): ?string {
        if ($allProcessedFailed) {
            return $firstEndpointErrorMessage ?: 'All endpoint requests failed.';
        }

        if (! empty($firstEndpointErrorMessage)) {
            return $firstEndpointErrorMessage;
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
            Log::warning('Failed to release Hazards fetch request token.', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
