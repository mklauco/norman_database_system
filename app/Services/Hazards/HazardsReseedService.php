<?php

declare(strict_types=1);

namespace App\Services\Hazards;

use App\Models\Hazards\ComptoxDetailRecord;
use App\Models\Hazards\ComptoxFateRecord;
use App\Models\Hazards\ComptoxPropertyRecord;
use App\Models\Hazards\ComptoxSubstanceData;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the derived hazards data (hazards_comptox_substance_data and the automatic
 * derivation selections and classifications) from the hazards source tables.
 *
 * Identity of a derived row is (source_key, data_domain), never an auto-increment id, so
 * a reseed of the source tables keeps derivation votes attached to the same substance and
 * parameter. The rebuild is idempotent: running it twice in a row changes nothing.
 */
class HazardsReseedService
{
    private const SOURCE_CHUNK = 300;

    private const INSERT_BATCH = 400;

    private const LOOKUP_BATCH = 4000;

    private const DELETE_CHUNK = 1000;

    private const SOURCE_TYPES = ['fate', 'property', 'janus', 'pikme'];

    /** @var array<string, int> */
    private array $totals = [];

    /** @var array<string, array<string, int>> */
    private array $bySource = [];

    /** @var array<int, true> */
    private array $affectedSubstances = [];

    public function __construct(
        private readonly ComptoxSubstanceDataFillService $mapper,
        private readonly HazardsClassificationService $classificationService
    ) {}

    /**
     * @return array{
     *     inserted: int, updated: int, unchanged: int, withdrawn: int, restored: int,
     *     deleted: int, kept_withdrawn: int, skipped_unresolved: int, substances_recomputed: int,
     *     by_source: array<string, array<string, int>>
     * }
     */
    public function rebuild(): array
    {
        $this->prepareLongRun();
        $this->resetCounters();

        $this->syncFateRecords();
        $this->syncPropertyRecords();
        $this->syncJanusRows();
        $this->syncPikmeRows();

        foreach (self::SOURCE_TYPES as $type) {
            $this->withdrawRowsOfRemovedSources($type);
        }

        $this->collectSubstancesWithoutAutoSelection();
        $this->recomputeAffectedSubstances();
        $this->deleteUnreferencedWithdrawnRows();

        return $this->totals + ['by_source' => $this->bySource];
    }

    private function prepareLongRun(): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        DB::disableQueryLog();
    }

    private function resetCounters(): void
    {
        $this->totals = [
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'withdrawn' => 0,
            'restored' => 0,
            'deleted' => 0,
            'kept_withdrawn' => 0,
            'skipped_unresolved' => 0,
            'substances_recomputed' => 0,
        ];

        $this->bySource = [];
        foreach (self::SOURCE_TYPES as $type) {
            $this->bySource[$type] = [
                'inserted' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'withdrawn' => 0,
                'restored' => 0,
            ];
        }

        $this->affectedSubstances = [];
    }

    private function syncFateRecords(): void
    {
        ComptoxFateRecord::query()
            ->orderBy('id')
            ->chunkById(self::SOURCE_CHUNK, function (Collection $records): void {
                $context = $this->loadIdentityContext(
                    $records->pluck('dtxid')->filter()->unique()->values()->all(),
                    $records->pluck('susdat_substance_id')->filter()->unique()->values()->all()
                );

                $entries = [];
                foreach ($records as $record) {
                    $detail = $context['details_by_dtxid'][$record->dtxid] ?? null;
                    $substanceId = $this->nullableInt($record->susdat_substance_id ?? $detail?->susdat_substance_id);
                    $identity = $this->mapper->buildIdentity(
                        $substanceId,
                        $record->dtxid,
                        $detail,
                        $substanceId !== null ? ($context['susdat'][$substanceId] ?? null) : null
                    );

                    $entries[] = $this->mapper->mapFateRecord($record, $identity);
                }

                $this->applyChunk('fate', $entries, array_column($entries, 'source_key'));
            });
    }

    private function syncPropertyRecords(): void
    {
        ComptoxPropertyRecord::query()
            ->orderBy('id')
            ->chunkById(self::SOURCE_CHUNK, function (Collection $records): void {
                $context = $this->loadIdentityContext(
                    $records->pluck('dtxid')->filter()->unique()->values()->all(),
                    $records->pluck('susdat_substance_id')->filter()->unique()->values()->all()
                );

                $entries = [];
                foreach ($records as $record) {
                    $detail = $context['details_by_dtxid'][$record->dtxid] ?? null;
                    $substanceId = $this->nullableInt($record->susdat_substance_id ?? $detail?->susdat_substance_id);
                    $identity = $this->mapper->buildIdentity(
                        $substanceId,
                        $record->dtxid,
                        $detail,
                        $substanceId !== null ? ($context['susdat'][$substanceId] ?? null) : null
                    );

                    $entries[] = $this->mapper->mapPropertyRecord($record, $identity);
                }

                $this->applyChunk('property', $entries, array_column($entries, 'source_key'));
            });
    }

    private function syncJanusRows(): void
    {
        DB::table('hazards_janus')
            ->orderBy('id')
            ->chunkById(self::SOURCE_CHUNK, function (Collection $rows): void {
                $substanceIds = $rows->pluck('susdat_substance_id')->filter()->unique()->values()->all();
                $context = $this->loadIdentityContext([], $substanceIds);

                $entries = [];
                $lookupKeys = [];

                foreach ($rows as $row) {
                    foreach (ComptoxSubstanceDataFillService::JANUS_PARAMETER_KEYS as $parameter) {
                        $lookupKeys[] = 'janus:'.$row->norman_id.':'.$parameter;
                    }

                    $substanceId = $this->nullableInt($row->susdat_substance_id);
                    if ($substanceId === null) {
                        $this->totals['skipped_unresolved']++;

                        continue;
                    }

                    $susdat = $context['susdat'][$substanceId] ?? null;
                    $identity = $this->mapper->buildIdentity(
                        $substanceId,
                        $susdat?->dtxid ?? $row->dtxid,
                        $context['details_by_substance'][$substanceId] ?? null,
                        $susdat,
                        $row->smiles
                    );

                    foreach ($this->mapper->mapJanusRows($row, $identity) as $entry) {
                        $entries[] = $entry;
                    }
                }

                $this->applyChunk('janus', $entries, $lookupKeys);
            });
    }

    private function syncPikmeRows(): void
    {
        DB::table('hazards_pikme')
            ->orderBy('id')
            ->chunkById(self::SOURCE_CHUNK, function (Collection $rows): void {
                $substanceIds = $rows->pluck('susdat_substance_id')->filter()->unique()->values()->all();
                $context = $this->loadIdentityContext([], $substanceIds);

                $entries = [];
                $lookupKeys = [];

                foreach ($rows as $row) {
                    foreach (ComptoxSubstanceDataFillService::PIKME_PARAMETER_KEYS as $parameter) {
                        $lookupKeys[] = 'pikme:'.$row->dtxid.':'.$parameter;
                    }

                    $substanceId = $this->nullableInt($row->susdat_substance_id);
                    if ($substanceId === null) {
                        $this->totals['skipped_unresolved']++;

                        continue;
                    }

                    $identity = $this->mapper->buildIdentity(
                        $substanceId,
                        (string) $row->dtxid,
                        $context['details_by_substance'][$substanceId] ?? null,
                        $context['susdat'][$substanceId] ?? null
                    );

                    foreach ($this->mapper->mapPikmeRows($row, $identity) as $entry) {
                        $entries[] = $entry;
                    }
                }

                $this->applyChunk('pikme', $entries, $lookupKeys);
            });
    }

    /**
     * Identity lookups for one chunk: CompTox detail records by dtxid and by substance id,
     * and read-only susdat_substances rows by id.
     *
     * @param  list<string>  $dtxids
     * @param  list<int|string>  $substanceIds
     * @return array{details_by_dtxid: array<string, object>, details_by_substance: array<int, object>, susdat: array<int, object>}
     */
    private function loadIdentityContext(array $dtxids, array $substanceIds): array
    {
        $detailsByDtxid = [];
        $detailsBySubstance = [];

        if ($dtxids !== []) {
            $details = ComptoxDetailRecord::query()
                ->select(['id', 'susdat_substance_id', 'dtxid', 'preferred_name', 'casrn', 'inchikey', 'smiles'])
                ->whereIn('dtxid', $dtxids)
                ->orderBy('id')
                ->get();

            foreach ($details as $detail) {
                $detailsByDtxid[(string) $detail->dtxid] = $detail;
            }
        }

        if ($substanceIds !== []) {
            $details = ComptoxDetailRecord::query()
                ->select(['id', 'susdat_substance_id', 'dtxid', 'preferred_name', 'casrn', 'inchikey', 'smiles'])
                ->whereIn('susdat_substance_id', $substanceIds)
                ->orderBy('id')
                ->get();

            foreach ($details as $detail) {
                $detailsBySubstance[(int) $detail->susdat_substance_id] = $detail;
            }
        }

        $allSubstanceIds = array_values(array_unique(array_merge(
            array_map('intval', $substanceIds),
            array_map(
                static fn (object $detail): int => (int) $detail->susdat_substance_id,
                array_filter($detailsByDtxid, static fn (object $detail): bool => $detail->susdat_substance_id !== null)
            )
        )));

        $susdat = [];
        if ($allSubstanceIds !== []) {
            $rows = DB::table('susdat_substances')
                ->select(['id', 'dtxid', 'name', 'name_dashboard', 'cas_number', 'stdinchikey', 'smiles'])
                ->whereIn('id', $allSubstanceIds)
                ->get();

            foreach ($rows as $row) {
                $susdat[(int) $row->id] = $row;
            }
        }

        return [
            'details_by_dtxid' => $detailsByDtxid,
            'details_by_substance' => $detailsBySubstance,
            'susdat' => $susdat,
        ];
    }

    /**
     * Writes one chunk: inserts new rows, updates changed rows, restores rows that are
     * produced again and withdraws existing rows of the looked-up keys that were not produced.
     *
     * @param  list<array{source_key: string, data_domain: string, attributes: array<string, mixed>}>  $entries
     * @param  list<string>  $lookupKeys  every key that this chunk of source rows can produce
     */
    private function applyChunk(string $type, array $entries, array $lookupKeys): void
    {
        if ($entries === [] && $lookupKeys === []) {
            return;
        }

        DB::transaction(function () use ($type, $entries, $lookupKeys): void {
            $existing = [];
            foreach (array_chunk(array_values(array_unique($lookupKeys)), self::LOOKUP_BATCH) as $keys) {
                $rows = ComptoxSubstanceData::query()->whereIn('source_key', $keys)->get();
                foreach ($rows as $row) {
                    $existing[$row->source_key.'|'.$row->data_domain] = $row;
                }
            }

            $now = now();
            $produced = [];
            $inserts = [];

            foreach ($entries as $entry) {
                $identity = $entry['source_key'].'|'.$entry['data_domain'];
                $produced[$identity] = true;
                $attributes = $entry['attributes'];
                $row = $existing[$identity] ?? null;

                if ($row === null) {
                    $inserts[] = $attributes + [
                        'withdrawn_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $this->count($type, 'inserted');
                    $this->markAffected($attributes['susdat_substance_id'] ?? null);

                    continue;
                }

                if ($row->date !== null) {
                    unset($attributes['date']);
                }

                $previousSubstanceId = $row->susdat_substance_id;
                $wasWithdrawn = $row->withdrawn_at !== null;

                $row->forceFill($attributes);
                if ($wasWithdrawn) {
                    $row->withdrawn_at = null;
                }

                if (! $row->isDirty()) {
                    $this->count($type, 'unchanged');

                    continue;
                }

                $row->save();
                $this->count($type, $wasWithdrawn ? 'restored' : 'updated');
                $this->markAffected($row->susdat_substance_id);
                $this->markAffected($previousSubstanceId);
            }

            $withdrawIds = [];
            foreach ($existing as $identity => $row) {
                if (isset($produced[$identity]) || $row->withdrawn_at !== null) {
                    continue;
                }

                $withdrawIds[] = (int) $row->id;
                $this->markAffected($row->susdat_substance_id);
            }

            foreach (array_chunk($inserts, self::INSERT_BATCH) as $batch) {
                ComptoxSubstanceData::query()->insert($batch);
            }

            if ($withdrawIds !== []) {
                ComptoxSubstanceData::query()
                    ->whereIn('id', $withdrawIds)
                    ->update(['withdrawn_at' => $now]);
                $this->count($type, 'withdrawn', count($withdrawIds));
            }
        });
    }

    /**
     * Withdraws active rows of one source type whose source record no longer exists.
     */
    private function withdrawRowsOfRemovedSources(string $type): void
    {
        ComptoxSubstanceData::query()
            ->where('source_record_type', $type)
            ->whereNull('withdrawn_at')
            ->chunkById(self::DELETE_CHUNK, function (Collection $rows) use ($type): void {
                $parts = [];
                foreach ($rows as $row) {
                    $segments = explode(':', (string) $row->source_key, 3);
                    $parts[$row->id] = [$segments[1] ?? '', $segments[2] ?? ''];
                }

                $present = $this->presentSourceEntities($type, $parts);

                $withdrawIds = [];
                foreach ($rows as $row) {
                    if (isset($present[$row->id])) {
                        continue;
                    }

                    $withdrawIds[] = (int) $row->id;
                    $this->markAffected($row->susdat_substance_id);
                }

                if ($withdrawIds !== []) {
                    ComptoxSubstanceData::query()
                        ->whereIn('id', $withdrawIds)
                        ->update(['withdrawn_at' => now()]);
                    $this->count($type, 'withdrawn', count($withdrawIds));
                }
            });
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $parts  row id => [entity, parameter or record id]
     * @return array<int, true> row ids whose source record still exists
     */
    private function presentSourceEntities(string $type, array $parts): array
    {
        $entities = array_values(array_unique(array_column($parts, 0)));
        $present = [];

        if ($type === 'janus' || $type === 'pikme') {
            $table = $type === 'janus' ? 'hazards_janus' : 'hazards_pikme';
            $column = $type === 'janus' ? 'norman_id' : 'dtxid';
            $existing = array_flip(DB::table($table)->whereIn($column, $entities)->pluck($column)->all());

            foreach ($parts as $rowId => [$entity]) {
                if (isset($existing[$entity])) {
                    $present[$rowId] = true;
                }
            }

            return $present;
        }

        $model = $type === 'fate' ? ComptoxFateRecord::class : ComptoxPropertyRecord::class;
        $recordIds = array_values(array_unique(array_filter(array_column($parts, 1), 'is_numeric')));
        $dtxidByRecord = $model::query()->whereIn('id', $recordIds)->pluck('dtxid', 'id')->all();

        foreach ($parts as $rowId => [$dtxid, $recordId]) {
            if (isset($dtxidByRecord[(int) $recordId]) && (string) $dtxidByRecord[(int) $recordId] === $dtxid) {
                $present[$rowId] = true;
            }
        }

        return $present;
    }

    /**
     * Substances with active data but no current auto selection are recomputed as well,
     * so an interrupted rebuild is repaired by the next one.
     */
    private function collectSubstancesWithoutAutoSelection(): void
    {
        $ids = ComptoxSubstanceData::query()
            ->whereNull('withdrawn_at')
            ->whereNotNull('susdat_substance_id')
            ->whereNotExists(function (Builder $query): void {
                $query->from('hazards_derivation_selections as selections')
                    ->whereColumn('selections.susdat_substance_id', 'hazards_comptox_substance_data.susdat_substance_id')
                    ->where('selections.kind', 'auto')
                    ->where('selections.is_current', true);
            })
            ->distinct()
            ->orderBy('susdat_substance_id')
            ->pluck('susdat_substance_id');

        foreach ($ids as $id) {
            $this->markAffected($id);
        }
    }

    private function recomputeAffectedSubstances(): void
    {
        $ids = array_keys($this->affectedSubstances);
        sort($ids);

        foreach (array_chunk($ids, 200) as $chunk) {
            $this->totals['substances_recomputed'] += $this->classificationService->runMany($chunk);
        }
    }

    /**
     * Deletes withdrawn rows that no selection or metadata snapshot refers to. Referenced
     * rows stay (withdrawn) so that votes keep their source row.
     */
    private function deleteUnreferencedWithdrawnRows(): void
    {
        $kept = 0;

        ComptoxSubstanceData::query()
            ->whereNotNull('withdrawn_at')
            ->chunkById(self::DELETE_CHUNK, function (Collection $rows) use (&$kept): void {
                $ids = $rows->pluck('id')->map(static fn ($id): int => (int) $id)->all();

                $referenced = array_merge(
                    DB::table('hazards_derivation_selections')->whereIn('hazards_substance_data_id', $ids)->pluck('hazards_substance_data_id')->all(),
                    DB::table('hazards_derivation_metadata')->whereIn('hazards_substance_data_id', $ids)->pluck('hazards_substance_data_id')->all()
                );

                $deletable = array_values(array_diff($ids, array_map('intval', $referenced)));
                $kept += count($ids) - count($deletable);

                if ($deletable !== []) {
                    ComptoxSubstanceData::query()->whereIn('id', $deletable)->delete();
                    $this->totals['deleted'] += count($deletable);
                }
            });

        $this->totals['kept_withdrawn'] = $kept;
    }

    private function count(string $type, string $key, int $by = 1): void
    {
        $this->bySource[$type][$key] += $by;
        $this->totals[$key] += $by;
    }

    private function markAffected(mixed $substanceId): void
    {
        $id = $this->nullableInt($substanceId);
        if ($id !== null && $id > 0) {
            $this->affectedSubstances[$id] = true;
        }
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
