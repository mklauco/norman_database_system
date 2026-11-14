<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hazards;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hazards\HazardsDerivationVoteRequest;
use App\Http\Requests\Hazards\HazardsRemoveVoteRequest;
use App\Models\Hazards\ComptoxSubstanceData;
use App\Models\Hazards\DerivationSelection;
use App\Models\Susdat\Substance;
use App\Services\Hazards\HazardsClassificationService;
use App\Services\Hazards\HazardsDerivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HazardsDerivationController extends Controller
{
    public function __construct(
        private readonly HazardsDerivationService $derivationService,
        private readonly HazardsClassificationService $classificationService
    ) {}

    public function filter(Request $request)
    {
        return view('hazards.derivation.filter', [
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
                ->route('hazards.derivation.search.filter')
                ->with('info', 'Please select a substance for derivation.');
        }

        $substanceId = (int) $substances[0];
        if (! Substance::query()->whereKey($substanceId)->exists()) {
            return redirect()
                ->route('hazards.derivation.search.filter')
                ->with('error', 'Selected substance was not found.');
        }

        return redirect()->route('hazards.derivation.index', ['susdatSubstanceId' => $substanceId]);
    }

    public function index(Request $request, string $susdatSubstanceId)
    {
        $substanceId = (int) $susdatSubstanceId;

        $data = $this->derivationService->getDerivationPageData(
            $substanceId,
            (int) ($request->user()?->id ?? 0)
        );

        return view('hazards.derivation.index', [
            'susdatSubstanceId' => $substanceId,
            'substance' => $data['substance'],
            'candidates' => $data['candidates'],
            'currentAuto' => $data['current_auto'],
            'currentVotes' => $data['current_votes'],
            'votedMap' => $data['voted_map'],
        ]);
    }

    public function vote(HazardsDerivationVoteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $selection = $this->derivationService->storeVote(
            susdatSubstanceId: (int) $data['susdat_substance_id'],
            bucket: (string) $data['bucket'],
            hazardsSubstanceDataId: (int) $data['hazards_substance_data_id'],
            userId: (int) $request->user()->id,
            metadataOverrides: $request->metadataOverrides(),
            userName: $request->editorName(),
            dataRow: $request->dataRow()
        );

        $this->classificationService->run((int) $selection->susdat_substance_id);

        return back()->with('success', 'Derivation vote stored.');
    }

    public function removeVote(HazardsRemoveVoteRequest $request): RedirectResponse
    {
        $user = $request->user();
        $selectionId = (int) $request->validated('selection_id');
        $isAdmin = $user->hasAnyRole(['super_admin', 'admin']);

        $selection = DerivationSelection::query()->find($selectionId);
        $removed = $this->derivationService->removeVote($selectionId, $isAdmin ? null : (int) $user->id);

        if (! $removed) {
            abort(403, 'You can only remove your own votes.');
        }

        if ($isAdmin) {
            Log::info('Hazards derivation vote removed by an administrator', [
                'actor_user_id' => $user->id,
                'selection_id' => $selectionId,
            ]);
        }

        if ($selection) {
            $this->classificationService->run((int) $selection->susdat_substance_id);
        }

        return back()->with('success', 'Derivation vote removed.');
    }

    public function substanceDataJson(int $id): JsonResponse
    {
        $row = ComptoxSubstanceData::with(['substance', 'editorUser:id,first_name,last_name'])->find($id);
        if (! $row) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        return response()->json($row);
    }

    public function metadataShow(int $selectionId)
    {
        $selection = DerivationSelection::with(['hazardsSubstanceData', 'substance', 'user'])->find($selectionId);
        if (! $selection) {
            abort(404);
        }

        $metadata = $this->derivationService->getMetadataForSelection($selectionId);
        if (! $metadata) {
            abort(404);
        }

        $substance = $selection->substance;
        $row = $selection->hazardsSubstanceData;

        return view('hazards.derivation.metadata', [
            'selection' => $selection,
            'metadata' => $metadata,
            'withdrawn' => $row?->withdrawn_at !== null,
            'substance' => (object) [
                'prefixed_code' => $substance?->prefixed_code,
                'substance_name' => $substance?->display_name ?? $substance?->name ?? $metadata->substance_name ?? $row?->substance_name,
                'cas_no' => $substance?->formatted_cas ?? $metadata->cas_number ?? $row?->cas_no,
                'inchikey' => $substance?->stdinchikey ?? $row?->inchikey,
            ],
        ]);
    }

    public function metadataJson(int $selectionId): JsonResponse
    {
        $metadata = $this->derivationService->getMetadataForSelection($selectionId);
        if (! $metadata) {
            return response()->json(['error' => 'Metadata not found'], 404);
        }

        return response()->json($metadata);
    }
}
