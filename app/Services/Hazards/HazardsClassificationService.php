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
    private const AUTO_USER_ID = 3;

    private const CRITERIA = ['P', 'B', 'M', 'T'];

    public function __construct(
        private readonly HazardsDerivationService $derivationService
    ) {
    }

    public function run(int|string $susdatSubstanceId): void
    {
        $substanceId = (int) $susdatSubstanceId;
        if ($substanceId <= 0) {
            return;
        }

        $this->derivationService->runAutoSelections($substanceId);
        $this->syncAutoBaseline($substanceId);
        $this->syncDerivationSnapshot($substanceId);
    }

    public function syncDerivationSnapshot(int|string $susdatSubstanceId, ?int $actorUserId = null): ?SubstanceClassification
    {
        $substanceId = (int) $susdatSubstanceId;
        $actorUserId = $actorUserId !== null ? (int) $actorUserId : null;

        if ($substanceId <= 0) {
            return null;
        }

        $autos = DerivationSelection::query()
            ->where('susdat_substance_id', $substanceId)
            ->where('kind', 'auto')
            ->where('is_current', true)
            ->get();

        $votes = DerivationSelection::query()
            ->where('susdat_substance_id', $substanceId)
            ->where('kind', 'vote')
            ->where('is_current', true)
            ->whereNotNull('user_id')
            ->get();

        $current = SubstanceClassification::query()
            ->with('supports')
            ->where('susdat_substance_id', $substanceId)
            ->where('kind', 'derivation')
            ->where('is_current', true)
            ->latest('id')
            ->first();

        if ($votes->isEmpty()) {
            SubstanceClassification::query()
                ->where('susdat_substance_id', $substanceId)
                ->where('kind', 'derivation')
                ->where('is_current', true)
                ->update(['is_current' => false]);

            return null;
        }

        $payload = $this->buildDerivationConclusionPayload($autos, $votes);
        $supports = $this->buildDerivationSupportRows($autos, $votes, $payload);

        if (
            $current
            && $this->snapshotMatches($current, $payload)
            && $this->supportsMatch($current, $supports)
        ) {
            $current->touch();

            return $current;
        }

        SubstanceClassification::query()
            ->where('susdat_substance_id', $substanceId)
            ->where('kind', 'derivation')
            ->where('is_current', true)
            ->update(['is_current' => false]);

        $editorUserId = $actorUserId
            ?: ((int) ($votes->sortByDesc('updated_at')->first()?->user_id ?? 0) ?: null);

        $row = SubstanceClassification::create($payload + [
            'susdat_substance_id' => $substanceId,
            'editor_user_id' => $editorUserId,
            'kind' => 'derivation',
            'is_current' => true,
        ]);

        $this->syncSupports($row, $supports);

        return $row;
    }

    public function syncAutoBaseline(int|string $susdatSubstanceId): ?SubstanceClassification
    {
        $substanceId = (int) $susdatSubstanceId;
        if ($substanceId <= 0) {
            return null;
        }

        $autos = DerivationSelection::query()
            ->where('susdat_substance_id', $substanceId)
            ->where('kind', 'auto')
            ->where('is_current', true)
            ->get();

        if ($autos->isEmpty()) {
            return null;
        }

        $payload = $this->buildDerivationConclusionPayload($autos, collect());
        $supports = $this->buildDerivationSupportRows($autos, collect(), $payload);

        return DB::transaction(function () use ($substanceId, $payload, $supports) {
            $row = SubstanceClassification::firstOrNew([
                'susdat_substance_id' => $substanceId,
                'editor_user_id' => self::AUTO_USER_ID,
                'kind' => 'auto_baseline',
            ]);

            $row->fill($payload + [
                'is_current' => true,
            ]);
            $row->save();

            if (! $row->wasRecentlyCreated && ! $row->wasChanged()) {
                $row->touch();
            }

            SubstanceClassification::query()
                ->where('susdat_substance_id', $substanceId)
                ->where('editor_user_id', self::AUTO_USER_ID)
                ->where('kind', 'auto_baseline')
                ->where('id', '!=', $row->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $this->syncSupports($row, $supports);

            return $row->fresh('supports');
        });
    }

    public function insertUserConclusionSnapshot(int|string $susdatSubstanceId, int $userId): ?SubstanceClassification
    {
        $substanceId = (int) $susdatSubstanceId;
        $userId = (int) $userId;

        if ($substanceId <= 0 || $userId <= 0) {
            return null;
        }

        $autos = DerivationSelection::query()
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

                $touchedPairs[] = $classificationType . '|' . $criterion;

                $current = ClassificationVote::query()
                    ->where('susdat_substance_id', $substanceId)
                    ->where('user_id', $userId)
                    ->where('classification_type', $classificationType)
                    ->where('criterion', $criterion)
                    ->where('is_current', true)
                    ->latest('id')
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

    private function buildDerivationConclusionPayload(Collection $autoSelections, Collection $derivationVotes): array
    {
        $autoScores = $this->resolveSelectionCodes($autoSelections);
        $voteScores = $this->resolveSelectionCodes($derivationVotes);

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

    private function buildDerivationSupportRows(Collection $autoSelections, Collection $derivationVotes, array $payload): array
    {
        return array_values(array_merge(
            $this->buildSelectionSupportRows($autoSelections, 'auto', $payload),
            $this->buildSelectionSupportRows($derivationVotes, 'derivation_vote', $payload)
        ));
    }

    private function buildClassificationSupportRows(Collection $autoSelections, Collection $classificationVotes, array $payload): array
    {
        return array_values(array_merge(
            $this->buildSelectionSupportRows($autoSelections, 'auto', $payload),
            $this->buildClassificationVoteSupportRows($classificationVotes, $payload)
        ));
    }

    private function buildSelectionSupportRows(Collection $selections, string $originType, array $payload): array
    {
        if ($selections->isEmpty()) {
            return [];
        }

        $metadataBySelection = $this->getLatestMetadataMapForSelections($selections->pluck('id'));
        $supports = [];

        foreach ($selections as $selection) {
            $criterion = strtoupper(substr((string) $selection->bucket, 0, 1));
            if (! in_array($criterion, self::CRITERIA, true)) {
                continue;
            }

            $metadata = $metadataBySelection->get($selection->id);
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
                $payload[strtolower($criterion) . '_auto_points'] = null;
                $payload[strtolower($criterion) . '_vote_points'] = null;
                $payload[strtolower($criterion) . '_total_points'] = null;
                $payload[strtolower($criterion) . '_all_points'] = null;
                continue;
            }

            $winnerCode = $this->pickWinningCode($totals);
            $autoPoints = $criterionAutoScores[$winnerCode] ?? 0;
            $votePoints = $criterionVoteScores[$winnerCode] ?? 0;
            $totalPoints = $totals[$winnerCode] ?? 0;
            $allPoints = array_sum($totals);

            $payload[$criterion] = $winnerCode;
            $payload[strtolower($criterion) . '_auto_points'] = $autoPoints ?: null;
            $payload[strtolower($criterion) . '_vote_points'] = $votePoints ?: null;
            $payload[strtolower($criterion) . '_total_points'] = $totalPoints ?: null;
            $payload[strtolower($criterion) . '_all_points'] = $allPoints ?: null;
        }

        return $payload;
    }

    private function resolveSelectionCodes(Collection $selections): array
    {
        if ($selections->isEmpty()) {
            return [];
        }

        $metadataBySelection = $this->getLatestMetadataMapForSelections($selections->pluck('id'));

        $scores = [];

        foreach (self::CRITERIA as $criterion) {
            $scores[$criterion] = [];
        }

        foreach ($selections as $selection) {
            $criterion = strtoupper(substr((string) $selection->bucket, 0, 1));
            $metadata = $metadataBySelection->get($selection->id);
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

    private function syncSnapshot(int $substanceId, int $userId, string $kind, array $payload, array $supports): SubstanceClassification
    {
        $current = SubstanceClassification::query()
            ->where('susdat_substance_id', $substanceId)
            ->where('editor_user_id', $userId)
            ->where('kind', $kind)
            ->where('is_current', true)
            ->latest('id')
            ->first();

        if ($current && $this->snapshotMatches($current, $payload)) {
            $this->syncSupports($current, $supports);
            $current->touch();

            return $current;
        }

        if ($current) {
            $current->update(['is_current' => false]);
        }

        $row = SubstanceClassification::create($payload + [
            'susdat_substance_id' => $substanceId,
            'editor_user_id' => $userId,
            'kind' => $kind,
            'is_current' => true,
        ]);

        $this->syncSupports($row, $supports);

        return $row;
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
        $user = $selection->user;
        if ((int) $selection->user_id === self::AUTO_USER_ID && $user) {
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

            return (string) ($user->email ?? 'NDS EXPERT');
        }

        if ((int) $selection->user_id === self::AUTO_USER_ID) {
            return 'NDS EXPERT';
        }

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

    public function getLatestMetadataMapForSelections(Collection $selectionIds): Collection
    {
        if ($selectionIds->isEmpty()) {
            return collect();
        }

        return DerivationMetadata::query()
            ->whereIn('selection_id', $selectionIds->all())
            ->orderByDesc('id')
            ->get()
            ->unique('selection_id')
            ->keyBy('selection_id');
    }
}

