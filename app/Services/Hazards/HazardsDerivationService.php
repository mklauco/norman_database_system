<?php

namespace App\Services\Hazards;

use App\Models\Hazards\ComptoxSubstanceData;
use App\Models\Hazards\DerivationMetadata;
use App\Models\Hazards\DerivationSelection;
use App\Models\Susdat\Substance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HazardsDerivationService
{
    private const AUTO_USER_ID = 3;

    private const BUCKETS = [
        'P_pred',
        'P_exp',
        'B_pred',
        'B_exp',
        'M_pred',
        'M_exp',
        'T_pred',
        'T_exp',
    ];

    private const JANUS_PARAMETER_NAMES = [
        'Persistency',
        'Toxicity',
        'Bioconcentration factor (BCF)',
    ];

    public function runAutoSelections(int|string $susdatSubstanceId): void
    {
        $substanceId = (int) $susdatSubstanceId;
        if ($substanceId <= 0) {
            return;
        }

        $rows = $this->loadCandidateRows($substanceId);
        if ($rows->isEmpty()) {
            return;
        }

        $buckets = $this->buildBuckets($rows);

        foreach (self::BUCKETS as $bucket) {
            $items = $buckets[$bucket] ?? collect();
            $pick = $this->pickAutoCandidate($items, $bucket);
            $this->syncAutoSelection($substanceId, $bucket, $pick);
        }
    }

    public function getDerivationPageData(int|string $susdatSubstanceId, ?int $currentUserId = null): array
    {
        $substanceId = (int) $susdatSubstanceId;
        $currentUserId = $currentUserId !== null ? (int) $currentUserId : null;
        $this->runAutoSelections($substanceId);

        $rows = $this->loadCandidateRows($substanceId);
        $buckets = $this->buildBuckets($rows);

        $currentAutos = DerivationSelection::query()
            ->with(['hazardsSubstanceData', 'user'])
            ->where('susdat_substance_id', $substanceId)
            ->where('kind', 'auto')
            ->where('is_current', true)
            ->get()
            ->keyBy('bucket');

        $voteHistory = DerivationSelection::query()
            ->with(['hazardsSubstanceData', 'user'])
            ->where('susdat_substance_id', $substanceId)
            ->where('kind', 'vote')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('bucket');

        $votedMap = [];
        foreach (self::BUCKETS as $bucket) {
            $currentUserVotes = DerivationSelection::query()
                ->where('susdat_substance_id', $substanceId)
                ->where('bucket', $bucket)
                ->where('kind', 'vote')
                ->where('is_current', true)
                ->when(
                    $currentUserId !== null && $currentUserId > 0,
                    fn ($query) => $query->where('user_id', $currentUserId),
                    fn ($query) => $query->whereRaw('1 = 0')
                )
                ->get();

            $votedMap[$bucket] = $currentUserVotes
                ->pluck('id', 'hazards_substance_data_id')
                ->toArray();
        }

        $bucketCandidates = [];
        foreach (self::BUCKETS as $bucket) {
            $autoSelection = $currentAutos->get($bucket);
            $bucketCandidates[$bucket] = ($buckets[$bucket] ?? collect())
                ->map(function (ComptoxSubstanceData $row) use ($autoSelection) {
                    return $this->normalizeCandidateRow(
                        $row,
                        $autoSelection && (int) $autoSelection->hazards_substance_data_id === (int) $row->id
                    );
                })
                ->values()
                ->all();
        }

        $currentAutoRows = [];
        foreach (self::BUCKETS as $bucket) {
            $selection = $currentAutos->get($bucket);
            $currentAutoRows[$bucket] = $selection ? $this->normalizeSelectionRow($selection) : null;
        }

        $currentVoteRows = [];
        foreach (self::BUCKETS as $bucket) {
            $currentVoteRows[$bucket] = ($voteHistory->get($bucket) ?? collect())
                ->map(fn (DerivationSelection $selection) => $this->normalizeSelectionRow($selection))
                ->values()
                ->all();
        }

        $substance = Substance::find($substanceId);
        $firstRow = $rows->first();

        return [
            'substance' => (object) [
                'susdat_substance_id' => $substanceId,
                'substance_name' => $substance?->display_name ?? $substance?->name ?? $firstRow?->substance_name,
                'cas_no' => $substance?->formatted_cas ?? $firstRow?->cas_no,
                'inchikey' => $substance?->stdinchikey ?? $firstRow?->inchikey,
                'dtxid' => $substance?->dtxid ?? $firstRow?->dtxid,
            ],
            'candidates' => $bucketCandidates,
            'current_auto' => $currentAutoRows,
            'current_votes' => $currentVoteRows,
            'voted_map' => $votedMap,
        ];
    }

    public function storeVote(
        int|string $susdatSubstanceId,
        string $bucket,
        int $hazardsSubstanceDataId,
        int $userId,
        array $metadataOverrides = [],
        ?string $userName = null
    ): DerivationSelection {
        $substanceId = (int) $susdatSubstanceId;

        return DB::transaction(function () use ($substanceId, $bucket, $hazardsSubstanceDataId, $userId, $metadataOverrides, $userName) {
            DerivationSelection::query()
                ->where('susdat_substance_id', $substanceId)
                ->where('bucket', $bucket)
                ->where('kind', 'vote')
                ->where('user_id', $userId)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $selection = DerivationSelection::create([
                'susdat_substance_id' => $substanceId,
                'bucket' => $bucket,
                'hazards_substance_data_id' => $hazardsSubstanceDataId,
                'source_label' => 'vote',
                'kind' => 'vote',
                'user_id' => $userId,
                'is_current' => true,
            ]);

            $this->createMetadataSnapshot($selection, $metadataOverrides, $userName);

            return $selection->fresh(['hazardsSubstanceData', 'user']);
        });
    }

    public function removeVote(int $selectionId, ?int $userId = null): void
    {
        $selection = DerivationSelection::query()
            ->where('id', $selectionId)
            ->where('kind', 'vote')
            ->when(
                $userId !== null && $userId > 0,
                fn ($query) => $query->where('user_id', (int) $userId)
            )
            ->first();

        if (! $selection) {
            return;
        }

        $selection->update(['is_current' => false]);
    }

    public function createMetadataSnapshot(
        DerivationSelection $selection,
        array $metadataOverrides = [],
        ?string $userName = null
    ): ?DerivationMetadata {
        $row = $selection->hazardsSubstanceData()->first();
        if (! $row) {
            return null;
        }

        $editorName = $userName ?: $this->resolveUserDisplayName($selection);
        $hazardCalculated = strtoupper(substr($selection->bucket, 0, 1));
        $normanVoteInput = $metadataOverrides['meta_norman_vote'] ?? null;
        $normanVote = ($normanVoteInput !== null && $normanVoteInput !== '')
            ? (int) $normanVoteInput
            : ($selection->kind === 'vote' ? 3 : $this->defaultNormanVoteForRow($row));

        $testTypeLabel = $this->resolveTestTypeLabel($row->test_type);
        if (! empty($metadataOverrides['meta_test_type']) && $metadataOverrides['meta_test_type'] !== 'other') {
            $testTypeLabel = (string) $metadataOverrides['meta_test_type'];
        }

        return DerivationMetadata::create([
            'selection_id' => $selection->id,
            'susdat_substance_id' => $selection->susdat_substance_id,
            'bucket' => $selection->bucket,
            'hazards_substance_data_id' => $row->id,
            'user_id' => $selection->user_id,
            'data_source' => $metadataOverrides['meta_pbmt_classification_code'] ?? $row->data_source,
            'editor' => $metadataOverrides['meta_editor'] ?? $editorName,
            'record_date' => $this->toNullableDateTime($metadataOverrides['meta_date'] ?? null) ?? now(),
            'reference_type' => $metadataOverrides['meta_reference_type'] ?? $row->reference_type,
            'title' => $metadataOverrides['meta_title'] ?? $row->title,
            'authors' => $metadataOverrides['meta_authors'] ?? $row->authors,
            'year' => $this->toNullableInteger($metadataOverrides['meta_year'] ?? $row->year),
            'bibliographic_source' => $metadataOverrides['meta_bibliographic_source'] ?? $row->bibliographic_source,
            'hazards_file_doi' => $metadataOverrides['meta_doi'] ?? $row->physico_chemical_source_doi,
            'test_type' => $testTypeLabel,
            'performed_under_glp' => $this->stringOrNull($metadataOverrides['meta_glp'] ?? $row->performed_under_glp),
            'standard_test' => $this->stringOrNull($metadataOverrides['meta_standard_test'] ?? $row->standard_test),
            'substance_name' => $metadataOverrides['meta_substance_name'] ?? $row->substance_name,
            'cas_number' => $metadataOverrides['meta_cas'] ?? $row->cas_no,
            'radio_labeled_substance' => $this->stringOrNull($metadataOverrides['meta_radio_labeled'] ?? $row->radio_labeled_substance),
            'standard_qualifier' => $metadataOverrides['meta_standard_qualifier'] ?? $row->standard_qualifier,
            'standard_used' => $metadataOverrides['meta_standard_used'] ?? $metadataOverrides['meta_assessment_method'] ?? $row->standard_used,
            'test_matrix' => $metadataOverrides['meta_test_matrix'] ?? $row->test_matrix,
            'test_species' => $metadataOverrides['meta_test_species'] ?? $row->test_species,
            'duration_days' => $this->toNullableFloat($metadataOverrides['meta_duration_days'] ?? $row->duration_days),
            'exposure_concentration' => $this->toNullableFloat($metadataOverrides['meta_exposure_concentration'] ?? $row->exposure_concentration),
            'ph' => $this->toNullableFloat($metadataOverrides['meta_ph'] ?? $row->ph),
            'temperature_c' => $this->toNullableFloat($metadataOverrides['meta_temperature_c'] ?? $row->temperature_c),
            'total_organic_carbon' => $this->toNullableFloat($metadataOverrides['meta_total_organic_carbon'] ?? $row->total_organic_carbon),
            'original_parameter_name' => $metadataOverrides['meta_original_parameter_name'] ?? $row->original_parameter_name,
            'original_qualifier' => $metadataOverrides['meta_original_qualifier'] ?? $row->original_qualifier,
            'original_value' => $this->toNullableFloat(
                array_key_exists('meta_original_value', $metadataOverrides)
                    ? $metadataOverrides['meta_original_value']
                    : $row->original_value
            ),
            'original_value_range' => $metadataOverrides['meta_original_value_range'] ?? $row->original_value_range,
            'original_unit' => $metadataOverrides['meta_original_unit'] ?? $metadataOverrides['meta_unit'] ?? $row->original_unit,
            'assessment_parameter_name' => $metadataOverrides['meta_assessment_parameter_name'] ?? $row->norman_parameter_name,
            'assessment_qualifier' => $metadataOverrides['meta_assessment_qualifier'] ?? $row->assessment_qualifier,
            'assessment_value' => $this->toNullableFloat(
                array_key_exists('meta_assessment_value', $metadataOverrides)
                    ? $metadataOverrides['meta_assessment_value']
                    : $row->value_assessment_index
            ),
            'assessment_unit' => $metadataOverrides['meta_assessment_unit'] ?? $metadataOverrides['meta_unit'] ?? $row->unit,
            'hazard_criterion' => $metadataOverrides['meta_hazard_criterion'] ?? $hazardCalculated,
            'original_classification' => $metadataOverrides['meta_original_classification'] ?? null,
            'classification_score' => $this->toNullableFloat($metadataOverrides['meta_classification_score'] ?? null),
            'general_comment' => $metadataOverrides['meta_general_comment'] ?? $row->general_comment,
            'applicability_domain' => $metadataOverrides['meta_applicability_domain'] ?? $row->applicability_domain,
            'applicability_domain_score' => $this->toNullableFloat($metadataOverrides['meta_applicability_domain_score'] ?? $row->applicability_domain_score),
            'reliability_score' => $this->toNullableFloat($metadataOverrides['meta_reliability_score'] ?? $row->reliability_score),
            'reliability_score_system' => $metadataOverrides['meta_reliability_system'] ?? $row->reliability_score_system,
            'reliability_rational' => $metadataOverrides['meta_reliability_rational'] ?? $row->reliability_rational,
            'institution_of_reliability_score' => $metadataOverrides['meta_reliability_institution'] ?? $row->institution_of_reliability_score,
            'regulatory_context' => $metadataOverrides['meta_regulatory_context'] ?? $row->regulatory_purpose,
            'institution_original_classification' => $metadataOverrides['meta_institution_original_classification'] ?? null,
            'norman_classification' => $metadataOverrides['meta_norman_classification'] ?? $row->assessment_class,
            'norman_vote' => $normanVote,
            'automated_expert_vote' => $metadataOverrides['meta_automated_expert_vote'] ?? $editorName,
        ]);
    }

    public function getMetadataForSelection(int $selectionId): ?DerivationMetadata
    {
        $selection = DerivationSelection::find($selectionId);
        if (! $selection) {
            return null;
        }

        $metadata = DerivationMetadata::query()
            ->where('selection_id', $selectionId)
            ->latest('id')
            ->first();

        if ($metadata) {
            return $metadata;
        }

        return DerivationMetadata::query()
            ->where('susdat_substance_id', $selection->susdat_substance_id)
            ->where('bucket', $selection->bucket)
            ->where('hazards_substance_data_id', $selection->hazards_substance_data_id)
            ->latest('id')
            ->first();
    }

    private function syncAutoSelection(int $substanceId, string $bucket, ?ComptoxSubstanceData $pick): void
    {
        $current = DerivationSelection::query()
            ->with(['hazardsSubstanceData', 'user'])
            ->where('susdat_substance_id', $substanceId)
            ->where('bucket', $bucket)
            ->where('kind', 'auto')
            ->where('is_current', true)
            ->latest('id')
            ->first();

        if (! $pick) {
            if ($current) {
                $current->update(['is_current' => false]);
            }

            return;
        }

        if ($current && (int) $current->hazards_substance_data_id === (int) $pick->id) {
            $this->ensureMetadataSnapshot($current);

            return;
        }

        if ($current) {
            $current->update(['is_current' => false]);
        }

        $selection = DerivationSelection::create([
            'susdat_substance_id' => $substanceId,
            'bucket' => $bucket,
            'hazards_substance_data_id' => (int) $pick->id,
            'source_label' => $this->autoSourceLabel($pick),
            'kind' => 'auto',
            'user_id' => self::AUTO_USER_ID,
            'is_current' => true,
        ]);

        $this->createMetadataSnapshot($selection);
    }

    private function loadCandidateRows(int $susdatSubstanceId): Collection
    {
        return ComptoxSubstanceData::query()
            ->where('susdat_substance_id', $susdatSubstanceId)
            ->orderByDesc('id')
            ->get()
            ->reject(fn (ComptoxSubstanceData $row) => $this->shouldSkipDerivationCandidate($row))
            ->values();
    }

    private function buildBuckets(Collection $rows): array
    {
        $buckets = [];
        foreach (self::BUCKETS as $bucket) {
            $buckets[$bucket] = collect();
        }

        foreach ($rows as $row) {
            $bucket = $this->resolveBucket($row);
            if ($bucket === null) {
                continue;
            }

            $buckets[$bucket]->push($row);
        }

        return $buckets;
    }

    private function shouldSkipDerivationCandidate(ComptoxSubstanceData $row): bool
    {
        return $row->data_source === 'CompTox Dashboard' && (string) $row->test_type === '3';
    }

    private function resolveBucket(ComptoxSubstanceData $row): ?string
    {
        $parameter = (string) ($row->norman_parameter_name ?? '');
        $testType = (string) ($row->test_type ?? '');

        if ($parameter === 'Persistency') {
            return 'P_pred';
        }

        if ($parameter === 'Biodeg. Half-Life') {
            return $testType === '2' ? 'P_exp' : 'P_pred';
        }

        if ($parameter === 'Bioconcentration factor (BCF)') {
            return 'B_pred';
        }

        if ($parameter === 'Bioconcentration Factor') {
            return $testType === '2' ? 'B_exp' : 'B_pred';
        }

        if ($parameter === 'Soil Adsorp. Coeff. (Koc)') {
            return $testType === '2' ? 'M_exp' : 'M_pred';
        }

        if ($parameter === 'Toxicity') {
            return $testType === '2' ? 'T_exp' : 'T_pred';
        }

        return null;
    }

    private function autoSourceLabel(ComptoxSubstanceData $row): string
    {
        if (in_array((string) $row->norman_parameter_name, self::JANUS_PARAMETER_NAMES, true)) {
            return 'JANUS';
        }

        if ((string) $row->test_type === '2') {
            return 'NORMANexp';
        }

        return 'PikMe';
    }

    private function pickAutoCandidate(Collection $items, string $bucket): ?ComptoxSubstanceData
    {
        if ($items->isEmpty()) {
            return null;
        }

        [$criterion, $mode] = explode('_', $bucket, 2);

        if ($mode === 'pred') {
            return $this->pickPredictedCandidate($items, $criterion);
        }

        return $this->pickExperimentalCandidate($items, $criterion);
    }

    private function pickPredictedCandidate(Collection $items, string $criterion): ?ComptoxSubstanceData
    {
        $bestRow = null;
        $bestRank = -1;
        $bestReliability = -INF;
        $bestId = -INF;

        foreach ($items as $row) {
            $rank = $this->rankAssessmentClass($row->assessment_class, $criterion);
            $reliability = is_numeric($row->reliability_score) ? (float) $row->reliability_score : -INF;
            $id = (float) ($row->id ?? -INF);

            if (
                $rank > $bestRank
                || ($rank === $bestRank && $reliability > $bestReliability)
                || ($rank === $bestRank && $reliability === $bestReliability && $id > $bestId)
            ) {
                $bestRow = $row;
                $bestRank = $rank;
                $bestReliability = $reliability;
                $bestId = $id;
            }
        }

        return $bestRow;
    }

    private function pickExperimentalCandidate(Collection $items, string $criterion): ?ComptoxSubstanceData
    {
        if ($criterion === 'B') {
            return $items
                ->filter(fn (ComptoxSubstanceData $row) => is_numeric($row->value_assessment_index))
                ->sort(function (ComptoxSubstanceData $a, ComptoxSubstanceData $b) {
                    $valueCompare = (float) $b->value_assessment_index <=> (float) $a->value_assessment_index;
                    if ($valueCompare !== 0) {
                        return $valueCompare;
                    }

                    return (int) $b->id <=> (int) $a->id;
                })
                ->first();
        }

        if ($criterion === 'M') {
            return $items
                ->filter(fn (ComptoxSubstanceData $row) => is_numeric($row->value_assessment_index))
                ->sort(function (ComptoxSubstanceData $a, ComptoxSubstanceData $b) {
                    $valueCompare = (float) $a->value_assessment_index <=> (float) $b->value_assessment_index;
                    if ($valueCompare !== 0) {
                        return $valueCompare;
                    }

                    return (int) $b->id <=> (int) $a->id;
                })
                ->first();
        }

        return null;
    }

    private function rankAssessmentClass(?string $assessmentClass, string $criterion): int
    {
        if (! $assessmentClass) {
            return 0;
        }

        $normalized = strtolower(trim($assessmentClass));

        $rankings = [
            'P' => ['vp' => 5, 'p' => 4, 'sp' => 3, 'np' => 2, 'probably-np' => 1],
            'B' => ['vb' => 5, 'b' => 4, 'sb' => 3, 'nb' => 2, 'probably-nb' => 1],
            'M' => ['vm' => 5, 'm' => 4, 'sm' => 3, 'nm' => 2, 'probably-nm' => 1],
            'T' => ['t+' => 5, 't' => 4, 'st' => 3, 'nt' => 2, 'probably-nt' => 1],
        ];

        foreach (($rankings[$criterion] ?? []) as $value => $rank) {
            if ($normalized === $value) {
                return $rank;
            }
        }

        return 0;
    }

    private function normalizeCandidateRow(ComptoxSubstanceData $row, bool $autoSelected): array
    {
        return [
            'hazards_substance_data_id' => (int) $row->id,
            'data_source' => $row->data_source ?? 'N/A',
            'test_type' => $this->resolveTestTypeLabel($row->test_type),
            'norman_parameter_name' => $row->norman_parameter_name ?? 'N/A',
            'original_value' => $this->formatNumber($row->original_value),
            'original_unit' => $row->original_unit ?? 'N/A',
            'value_assessment_index' => $this->formatNumber($row->value_assessment_index),
            'unit' => $row->unit ?? 'N/A',
            'assessment_class' => $row->assessment_class ?? 'N/A',
            'applicability_domain_score' => $this->formatNumber($row->applicability_domain_score),
            'reliability_score' => $this->formatNumber($row->reliability_score),
            'auto_selected' => $autoSelected,
        ];
    }

    private function normalizeSelectionRow(DerivationSelection $selection): array
    {
        $row = $selection->hazardsSubstanceData;
        $metadata = $this->getMetadataForSelection((int) $selection->id);
        $classificationType = $selection->kind === 'vote'
            ? $this->resolveSelectionSourceType($row)
            : ($selection->source_label ?: $this->resolveSelectionSourceType($row));

        return [
            'selection_id' => (int) $selection->id,
            'user_id' => $selection->user_id ? (int) $selection->user_id : null,
            'hazards_substance_data_id' => (int) ($selection->hazards_substance_data_id),
            'data_source' => $metadata?->data_source ?? $row?->data_source ?? 'N/A',
            'test_type' => $metadata?->test_type ?? $this->resolveTestTypeLabel($row?->test_type),
            'norman_parameter_name' => $metadata?->assessment_parameter_name ?? $row?->norman_parameter_name ?? 'N/A',
            'original_value' => $this->formatNumber($metadata?->original_value ?? $row?->original_value),
            'original_unit' => $metadata?->original_unit ?? $row?->original_unit ?? 'N/A',
            'value_assessment_index' => $this->formatNumber($metadata?->assessment_value ?? $row?->value_assessment_index),
            'unit' => $metadata?->assessment_unit ?? $row?->unit ?? 'N/A',
            'assessment_class' => $metadata?->norman_classification ?? $row?->assessment_class ?? 'N/A',
            'classification_type' => $classificationType,
            'vote' => $metadata?->norman_vote ?? ($selection->kind === 'vote' ? 3 : $this->defaultNormanVoteForRow($row)),
            'expert' => $metadata?->editor ?? $this->resolveUserDisplayName($selection),
            'date' => $metadata?->record_date ?? $selection->created_at,
            'active' => (bool) $selection->is_current,
        ];
    }

    private function ensureMetadataSnapshot(DerivationSelection $selection): void
    {
        $existingMetadata = DerivationMetadata::query()
            ->where('selection_id', $selection->id)
            ->exists();

        if (! $existingMetadata) {
            $this->createMetadataSnapshot($selection);
        }
    }

    private function resolveUserDisplayName(DerivationSelection $selection): string
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
            return 'N/A';
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

        return (string) ($user->email ?? 'N/A');
    }

    private function resolveTestTypeLabel(mixed $value): string
    {
        return match ((string) ($value ?? '')) {
            '2' => 'Experimental',
            '3' => 'Predicted',
            '1' => 'Other',
            default => (string) ($value ?: 'N/A'),
        };
    }

    private function defaultNormanVoteForRow(?ComptoxSubstanceData $row): int
    {
        if (! $row) {
            return 1;
        }

        return (string) $row->test_type === '2' ? 2 : 1;
    }

    private function resolveSelectionSourceType(?ComptoxSubstanceData $row): string
    {
        if (! $row) {
            return 'N/A';
        }

        $dataSource = strtolower((string) ($row->data_source ?? ''));
        if (str_contains($dataSource, 'janus')) {
            return 'JANUS';
        }

        if ((string) ($row->test_type ?? '') === '2') {
            return 'NORMANexp';
        }

        return 'PikMe';
    }

    private function formatNumber(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        $numericValue = (float) $value;
        $absoluteValue = abs($numericValue);

        if ($numericValue === 0.0) {
            return '0';
        }

        if ($absoluteValue > 0 && $absoluteValue < 0.001) {
            $formatted = sprintf('%.3e', $numericValue);
            $formatted = preg_replace('/\.?0+e/i', 'e', $formatted) ?? $formatted;
            $formatted = preg_replace('/e\+?(-?)0*(\d+)/i', 'e$1$2', $formatted) ?? $formatted;

            return strtolower($formatted);
        }

        $formatted = number_format($numericValue, 4, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = str_replace(',', '.', $value);
            if (is_numeric($normalized)) {
                return (float) $normalized;
            }
        }

        return null;
    }

    private function toNullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function toNullableDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }
}
