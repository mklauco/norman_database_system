<?php

namespace App\Services\Hazards;

use App\Models\Hazards\ClassificationSupport;
use App\Models\Hazards\ClassificationVote;
use App\Models\Hazards\DerivationMetadata;
use App\Models\Hazards\DerivationSelection;
use App\Models\Hazards\SubstanceClassification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HazardsClassificationService
{
    private const CRITERIA = ['P', 'B', 'M', 'T'];

    private const GROUP_SIZE = 25;

    public function __construct(
        private readonly HazardsDerivationService $derivationService
    ) {}

    public function run(int|string $susdatSubstanceId): void
    {
        $this->runMany([(int) $susdatSubstanceId]);
    }

    /**
     * Recomputes the automatic selections, the auto baseline and the derivation conclusion
     * of many substances. Substances are written in short transactions of GROUP_SIZE; each
     * group reads what it needs with a handful of whereIn queries and writes in bulk.
     *
     * @param  list<int|string>  $substanceIds
     */
    public function runMany(array $substanceIds): int
    {
        $substanceIds = array_values(array_unique(array_filter(array_map('intval', $substanceIds), static fn (int $id): bool => $id > 0)));

        foreach (array_chunk($substanceIds, self::GROUP_SIZE) as $group) {
            DB::transaction(fn () => $this->recomputeGroup($group));
        }

        return count($substanceIds);
    }

    /**
     * @param  list<int>  $ids
     */
    private function recomputeGroup(array $ids): void
    {
        $now = now();
        $rowsBySubstance = $this->derivationService->loadCandidateRowsForMany($ids);

        $selections = DerivationSelection::query()
            ->with('hazardsSubstanceData')
            ->whereIn('susdat_substance_id', $ids)
            ->where('is_current', true)
            ->where(function ($query): void {
                $query->where('kind', 'auto')
                    ->orWhere(fn ($vote) => $vote->where('kind', 'vote')->whereNotNull('user_id'));
            })
            ->get();

        $votes = $selections->where('kind', 'vote');
        if ($votes->isNotEmpty()) {
            $votes->load('user');
        }
        $voteMetadata = $this->getLatestMetadataMapForSelections($votes);
        $autosBySubstance = $selections->where('kind', 'auto')->groupBy('susdat_substance_id');
        $votesBySubstance = $votes->groupBy('susdat_substance_id');

        $classifications = SubstanceClassification::query()
            ->with('supports')
            ->whereIn('susdat_substance_id', $ids)
            ->where('is_current', true)
            ->where(function ($query): void {
                $query->where('kind', 'derivation')
                    ->orWhere(fn ($baseline) => $baseline->where('kind', 'auto_baseline')->whereNull('editor_user_id'));
            })
            ->orderBy('id')
            ->get();
        $baselineBySubstance = $classifications->where('kind', 'auto_baseline')->keyBy('susdat_substance_id');
        $derivationBySubstance = $classifications->where('kind', 'derivation')->keyBy('susdat_substance_id');

        $plans = [];
        $deactivateSelections = [];
        $newSelections = [];
        foreach ($ids as $id) {
            $plans[$id] = $this->derivationService->planAutoSelections(
                $rowsBySubstance->get($id, collect()),
                $autosBySubstance->get($id, collect())
            );
            array_push($deactivateSelections, ...$plans[$id]['deactivate']);
            foreach ($plans[$id]['create'] as $spec) {
                $newSelections[] = [
                    'susdat_substance_id' => $id,
                    'bucket' => $spec['bucket'],
                    'hazards_substance_data_id' => (int) $spec['row']->id,
                    'source_label' => $spec['label'],
                    'kind' => 'auto',
                    'user_id' => null,
                    'is_current' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($deactivateSelections !== []) {
            DerivationSelection::query()->whereIn('id', $deactivateSelections)->update(['is_current' => false, 'updated_at' => $now]);
        }

        $createdIds = [];
        foreach ($this->bulkInsert('hazards_derivation_selections', $newSelections, ['id', 'susdat_substance_id', 'bucket']) as $returned) {
            $createdIds[$returned->susdat_substance_id.'|'.$returned->bucket] = (int) $returned->id;
        }

        $deactivateClassifications = [];
        $updatedBaselines = [];
        $newClassifications = [];
        $supportsOfExisting = [];

        foreach ($ids as $id) {
            $autos = collect($plans[$id]['keep']);
            foreach ($plans[$id]['create'] as $spec) {
                $selection = new DerivationSelection([
                    'susdat_substance_id' => $id,
                    'bucket' => $spec['bucket'],
                    'hazards_substance_data_id' => (int) $spec['row']->id,
                    'source_label' => $spec['label'],
                    'kind' => 'auto',
                    'user_id' => null,
                    'is_current' => true,
                ]);
                $selection->id = $createdIds[$id.'|'.$spec['bucket']];
                $selection->setRelation('hazardsSubstanceData', $spec['row']);
                $autos->push($selection);
            }

            $currentVotes = $votesBySubstance->get($id, collect());

            $baseline = $baselineBySubstance->get($id);
            if ($autos->isEmpty()) {
                if ($baseline) {
                    $deactivateClassifications[] = (int) $baseline->id;
                }
            } else {
                $payload = $this->buildDerivationConclusionPayload($autos, collect(), $voteMetadata);
                $supports = $this->buildDerivationSupportRows($autos, collect(), $payload, $voteMetadata);
                $payloadMatches = $baseline && $this->snapshotMatches($baseline, $payload);

                if (! ($payloadMatches && $this->supportsMatch($baseline, $supports))) {
                    if ($baseline) {
                        if (! $payloadMatches) {
                            $baseline->fill($payload + ['is_current' => true]);
                            $updatedBaselines[] = $baseline;
                        }
                        $supportsOfExisting[(int) $baseline->id] = $supports;
                    } else {
                        $newClassifications[$id.'|auto_baseline'] = [
                            'row' => $payload + [
                                'susdat_substance_id' => $id,
                                'editor_user_id' => null,
                                'kind' => 'auto_baseline',
                                'is_current' => true,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ],
                            'supports' => $supports,
                        ];
                    }
                }
            }

            $derivation = $derivationBySubstance->get($id);
            if ($currentVotes->isEmpty()) {
                if ($derivation) {
                    $deactivateClassifications[] = (int) $derivation->id;
                }

                continue;
            }

            $payload = $this->buildDerivationConclusionPayload($autos, $currentVotes, $voteMetadata);
            $supports = $this->buildDerivationSupportRows($autos, $currentVotes, $payload, $voteMetadata);

            if ($derivation && $this->snapshotMatches($derivation, $payload) && $this->supportsMatch($derivation, $supports)) {
                continue;
            }

            if ($derivation) {
                $deactivateClassifications[] = (int) $derivation->id;
            }
            $newClassifications[$id.'|derivation'] = [
                'row' => $payload + [
                    'susdat_substance_id' => $id,
                    'editor_user_id' => null,
                    'kind' => 'derivation',
                    'is_current' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                'supports' => $supports,
            ];
        }

        if ($deactivateClassifications !== []) {
            SubstanceClassification::query()->whereIn('id', $deactivateClassifications)->update(['is_current' => false, 'updated_at' => $now]);
        }

        foreach ($updatedBaselines as $baseline) {
            $baseline->save();
        }

        if ($supportsOfExisting !== []) {
            ClassificationSupport::query()->whereIn('substance_classification_id', array_keys($supportsOfExisting))->delete();
        }

        $supportRows = [];
        foreach ($supportsOfExisting as $classificationId => $supports) {
            foreach ($supports as $support) {
                $supportRows[] = $support + ['substance_classification_id' => $classificationId, 'created_at' => $now, 'updated_at' => $now];
            }
        }

        $keys = array_keys($newClassifications);
        $inserted = $this->bulkInsert('hazards_substance_classifications', array_column($newClassifications, 'row'), ['id', 'susdat_substance_id', 'kind']);
        $classificationIds = [];
        foreach ($inserted as $returned) {
            $classificationIds[$returned->susdat_substance_id.'|'.$returned->kind] = (int) $returned->id;
        }
        foreach ($keys as $key) {
            foreach ($newClassifications[$key]['supports'] as $support) {
                $supportRows[] = $support + ['substance_classification_id' => $classificationIds[$key], 'created_at' => $now, 'updated_at' => $now];
            }
        }

        $this->bulkInsert('hazards_classification_supports', $supportRows);
    }

    /**
     * Multi-row insert, optionally returning columns of the inserted rows.
     *
     * @param  list<array<string, mixed>>  $rows  all with the same keys
     * @param  list<string>  $returning
     * @return list<object>
     */
    private function bulkInsert(string $table, array $rows, array $returning = []): array
    {
        $returned = [];

        foreach (array_chunk($rows, 400) as $chunk) {
            $columns = array_keys($chunk[0]);
            $tuple = '('.implode(',', array_fill(0, count($columns), '?')).')';
            $sql = 'insert into "'.$table.'" ("'.implode('","', $columns).'") values '.implode(',', array_fill(0, count($chunk), $tuple));
            $bindings = [];
            foreach ($chunk as $row) {
                foreach ($columns as $column) {
                    $bindings[] = $row[$column];
                }
            }

            if ($returning === []) {
                DB::insert($sql, $bindings);

                continue;
            }

            $sql .= ' returning "'.implode('","', $returning).'"';
            array_push($returned, ...DB::selectFromWriteConnection($sql, $bindings));
        }

        return $returned;
    }

    public function insertUserConclusionSnapshot(int|string $susdatSubstanceId, int $userId): ?SubstanceClassification
    {
        $substanceId = (int) $susdatSubstanceId;
        $userId = (int) $userId;

        if ($substanceId <= 0 || $userId <= 0) {
            return null;
        }

        $autos = DerivationSelection::query()
            ->with('hazardsSubstanceData')
            ->where('susdat_substance_id', $substanceId)
            ->where('kind', 'auto')
            ->where('is_current', true)
            ->get();

        $votes = ClassificationVote::query()
            ->where('susdat_substance_id', $substanceId)
            ->where('user_id', $userId)
            ->where('is_current', true)
            ->get();

        if ($autos->isEmpty() && $votes->isEmpty()) {
            return null;
        }

        $payload = $this->buildClassificationConclusionPayload($autos, $votes);
        $supports = $this->buildClassificationSupportRows($autos, $votes, $payload);

        return DB::transaction(function () use ($substanceId, $userId, $payload, $supports) {
            SubstanceClassification::query()
                ->where('susdat_substance_id', $substanceId)
                ->where('editor_user_id', $userId)
                ->where('kind', 'classification')
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $row = SubstanceClassification::create($payload + [
                'susdat_substance_id' => $substanceId,
                'editor_user_id' => $userId,
                'kind' => 'classification',
                'is_current' => true,
            ]);

            $this->syncSupports($row, $supports);

            return $row;
        });
    }

    public function storeVotes(int|string $susdatSubstanceId, int $userId, array $votes): void
    {
        $substanceId = (int) $susdatSubstanceId;
        $userId = (int) $userId;

        if ($substanceId <= 0 || $userId <= 0) {
            return;
        }

        DB::transaction(function () use ($substanceId, $userId, $votes) {
            DB::select('select pg_advisory_xact_lock(?, ?)', [$userId, $substanceId]);
            $touchedPairs = [];

            foreach ($votes as $vote) {
                $classificationType = trim((string) ($vote['classification_type'] ?? ''));
                $criterion = strtoupper(trim((string) ($vote['criterion'] ?? '')));
                $classificationCode = trim((string) ($vote['classification_code'] ?? ''));
                $voteValueRaw = $vote['vote_value'] ?? null;
                $voteValue = is_numeric($voteValueRaw) ? (int) $voteValueRaw : null;

                if (
                    $classificationType === ''
                    || ! in_array($criterion, self::CRITERIA, true)
                    || $classificationCode === ''
                ) {
                    continue;
                }

                $touchedPairs[] = $classificationType.'|'.$criterion;

                $current = ClassificationVote::query()
                    ->where('susdat_substance_id', $substanceId)
                    ->where('user_id', $userId)
                    ->where('classification_type', $classificationType)
                    ->where('criterion', $criterion)
                    ->where('is_current', true)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($voteValue === null || $voteValue < 1 || $voteValue > 3) {
                    if ($current) {
                        $current->update(['is_current' => false]);
                    }

                    continue;
                }

                if (
                    $current
                    && $current->classification_code === $classificationCode
                    && (int) $current->vote_value === $voteValue
                ) {
                    continue;
                }

                if ($current) {
                    $current->update(['is_current' => false]);
                }

                ClassificationVote::create([
                    'susdat_substance_id' => $substanceId,
                    'user_id' => $userId,
                    'classification_type' => $classificationType,
                    'criterion' => $criterion,
                    'classification_code' => $classificationCode,
                    'vote_value' => $voteValue,
                    'is_current' => true,
                ]);
            }

            $this->insertUserConclusionSnapshot($substanceId, $userId);
        });
    }

    private function buildDerivationConclusionPayload(Collection $autoSelections, Collection $derivationVotes, ?Collection $voteMetadata = null): array
    {
        $autoScores = $this->resolveSelectionCodes($autoSelections, $voteMetadata);
        $voteScores = $this->resolveSelectionCodes($derivationVotes, $voteMetadata);

        return $this->buildPayloadFromScores(
            autoScores: $autoScores,
            voteScores: $voteScores,
            sourceType: $this->deriveSourceType($derivationVotes)
        );
    }

    private function buildClassificationConclusionPayload(Collection $autoSelections, Collection $classificationVotes): array
    {
        $selectionCodes = $this->resolveSelectionCodes($autoSelections);
        $voteScores = $this->collectVoteScores($classificationVotes);

        return $this->buildPayloadFromScores(
            autoScores: $selectionCodes,
            voteScores: $voteScores,
            sourceType: $this->deriveVoteSourceType($classificationVotes)
        );
    }

    private function buildDerivationSupportRows(Collection $autoSelections, Collection $derivationVotes, array $payload, ?Collection $voteMetadata = null): array
    {
        return array_values(array_merge(
            $this->buildSelectionSupportRows($autoSelections, 'auto', $payload, $voteMetadata),
            $this->buildSelectionSupportRows($derivationVotes, 'derivation_vote', $payload, $voteMetadata)
        ));
    }

    private function buildClassificationSupportRows(Collection $autoSelections, Collection $classificationVotes, array $payload): array
    {
        return array_values(array_merge(
            $this->buildSelectionSupportRows($autoSelections, 'auto', $payload),
            $this->buildClassificationVoteSupportRows($classificationVotes, $payload)
        ));
    }

    private function buildSelectionSupportRows(Collection $selections, string $originType, array $payload, ?Collection $voteMetadata = null): array
    {
        if ($selections->isEmpty()) {
            return [];
        }

        $metadataBySelection = $voteMetadata ?? $this->getLatestMetadataMapForSelections($selections);
        $supports = [];

        foreach ($selections as $selection) {
            $criterion = strtoupper(substr((string) $selection->bucket, 0, 1));
            if (! in_array($criterion, self::CRITERIA, true)) {
                continue;
            }

            $metadata = $selection->kind === 'vote' ? $metadataBySelection->get($selection->id) : null;
            $code = $this->normalizeCode(
                $metadata?->norman_classification
                    ?? $selection->hazardsSubstanceData?->assessment_class
            );
            $points = (int) ($metadata?->norman_vote ?? $this->defaultSelectionVote($selection));

            if ($code === null || $points <= 0) {
                continue;
            }

            $supports[] = [
                'susdat_substance_id' => (int) $selection->susdat_substance_id,
                'criterion' => $criterion,
                'classification_code' => $code,
                'points' => $points,
                'source_type' => $this->resolveSelectionSupportSourceType($selection),
                'origin_type' => $originType,
                'origin_user_id' => $selection->user_id ? (int) $selection->user_id : null,
                'derivation_selection_id' => (int) $selection->id,
                'classification_vote_id' => null,
                'is_winner' => ($payload[$criterion] ?? null) === $code,
            ];
        }

        return $supports;
    }

    private function buildClassificationVoteSupportRows(Collection $classificationVotes, array $payload): array
    {
        $supports = [];

        foreach ($classificationVotes as $vote) {
            $criterion = strtoupper((string) ($vote->criterion ?? ''));
            $code = $this->normalizeCode($vote->classification_code ?? null);
            $points = (int) ($vote->vote_value ?? 0);

            if (! in_array($criterion, self::CRITERIA, true) || $code === null || $points <= 0) {
                continue;
            }

            $supports[] = [
                'susdat_substance_id' => (int) $vote->susdat_substance_id,
                'criterion' => $criterion,
                'classification_code' => $code,
                'points' => $points,
                'source_type' => $vote->classification_type,
                'origin_type' => 'classification_vote',
                'origin_user_id' => $vote->user_id ? (int) $vote->user_id : null,
                'derivation_selection_id' => null,
                'classification_vote_id' => (int) $vote->id,
                'is_winner' => ($payload[$criterion] ?? null) === $code,
            ];
        }

        return $supports;
    }

    private function buildPayloadFromScores(array $autoScores, array $voteScores, ?string $sourceType): array
    {
        $payload = [
            'source_type' => $sourceType,
        ];

        foreach (self::CRITERIA as $criterion) {
            $criterionAutoScores = $autoScores[$criterion] ?? [];
            $criterionVoteScores = $voteScores[$criterion] ?? [];
            $totals = $this->mergeScores($criterionAutoScores, $criterionVoteScores);

            if (empty($totals)) {
                $payload[$criterion] = null;
                $payload[strtolower($criterion).'_auto_points'] = null;
                $payload[strtolower($criterion).'_vote_points'] = null;
                $payload[strtolower($criterion).'_total_points'] = null;
                $payload[strtolower($criterion).'_all_points'] = null;

                continue;
            }

            $winnerCode = $this->pickWinningCode($totals);
            $autoPoints = $criterionAutoScores[$winnerCode] ?? 0;
            $votePoints = $criterionVoteScores[$winnerCode] ?? 0;
            $totalPoints = $totals[$winnerCode] ?? 0;
            $allPoints = array_sum($totals);

            $payload[$criterion] = $winnerCode;
            $payload[strtolower($criterion).'_auto_points'] = $autoPoints ?: null;
            $payload[strtolower($criterion).'_vote_points'] = $votePoints ?: null;
            $payload[strtolower($criterion).'_total_points'] = $totalPoints ?: null;
            $payload[strtolower($criterion).'_all_points'] = $allPoints ?: null;
        }

        return $payload;
    }

    private function resolveSelectionCodes(Collection $selections, ?Collection $voteMetadata = null): array
    {
        if ($selections->isEmpty()) {
            return [];
        }

        $metadataBySelection = $voteMetadata ?? $this->getLatestMetadataMapForSelections($selections);

        $scores = [];

        foreach (self::CRITERIA as $criterion) {
            $scores[$criterion] = [];
        }

        foreach ($selections as $selection) {
            $criterion = strtoupper(substr((string) $selection->bucket, 0, 1));
            $metadata = $selection->kind === 'vote' ? $metadataBySelection->get($selection->id) : null;
            $code = $this->normalizeCode(
                $metadata?->norman_classification
                    ?? $selection->hazardsSubstanceData?->assessment_class
            );
            $points = $metadata?->norman_vote ?? $this->defaultSelectionVote($selection);

            if (! in_array($criterion, self::CRITERIA, true) || $code === null) {
                continue;
            }

            $scores[$criterion][$code] = ($scores[$criterion][$code] ?? 0) + (int) $points;
        }

        return $scores;
    }

    private function collectVoteScores(Collection $classificationVotes): array
    {
        $scores = [];

        foreach (self::CRITERIA as $criterion) {
            $scores[$criterion] = [];
        }

        foreach ($classificationVotes as $vote) {
            $criterion = strtoupper((string) ($vote->criterion ?? ''));
            $code = $this->normalizeCode($vote->classification_code ?? null);

            if (! in_array($criterion, self::CRITERIA, true) || $code === null) {
                continue;
            }

            $points = (int) ($vote->vote_value ?? 0);
            if ($points <= 0) {
                continue;
            }

            $scores[$criterion][$code] = ($scores[$criterion][$code] ?? 0) + $points;
        }

        return $scores;
    }

    private function mergeScores(array $autoScores, array $voteScores): array
    {
        $merged = $autoScores;

        foreach ($voteScores as $code => $points) {
            $merged[$code] = ($merged[$code] ?? 0) + $points;
        }

        return $merged;
    }

    private function pickWinningCode(array $totals): string
    {
        $winnerCode = null;
        $winnerPoints = -1;

        foreach ($totals as $code => $points) {
            if (
                $winnerCode === null
                || $points > $winnerPoints
                || ($points === $winnerPoints && strcmp($code, $winnerCode) < 0)
            ) {
                $winnerCode = $code;
                $winnerPoints = $points;
            }
        }

        return (string) $winnerCode;
    }

    private function normalizeCode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function deriveSourceType(Collection $autoSelections): ?string
    {
        if ($autoSelections->isEmpty()) {
            return null;
        }

        $sources = $autoSelections
            ->map(fn (DerivationSelection $selection) => $this->resolveSelectionSupportSourceType($selection))
            ->filter()
            ->unique()
            ->values();

        return $sources->count() === 1 ? (string) $sources->first() : null;
    }

    private function deriveVoteSourceType(Collection $classificationVotes): ?string
    {
        if ($classificationVotes->isEmpty()) {
            return null;
        }

        $types = $classificationVotes
            ->pluck('classification_type')
            ->filter()
            ->unique()
            ->values();

        return $types->count() === 1 ? (string) $types->first() : null;
    }

    private function syncSupports(SubstanceClassification $row, array $supports): void
    {
        ClassificationSupport::query()
            ->where('substance_classification_id', $row->id)
            ->delete();

        if (empty($supports)) {
            return;
        }

        ClassificationSupport::insert(array_map(function (array $support) use ($row) {
            return $support + [
                'substance_classification_id' => $row->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $supports));
    }

    private function snapshotMatches(SubstanceClassification $row, array $payload): bool
    {
        $fields = [
            'P',
            'p_auto_points',
            'p_vote_points',
            'p_total_points',
            'p_all_points',
            'B',
            'b_auto_points',
            'b_vote_points',
            'b_total_points',
            'b_all_points',
            'M',
            'm_auto_points',
            'm_vote_points',
            'm_total_points',
            'm_all_points',
            'T',
            't_auto_points',
            't_vote_points',
            't_total_points',
            't_all_points',
            'source_type',
        ];

        foreach ($fields as $field) {
            if ($row->{$field} !== ($payload[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function supportsMatch(SubstanceClassification $row, array $supports): bool
    {
        $currentSupports = $row->relationLoaded('supports')
            ? $row->supports
            : $row->supports()->get();

        $normalize = static function (iterable $items): array {
            $normalized = [];

            foreach ($items as $item) {
                $normalized[] = [
                    'criterion' => (string) ($item['criterion'] ?? $item->criterion ?? ''),
                    'classification_code' => (string) ($item['classification_code'] ?? $item->classification_code ?? ''),
                    'points' => (int) ($item['points'] ?? $item->points ?? 0),
                    'source_type' => (string) ($item['source_type'] ?? $item->source_type ?? ''),
                    'origin_type' => (string) ($item['origin_type'] ?? $item->origin_type ?? ''),
                    'origin_user_id' => ($item['origin_user_id'] ?? $item->origin_user_id ?? null) !== null
                        ? (int) ($item['origin_user_id'] ?? $item->origin_user_id)
                        : null,
                    'derivation_selection_id' => ($item['derivation_selection_id'] ?? $item->derivation_selection_id ?? null) !== null
                        ? (int) ($item['derivation_selection_id'] ?? $item->derivation_selection_id)
                        : null,
                    'classification_vote_id' => ($item['classification_vote_id'] ?? $item->classification_vote_id ?? null) !== null
                        ? (int) ($item['classification_vote_id'] ?? $item->classification_vote_id)
                        : null,
                    'is_winner' => (bool) ($item['is_winner'] ?? $item->is_winner ?? false),
                ];
            }

            usort($normalized, static function (array $a, array $b): int {
                return strcmp(json_encode($a), json_encode($b));
            });

            return $normalized;
        };

        return $normalize($currentSupports) === $normalize($supports);
    }

    private function resolveSelectionSupportSourceType(DerivationSelection $selection): string
    {
        if ($selection->kind === 'vote') {
            return $this->resolveSelectionUserDisplayName($selection);
        }

        if (! empty($selection->source_label) && strtolower((string) $selection->source_label) !== 'vote') {
            return (string) $selection->source_label;
        }

        $row = $selection->hazardsSubstanceData;
        $source = strtolower((string) ($row?->data_source ?? ''));

        if (str_contains($source, 'janus')) {
            return 'JANUS';
        }

        if ((string) ($row?->test_type ?? '') === '2') {
            return 'NORMANexp';
        }

        return 'PikMe';
    }

    private function resolveSelectionUserDisplayName(DerivationSelection $selection): string
    {
        if ($selection->kind === 'auto') {
            return 'NDS EXPERT';
        }

        $user = $selection->user;
        if (! $user) {
            return 'Expert derivation';
        }

        $formattedName = trim((string) ($user->formatted_name ?? ''));
        if ($formattedName !== '') {
            return $formattedName;
        }

        $fullName = trim((string) ($user->full_name ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $username = trim((string) ($user->username ?? ''));
        if ($username !== '') {
            return $username;
        }

        return (string) ($user->email ?? 'Expert derivation');
    }

    private function defaultSelectionVote(DerivationSelection $selection): int
    {
        if ($selection->kind === 'vote') {
            return 3;
        }

        $testType = (string) ($selection->hazardsSubstanceData?->test_type ?? '');

        return $testType === '2' ? 2 : 1;
    }

    /**
     * Latest vote snapshot per selection. Automatic selections have no snapshot; their
     * values come from the live source row.
     *
     * @param  Collection<int, DerivationSelection>  $selections
     * @return Collection<int, DerivationMetadata> keyed by selection id
     */
    public function getLatestMetadataMapForSelections(Collection $selections): Collection
    {
        $voteIds = $selections->where('kind', 'vote')->pluck('id')->all();
        if ($voteIds === []) {
            return collect();
        }

        return DerivationMetadata::query()
            ->whereIn('selection_id', $voteIds)
            ->orderByDesc('id')
            ->get()
            ->unique('selection_id')
            ->keyBy('selection_id');
    }
}
