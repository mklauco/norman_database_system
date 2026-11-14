<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hazards;

use App\Http\Controllers\Controller;
use App\Models\Hazards\ClassificationVote;
use App\Models\Hazards\ComptoxSubstanceData;
use App\Models\Hazards\DerivationSelection;
use App\Models\Hazards\SubstanceClassification;
use App\Models\Susdat\Substance;
use App\Models\User;
use App\Services\Hazards\HazardsClassificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HazardsClassificationController extends Controller
{
    public function __construct(
        private readonly HazardsClassificationService $classificationService
    ) {}

    public function filter(Request $request)
    {
        return view('hazards.classification.filter', [
            'request' => $request,
        ]);
    }

    public function search(Request $request): RedirectResponse
    {
        $substances = $request->input('substances', []);

        if (is_string($substances)) {
            $decoded = json_decode($substances, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $substances = $decoded;
            } else {
                $substances = [$substances];
            }
        }

        if (! is_array($substances)) {
            $substances = [$substances];
        }

        $substances = array_values(array_filter($substances, static fn ($value) => ! empty($value)));

        if (count($substances) === 0) {
            return redirect()
                ->route('hazards.classification.search.filter')
                ->with('info', 'Please select a substance for classification.');
        }

        $substanceId = (int) $substances[0];
        if (! Substance::query()->whereKey($substanceId)->exists()) {
            return redirect()
                ->route('hazards.classification.search.filter')
                ->with('error', 'Selected substance was not found.');
        }

        return redirect()->route('hazards.classification.index', [
            'susdatSubstanceId' => $substanceId,
        ]);
    }

    public function index(string $susdatSubstanceId)
    {
        $substanceId = (int) $susdatSubstanceId;
        $currentUserId = (int) (auth()->id() ?? 0);

        $substance = Substance::find($substanceId);

        $selections = DerivationSelection::query()
            ->with(['hazardsSubstanceData', 'user'])
            ->where('susdat_substance_id', $substanceId)
            ->where('is_current', true)
            ->orderBy('bucket')
            ->orderByDesc('kind')
            ->orderByDesc('id')
            ->get();

        $classificationVotes = ClassificationVote::query()
            ->with('user')
            ->where('susdat_substance_id', $substanceId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $currentUserVotes = $classificationVotes
            ->where('user_id', $currentUserId)
            ->where('is_current', true)
            ->keyBy(fn (ClassificationVote $vote) => $vote->classification_type.'|'.$vote->criterion);

        $conclusions = SubstanceClassification::query()
            ->with(['editor', 'supports'])
            ->where('susdat_substance_id', $substanceId)
            ->orderByRaw('editor_user_id IS NULL ASC')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (SubstanceClassification $row) {
                $row->editor_display_name = $this->resolveEditorDisplayName($row);

                return $row;
            });

        $activeAutoBaseline = $conclusions
            ->first(fn (SubstanceClassification $row) => $row->kind === 'auto_baseline' && $row->is_current);

        $activeDerivationConclusion = $conclusions
            ->first(fn (SubstanceClassification $row) => $row->kind === 'derivation' && $row->is_current);

        $classificationRows = $this->buildClassificationRows($selections, $currentUserVotes, $currentUserId);
        $activeConclusionId = $activeAutoBaseline?->id;

        if ($currentUserVotes->isNotEmpty()) {
            $activeConclusionId = $conclusions
                ->first(function (SubstanceClassification $row) use ($currentUserId) {
                    return $row->is_current
                        && $row->kind === 'classification'
                        && (int) $row->editor_user_id === $currentUserId;
                })?->id;
        } elseif ($activeDerivationConclusion) {
            $activeConclusionId = $activeDerivationConclusion->id;
        }

        return view('hazards.classification.index', [
            'susdatSubstanceId' => $substanceId,
            'substance' => (object) [
                'susdat_substance_id' => $substanceId,
                'prefixed_code' => $substance?->prefixed_code,
                'substance_name' => $substance?->display_name ?? $substance?->name,
                'cas_no' => $substance?->formatted_cas,
                'inchikey' => $substance?->stdinchikey,
                'dtxid' => $substance?->dtxid,
            ],
            'selections' => $selections,
            'classificationRows' => $classificationRows,
            'classificationVotes' => $classificationVotes,
            'conclusions' => $conclusions,
            'activeAutoBaseline' => $activeAutoBaseline,
            'activeConclusionId' => $activeConclusionId,
        ]);
    }

    public function vote(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'susdat_substance_id' => ['required', 'integer', 'exists:susdat_substances,id'],
            'votes' => ['nullable', 'array'],
            'votes.*.classification_type' => ['nullable', 'string', 'max:50'],
            'votes.*.criterion' => ['nullable', 'string', 'max:1'],
            'votes.*.classification_code' => ['nullable', 'string', 'max:50'],
            'votes.*.vote_value' => ['nullable'],
        ]);

        $this->classificationService->run((int) $data['susdat_substance_id']);
        $this->classificationService->storeVotes(
            susdatSubstanceId: (int) $data['susdat_substance_id'],
            userId: (int) (auth()->id() ?? 0),
            votes: $data['votes'] ?? []
        );

        return back()->with('success', 'Classification vote stored.');
    }

    private function buildClassificationRows(Collection $selections, Collection $currentUserVotes, int $currentUserId): array
    {
        if ($selections->isEmpty()) {
            return [];
        }

        $latestMetadata = $this->classificationService
            ->getLatestMetadataMapForSelections($selections->pluck('id'));

        $rows = [];

        foreach ($selections as $selection) {
            $type = $this->resolveClassificationType($selection);
            if (! isset($rows[$type])) {
                $rows[$type] = [
                    'type' => $type,
                    'P' => null,
                    'sum_P' => null,
                    'B' => null,
                    'sum_B' => null,
                    'M' => null,
                    'sum_M' => null,
                    'T' => null,
                    'sum_T' => null,
                ];
            }

            $criterion = strtoupper(substr((string) $selection->bucket, 0, 1));
            if (! in_array($criterion, ['P', 'B', 'M', 'T'], true)) {
                continue;
            }

            $metadata = $latestMetadata->get($selection->id);
            $defaultVote = $metadata?->norman_vote
                ?? ($selection->kind === 'vote'
                    ? 3
                    : $this->defaultNormanVoteForSelection($selection));
            $voteKey = $type.'|'.$criterion;
            $currentUserVote = $currentUserVotes->get($voteKey);

            $prefillVote = $currentUserVote?->vote_value;
            if ($prefillVote === null && $selection->kind === 'vote' && (int) $selection->user_id === $currentUserId) {
                $prefillVote = $defaultVote;
            }

            $rows[$type][$criterion] = [
                'selection_id' => (int) $selection->id,
                'hazards_substance_data_id' => (int) $selection->hazards_substance_data_id,
                'withdrawn' => $selection->hazardsSubstanceData?->withdrawn_at !== null,
                'classification' => $metadata?->norman_classification ?: $selection->hazardsSubstanceData?->assessment_class,
                'points' => $defaultVote,
                'kind' => $selection->kind,
                'active' => (bool) $selection->is_current,
                'source_label' => $selection->source_label,
                'parameter_name' => $selection->hazardsSubstanceData?->norman_parameter_name,
                'prefill_vote' => $prefillVote,
            ];
            $rows[$type]['sum_'.$criterion] = $defaultVote;
        }

        return array_values($rows);
    }

    private function resolveClassificationType(DerivationSelection $selection): string
    {
        if ($selection->kind === 'vote') {
            return $this->resolveSelectionUserDisplayName($selection);
        }

        $row = $selection->hazardsSubstanceData;
        if (! $row instanceof ComptoxSubstanceData) {
            return 'N/A';
        }

        $source = strtolower((string) ($row->data_source ?? ''));
        if (str_contains($source, 'janus')) {
            return 'JANUS';
        }

        if ((string) ($row->test_type ?? '') === '2') {
            return 'NORMANexp';
        }

        return 'PikMe';
    }

    private function resolveSelectionUserDisplayName(DerivationSelection $selection): string
    {
        if ($selection->kind === 'auto') {
            return 'NDS EXPERT';
        }

        return $this->displayNameOf($selection->user, 'Expert derivation');
    }

    private function resolveEditorDisplayName(SubstanceClassification $row): string
    {
        if ($row->kind === 'auto_baseline') {
            return 'NDS EXPERT';
        }

        if ($row->kind === 'derivation' && $row->editor === null) {
            return 'Expert derivation';
        }

        return $this->displayNameOf($row->editor, 'N/A');
    }

    private function displayNameOf(?User $user, string $fallback): string
    {
        if (! $user) {
            return $fallback;
        }

        foreach (['formatted_name', 'full_name', 'username'] as $attribute) {
            $value = trim((string) ($user->{$attribute} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return (string) ($user->email ?? $fallback);
    }

    private function defaultNormanVoteForSelection(DerivationSelection $selection): int
    {
        $testType = (string) ($selection->hazardsSubstanceData?->test_type ?? '');

        return $testType === '2' ? 2 : 1;
    }
}
