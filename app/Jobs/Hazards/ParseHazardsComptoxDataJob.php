<?php

namespace App\Jobs\Hazards;

use App\Mail\Hazards\ParseComptoxEnd;
use App\Mail\Hazards\ParseComptoxStart;
use App\Models\Hazards\ComptoxDetailRecord;
use App\Models\Hazards\ComptoxFateRecord;
use App\Models\Hazards\ComptoxPayload;
use App\Models\Hazards\ComptoxPropertyRecord;
use App\Models\Hazards\ParseRun;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ParseHazardsComptoxDataJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;
    public int $uniqueFor = 7200;
    public ?int $sourceApiRunId;
    public string $trigger;

    private const WANTED_FATE_ENDPOINTS = [
        'ReadyBiodeg',
        'Biodeg. Half-Life',
        'Soil Adsorp. Coeff. (Koc)',
        'Bioconcentration Factor',
    ];

    private const WANTED_PROPERTY_NAMES = [
        'Water Solubility',
        'LogKoa: Octanol-Air',
        'LogKow: Octanol-Water',
        'pKa Acidic - Apparent',
        'pKa Acidic – Apparent',
        'pKa Basic - Apparent',
        'pKa Basic – Apparent',
        'LogD5.5',
        'LogD7.4',
    ];

    public function __construct(?int $sourceApiRunId = null, string $trigger = 'manual')
    {
        $this->sourceApiRunId = $sourceApiRunId;
        $this->trigger = $trigger;
    }

    public function uniqueId(): string
    {
        return 'hazards-parse:'.($this->sourceApiRunId ?? 'recent');
    }

    public function handle(): void
    {
        $lock = $this->acquireLock();
        if (! $lock) {
            Log::warning('Hazards parse skipped because another parse run is already in progress.');
            return;
        }

        $startedAt = Carbon::now();
        $notifyTo = config('services.hazards_comptox.notify_to');

        $run = ParseRun::create([
            'source_api_run_id' => $this->sourceApiRunId,
            'trigger' => $this->trigger,
            'status' => 'running',
            'started_at' => $startedAt,
            'counts_by_type' => [
                'fate' => ['new' => 0, 'updated' => 0, 'unchanged' => 0],
                'property' => ['new' => 0, 'updated' => 0, 'unchanged' => 0],
                'detail' => ['new' => 0, 'updated' => 0, 'unchanged' => 0],
            ],
        ]);

        $this->sendMailSafely($notifyTo, new ParseComptoxStart($run), 'parse-start', $run->id);

        try {
            $payloadQuery = ComptoxPayload::query()
                ->orderBy('id')
                ->select([
                    'id',
                    'api_run_id',
                    'susdat_substance_id',
                    'dtxid',
                    'fate',
                    'property',
                    'detail',
                    'updated_at',
                ]);

            if ($this->sourceApiRunId !== null) {
                $payloadQuery->where('api_run_id', $this->sourceApiRunId);
            } else {
                // Close to original PBMT behavior: parse recently refreshed raw payload rows.
                $payloadQuery->where('updated_at', '>=', Carbon::now()->subHour());
            }

            $payloads = $payloadQuery->get();
            $totalPayloads = $payloads->count();
            $run->update(['total_payloads' => $totalPayloads]);

            if ($totalPayloads === 0) {
                $run->update([
                    'status' => 'finished',
                    'processed_payloads' => 0,
                    'successful_payloads' => 0,
                    'failed_payloads' => 0,
                    'new_records' => 0,
                    'updated_records' => 0,
                    'unchanged_records' => 0,
                    'duration_seconds' => $this->elapsedSeconds($startedAt),
                    'ended_at' => Carbon::now(),
                    'notes' => 'No payloads found for parse input.',
                ]);

                $this->sendMailSafely($notifyTo, new ParseComptoxEnd($run->fresh()), 'parse-end', $run->id);
                return;
            }

            $countsByType = [
                'fate' => ['new' => 0, 'updated' => 0, 'unchanged' => 0],
                'property' => ['new' => 0, 'updated' => 0, 'unchanged' => 0],
                'detail' => ['new' => 0, 'updated' => 0, 'unchanged' => 0],
            ];

            $processedPayloads = 0;
            $successfulPayloads = 0;
            $failedPayloads = 0;
            $newRecords = 0;
            $updatedRecords = 0;
            $unchangedRecords = 0;
            $firstPayloadErrorMessage = null;

            foreach ($payloads as $payload) {
                try {
                    $processedPayloads++;
                    $actions = [];

                    $payloadDtxid = (string) $payload->dtxid;

                    $fateItems = $this->extractFateItems($payload->fate, $payloadDtxid);
                    $processedFateIds = [];
                    foreach ($fateItems as $item) {
                        if (! is_array($item)) {
                            continue;
                        }

                        $fateId = $this->buildFateRecordId($item);
                        if ($fateId !== '' && isset($processedFateIds[$fateId])) {
                            continue;
                        }

                        $actions[] = $this->handleFateData($run->id, $payload, $item);
                        if ($fateId !== '') {
                            $processedFateIds[$fateId] = true;
                        }
                    }

                    $propertyItems = $this->extractPropertyItems($payload->property, $payloadDtxid);
                    $processedPropertyIds = [];
                    foreach ($propertyItems as $item) {
                        if (! is_array($item)) {
                            continue;
                        }

                        if (! $this->isWantedPropertyName((string) ($item['name'] ?? ''))) {
                            continue;
                        }

                        $propertyId = $this->buildPropertyRecordId(
                            $item,
                            $this->toNullableString($item['propType'] ?? $item['prop_type'] ?? null)
                        );
                        if ($propertyId !== '' && in_array($propertyId, $processedPropertyIds, true)) {
                            continue;
                        }

                        $actions[] = $this->handlePropertyData($run->id, $payload, $item);
                        if ($propertyId !== '') {
                            $processedPropertyIds[] = $propertyId;
                        }
                    }

                    $detailItems = $this->normalizeItems($payload->detail);
                    foreach ($detailItems as $item) {
                        if (! is_array($item)) {
                            continue;
                        }

                        if (($item['dtxsid'] ?? null) !== $payloadDtxid) {
                            continue;
                        }

                        $actions[] = $this->handleDetailData($run->id, $payload, $item);
                        // Keep close to original: only one detail row per DTXID.
                        break;
                    }

                    foreach ($actions as $action) {
                        $recordType = $action['record_type'];
                        $actionType = $action['action'];

                        $countsByType[$recordType][$actionType]++;

                        if ($actionType === 'new') {
                            $newRecords++;
                        } elseif ($actionType === 'updated') {
                            $updatedRecords++;
                        } else {
                            $unchangedRecords++;
                        }
                    }

                    $successfulPayloads++;
                } catch (\Throwable $e) {
                    $failedPayloads++;
                    if ($firstPayloadErrorMessage === null) {
                        $firstPayloadErrorMessage = $e->getMessage();
                    }
                    Log::warning('Hazards parse payload failed', [
                        'parse_run_id' => $run->id,
                        'payload_id' => $payload->id,
                        'dtxid' => $payload->dtxid,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $allProcessedFailed = ($processedPayloads > 0) && ($successfulPayloads === 0);
            $status = $allProcessedFailed ? 'failed' : 'finished';

            $run->update([
                'status' => $status,
                'processed_payloads' => $processedPayloads,
                'successful_payloads' => $successfulPayloads,
                'failed_payloads' => $failedPayloads,
                'new_records' => $newRecords,
                'updated_records' => $updatedRecords,
                'unchanged_records' => $unchangedRecords,
                'counts_by_type' => $countsByType,
                'duration_seconds' => $this->elapsedSeconds($startedAt),
                'ended_at' => Carbon::now(),
                'notes' => $this->buildCompletionNotes($allProcessedFailed, $firstPayloadErrorMessage, $failedPayloads),
            ]);
        } catch (\Throwable $e) {
            Log::error('Hazards parse failed: '.$e->getMessage(), [
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
                Log::error('Hazards parse run status update failed: '.$updateError->getMessage());
            }
        } finally {
            try {
                $this->sendMailSafely($notifyTo, new ParseComptoxEnd($run->fresh()), 'parse-end', $run->id);
            } finally {
                optional($lock)->release();
            }
        }
    }

    private function handleFateData(int $parseRunId, ComptoxPayload $payload, array $item): array
    {
        $sourceJson = $item['_source'] ?? $item;
        $fateId = $this->buildFateRecordId($item);

        $data = [
            'parse_run_id' => $parseRunId,
            'comptox_payload_id' => $payload->id,
            'susdat_substance_id' => $payload->susdat_substance_id,
            'fate_id' => $fateId,
            'dtxid' => (string) ($item['dtxsid'] ?? $item['dtxid'] ?? $payload->dtxid),
            'endpoint_name' => (string) ($item['endpointName'] ?? ''),
            'result_value' => $this->toNullableString($item['resultValue'] ?? null),
            'model_source' => $this->toNullableString($item['modelSource'] ?? null),
            'unit' => $this->toNullableString($item['unit'] ?? null),
            'value_type' => $this->toNullableString($item['valueType'] ?? null),
            'source_json' => $sourceJson,
        ];

        $existing = ComptoxFateRecord::where('dtxid', $data['dtxid'])
            ->where('fate_id', $data['fate_id'])
            ->first();

        if (! $existing) {
            ComptoxFateRecord::create($data);
            return ['action' => 'new', 'record_type' => 'fate'];
        }

        if ($this->hasChanges($existing, $data, [
            'endpoint_name',
            'result_value',
            'model_source',
            'unit',
            'value_type',
            'source_json',
        ])) {
            $existing->update($data);
            return ['action' => 'updated', 'record_type' => 'fate'];
        }

        return ['action' => 'unchanged', 'record_type' => 'fate'];
    }

    private function buildFateRecordId(array $item): string
    {
        $sourceJson = $item['_source'] ?? $item;
        $fateId = trim((string) ($item['id'] ?? ''));

        if ($fateId !== '') {
            return $fateId;
        }

        return sha1(json_encode($sourceJson));
    }

    private function handlePropertyData(int $parseRunId, ComptoxPayload $payload, array $item): array
    {
        $propertyType = $this->toNullableString($item['propType'] ?? null);
        $propertyId = $this->buildPropertyRecordId($item, $propertyType);

        $data = [
            'parse_run_id' => $parseRunId,
            'comptox_payload_id' => $payload->id,
            'susdat_substance_id' => $payload->susdat_substance_id,
            'property_id' => $propertyId,
            'dtxid' => (string) ($item['dtxsid'] ?? $payload->dtxid),
            'name' => (string) ($item['name'] ?? ''),
            'value' => $this->toNullableString($item['value'] ?? null),
            'unit' => $this->toNullableString($item['unit'] ?? null),
            'prop_type' => $propertyType,
            'source' => $this->toNullableString($item['source'] ?? null),
            'property_string_id' => $this->toNullableString($item['propertyId'] ?? null),
            'source_json' => $item,
        ];

        $existing = ComptoxPropertyRecord::where('dtxid', $data['dtxid'])
            ->where('property_id', $data['property_id'])
            ->first();

        if (! $existing) {
            ComptoxPropertyRecord::create($data);
            return ['action' => 'new', 'record_type' => 'property'];
        }

        if ($this->hasChanges($existing, $data, [
            'name',
            'value',
            'unit',
            'prop_type',
            'source',
            'property_string_id',
            'source_json',
        ])) {
            $existing->update($data);
            return ['action' => 'updated', 'record_type' => 'property'];
        }

        return ['action' => 'unchanged', 'record_type' => 'property'];
    }

    private function buildPropertyRecordId(array $item, ?string $propertyType): string
    {
        $baseId = trim((string) ($item['id'] ?? ''));
        $normalizedType = strtolower(trim((string) $propertyType));

        if ($baseId !== '') {
            return $normalizedType !== '' ? $baseId.'|'.$normalizedType : $baseId;
        }

        return sha1(json_encode([
            'dtxsid' => $item['dtxsid'] ?? null,
            'name' => $item['name'] ?? null,
            'value' => $item['value'] ?? null,
            'unit' => $item['unit'] ?? null,
            'propType' => $propertyType,
            'propertyId' => $item['propertyId'] ?? null,
            'source' => $item['source'] ?? null,
        ]));
    }

    private function handleDetailData(int $parseRunId, ComptoxPayload $payload, array $item): array
    {
        $data = [
            'parse_run_id' => $parseRunId,
            'comptox_payload_id' => $payload->id,
            'susdat_substance_id' => $payload->susdat_substance_id,
            'dtxid' => (string) ($item['dtxsid'] ?? $payload->dtxid),
            'preferred_name' => $this->toNullableString($item['preferredName'] ?? null),
            'casrn' => $this->toNullableString($item['casrn'] ?? null),
            'inchikey' => $this->toNullableString($item['inchikey'] ?? null),
            'smiles' => $this->toNullableString($item['smiles'] ?? null),
            'source_json' => $item,
        ];

        $existing = ComptoxDetailRecord::where('dtxid', $data['dtxid'])->first();

        if (! $existing) {
            ComptoxDetailRecord::create($data);
            return ['action' => 'new', 'record_type' => 'detail'];
        }

        if ($this->hasChanges($existing, $data, [
            'preferred_name',
            'casrn',
            'inchikey',
            'smiles',
            'source_json',
        ])) {
            $existing->update($data);
            return ['action' => 'updated', 'record_type' => 'detail'];
        }

        return ['action' => 'unchanged', 'record_type' => 'detail'];
    }

    private function hasChanges(object $existing, array $newData, array $fields): bool
    {
        foreach ($fields as $field) {
            $old = $existing->{$field} ?? null;
            $new = $newData[$field] ?? null;
            if ($old != $new) {
                return true;
            }
        }

        return false;
    }

    private function normalizeItems(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if ($value === []) {
            return [];
        }

        // Single object payload shape.
        if (array_keys($value) !== range(0, count($value) - 1)) {
            return [$value];
        }

        return $value;
    }

    private function extractPropertyItems(mixed $rawProperty, string $payloadDtxid): array
    {
        $rows = [];
        $propertyItems = $this->normalizeItems($rawProperty);

        foreach ($propertyItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (isset($item['properties']) && is_array($item['properties'])) {
                $topDtxid = (string) ($item['dtxsid'] ?? $item['dtxid'] ?? '');
                if ($topDtxid !== '' && $topDtxid !== $payloadDtxid) {
                    continue;
                }

                foreach ($item['properties'] as $propertyRow) {
                    if (! is_array($propertyRow)) {
                        continue;
                    }

                    $rows = array_merge(
                        $rows,
                        $this->extractPropertyRowsFromNestedProperty(
                            $propertyRow,
                            $topDtxid ?: $payloadDtxid
                        )
                    );
                }

                continue;
            }

            $normalized = $this->normalizeFlatPropertyRow($item, $payloadDtxid);
            if ($normalized !== null) {
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    private function extractPropertyRowsFromNestedProperty(array $propertyRow, string $fallbackDtxid): array
    {
        $rows = [];
        $propertyName = (string) ($propertyRow['name'] ?? $propertyRow['propName'] ?? $propertyRow['prop_name'] ?? '');

        $directNormalized = $this->normalizeFlatPropertyRow(
            $propertyRow + ['name' => $propertyName, 'dtxsid' => $propertyRow['dtxsid'] ?? $fallbackDtxid],
            $fallbackDtxid
        );
        if ($directNormalized !== null && $this->rowLooksLikePropertyValue($directNormalized)) {
            $rows[] = $directNormalized;
        }

        $bucketMap = [
            'experimentalPropertyData' => 'experimental',
            'predictedPropertyData' => 'predicted',
            'experimentalProperties' => 'experimental',
            'predictedProperties' => 'predicted',
            'experimentalData' => 'experimental',
            'predictedData' => 'predicted',
        ];

        foreach ($bucketMap as $bucketKey => $bucketType) {
            if (! isset($propertyRow[$bucketKey]) || ! is_array($propertyRow[$bucketKey])) {
                continue;
            }

            foreach ($propertyRow[$bucketKey] as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $normalized = $this->normalizeFlatPropertyRow(
                    $entry + [
                        'name' => $entry['name'] ?? $propertyName,
                        'propType' => $entry['propType'] ?? $entry['prop_type'] ?? $bucketType,
                        'dtxsid' => $entry['dtxsid'] ?? $fallbackDtxid,
                    ],
                    $fallbackDtxid
                );

                if ($normalized !== null) {
                    $rows[] = $normalized;
                }
            }
        }

        return $rows;
    }

    private function normalizeFlatPropertyRow(array $item, string $payloadDtxid): ?array
    {
        $dtxsid = (string) ($item['dtxsid'] ?? $item['dtxid'] ?? $payloadDtxid);
        if ($dtxsid === '' || $dtxsid !== $payloadDtxid) {
            return null;
        }

        $name = (string) ($item['name'] ?? $item['propName'] ?? $item['prop_name'] ?? '');
        if ($name === '') {
            return null;
        }

        return [
            'id' => $item['id'] ?? null,
            'dtxsid' => $dtxsid,
            'name' => $name,
            'value' => $item['value'] ?? $item['propValue'] ?? $item['prop_value_string'] ?? $item['prop_value'] ?? null,
            'unit' => $item['unit'] ?? $item['propUnit'] ?? $item['prop_unit'] ?? null,
            'propType' => $item['propType'] ?? $item['prop_type'] ?? $item['valueType'] ?? null,
            'source' => $item['source']
                ?? $item['sourceName']
                ?? $item['source_name']
                ?? $item['modelName']
                ?? $item['model_name']
                ?? $item['dataset']
                ?? null,
            'propertyId' => $item['propertyId']
                ?? $item['property_string_id']
                ?? $item['propValueId']
                ?? $item['prop_value_id']
                ?? null,
            'source_json' => $item,
        ];
    }

    private function rowLooksLikePropertyValue(array $row): bool
    {
        return $row['value'] !== null || $row['unit'] !== null || $row['propertyId'] !== null;
    }

    private function extractFateItems(mixed $rawFate, string $payloadDtxid): array
    {
        $rows = [];
        $fateItems = $this->normalizeItems($rawFate);

        foreach ($fateItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            // New COMPTox shape: [{ dtxsid, properties: [{ propName, predictedFateData[], experimentalFateData[] }] }]
            if (isset($item['properties']) && is_array($item['properties'])) {
                $topDtxid = (string) ($item['dtxsid'] ?? '');
                if ($topDtxid !== '' && $topDtxid !== $payloadDtxid) {
                    continue;
                }

                foreach ($item['properties'] as $propertyRow) {
                    if (! is_array($propertyRow)) {
                        continue;
                    }

                    $endpointName = (string) ($propertyRow['propName'] ?? $propertyRow['prop_name'] ?? '');
                    if (! $this->isWantedFateEndpoint($endpointName)) {
                        continue;
                    }

                    foreach (['predictedFateData', 'experimentalFateData'] as $bucket) {
                        if (! isset($propertyRow[$bucket]) || ! is_array($propertyRow[$bucket])) {
                            continue;
                        }

                        foreach ($propertyRow[$bucket] as $entry) {
                            if (! is_array($entry)) {
                                continue;
                            }

                            $entryDtxid = (string) ($entry['dtxsid'] ?? $topDtxid ?: $payloadDtxid);
                            if ($entryDtxid !== '' && $entryDtxid !== $payloadDtxid) {
                                continue;
                            }

                            $rows[] = [
                                'id' => $entry['id'] ?? null,
                                'dtxsid' => $entryDtxid ?: $payloadDtxid,
                                'endpointName' => (string) ($entry['prop_name'] ?? $endpointName),
                                'resultValue' => $entry['prop_value_string'] ?? $entry['prop_value'] ?? null,
                                'modelSource' => $entry['source_name'] ?? $entry['model_name'] ?? null,
                                'unit' => $entry['prop_unit'] ?? null,
                                'valueType' => $entry['prop_type'] ?? ($bucket === 'experimentalFateData' ? 'experimental' : 'predicted'),
                                '_source' => $entry,
                            ];
                        }
                    }
                }

                continue;
            }

            // Original PBMT-like flat shape.
            $itemDtxid = (string) ($item['dtxsid'] ?? $item['dtxid'] ?? '');
            if ($itemDtxid !== '' && $itemDtxid !== $payloadDtxid) {
                continue;
            }

            $endpointName = (string) ($item['endpointName'] ?? $item['prop_name'] ?? '');
            if (! $this->isWantedFateEndpoint($endpointName)) {
                continue;
            }

            $rows[] = [
                'id' => $item['id'] ?? null,
                'dtxsid' => $itemDtxid ?: $payloadDtxid,
                'endpointName' => $endpointName,
                'resultValue' => $item['resultValue'] ?? $item['prop_value_string'] ?? $item['prop_value'] ?? null,
                'modelSource' => $item['modelSource'] ?? $item['source_name'] ?? $item['model_name'] ?? null,
                'unit' => $item['unit'] ?? $item['prop_unit'] ?? null,
                'valueType' => $item['valueType'] ?? $item['prop_type'] ?? null,
                '_source' => $item,
            ];
        }

        return $rows;
    }

    private function isWantedFateEndpoint(string $endpointName): bool
    {
        return in_array($endpointName, self::WANTED_FATE_ENDPOINTS, true);
    }

    private function isWantedPropertyName(string $propertyName): bool
    {
        $normalizedPropertyName = $this->normalizePropertyName($propertyName);
        if ($normalizedPropertyName === '') {
            return false;
        }

        foreach (self::WANTED_PROPERTY_NAMES as $wantedPropertyName) {
            if ($this->normalizePropertyName($wantedPropertyName) === $normalizedPropertyName) {
                return true;
            }
        }

        return false;
    }

    private function normalizePropertyName(string $value): string
    {
        $normalized = str_replace(
            ["\xc2\xa0", "\xe2\x80\x93", "\xe2\x80\x94"],
            [' ', '-', '-'],
            trim($value)
        );

        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return strtolower($normalized);
    }

    private function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }

    private function acquireLock(): ?Lock
    {
        try {
            $lock = Cache::lock('hazards:parse:lock', 3600);
            if ($lock->get()) {
                return $lock;
            }
        } catch (\Throwable $e) {
            Log::warning('Hazards parse default cache lock failed, trying file lock.', [
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $lock = Cache::store('file')->lock('hazards:parse:lock', 3600);
            if ($lock->get()) {
                return $lock;
            }
        } catch (\Throwable $e) {
            Log::warning('Hazards parse file cache lock failed.', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
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
        ?string $firstPayloadErrorMessage,
        int $failedPayloads
    ): ?string {
        if ($allProcessedFailed) {
            return $firstPayloadErrorMessage ?: 'All payload parses failed.';
        }

        if ($failedPayloads > 0) {
            return $firstPayloadErrorMessage ?: 'Some payloads failed during parse.';
        }

        return null;
    }
}
