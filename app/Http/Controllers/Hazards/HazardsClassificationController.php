<?php

namespace App\Http\Controllers\Hazards;

use App\Http\Controllers\Controller;
use App\Models\Hazards\ClassificationVote;
use App\Models\Hazards\DerivationSelection;
use App\Models\Hazards\SubstanceClassification;
use App\Models\Hazards\ComptoxSubstanceData;
use App\Models\Susdat\Substance;
use App\Services\Hazards\HazardsClassificationService;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HazardsClassificationController extends Controller
{
    private const AUTO_USER_ID = 3;

    public function __construct(
        private readonly HazardsClassificationService $classificationService
    ) {
    }

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

        $this->classificationService->run($substanceId);

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
            ->keyBy(fn (ClassificationVote $vote) => $vote->classification_type . '|' . $vote->criterion);

        $conclusions = SubstanceClassification::query()
            ->with(['editor', 'supports'])
            ->where('susdat_substance_id', $substanceId)
            ->orderByRaw('editor_user_id = ' . self::AUTO_USER_ID . ' ASC')
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
        $activeEditorId = self::AUTO_USER_ID;
        $activeConclusionId = $activeAutoBaseline?->id;

        if ($currentUserVotes->isNotEmpty()) {
            $activeEditorId = $currentUserId;
            $activeConclusionId = $conclusions
                ->first(function (SubstanceClassification $row) use ($activeEditorId) {
                    return $row->is_current && (int) $row->editor_user_id === $activeEditorId;
                })?->id;
        } elseif ($activeDerivationConclusion) {
            $activeConclusionId = $activeDerivationConclusion->id;
        }

        return view('hazards.classification.index', [
            'susdatSubstanceId' => $substanceId,
            'substance' => (object) [
                'susdat_substance_id' => $substanceId,
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
            $voteKey = $type . '|' . $criterion;
            $currentUserVote = $currentUserVotes->get($voteKey);

            $prefillVote = $currentUserVote?->vote_value;
            if ($prefillVote === null && $selection->kind === 'vote' && (int) $selection->user_id === $currentUserId) {
                $prefillVote = $defaultVote;
            }

            $rows[$type][$criterion] = [
                'selection_id' => (int) $selection->id,
                'hazards_substance_data_id' => (int) $selection->hazards_substance_data_id,
                'classification' => $metadata?->norman_classification ?: $selection->hazardsSubstanceData?->assessment_class,
                'points' => $defaultVote,
                'kind' => $selection->kind,
                'active' => (bool) $selection->is_current,
                'source_label' => $selection->source_label,
                'parameter_name' => $selection->hazardsSubstanceData?->norman_parameter_name,
                'prefill_vote' => $prefillVote,
            ];
            $rows[$type]['sum_' . $criterion] = $defaultVote;
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

    private function resolveEditorDisplayName(SubstanceClassification $row): string
    {
        $user = $row->editor;
        if ((int) $row->editor_user_id === self::AUTO_USER_ID && $user) {
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

        if ((int) $row->editor_user_id === self::AUTO_USER_ID) {
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

    private function defaultNormanVoteForSelection(DerivationSelection $selection): int
    {
        $testType = (string) ($selection->hazardsSubstanceData?->test_type ?? '');

        return $testType === '2' ? 2 : 1;
    }
}
