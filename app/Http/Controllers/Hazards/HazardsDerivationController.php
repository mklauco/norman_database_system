<?php

namespace App\Http\Controllers\Hazards;

use App\Http\Controllers\Controller;
use App\Models\Hazards\ComptoxSubstanceData;
use App\Models\Hazards\DerivationSelection;
use App\Services\Hazards\HazardsDerivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Susdat\Substance;

class HazardsDerivationController extends Controller
{
    public function __construct(
        private readonly HazardsDerivationService $derivationService
    ) {
    }

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

    public function index(string $susdatSubstanceId)
    {
        $data = $this->derivationService->getDerivationPageData((int) $susdatSubstanceId);

        return view('hazards.derivation.index', [
            'susdatSubstanceId' => (int) $susdatSubstanceId,
            'substance' => $data['substance'],
            'candidates' => $data['candidates'],
            'currentAuto' => $data['current_auto'],
            'currentVotes' => $data['current_votes'],
            'votedMap' => $data['voted_map'],
        ]);
    }

    public function vote(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'susdat_substance_id' => ['required', 'integer', 'exists:susdat_substances,id'],
            'bucket' => ['required', 'string', 'max:12'],
            'hazards_substance_data_id' => ['required', 'integer', 'exists:hazards_comptox_substance_data,id'],
        ]);

        $this->derivationService->storeVote(
            susdatSubstanceId: (int) $data['susdat_substance_id'],
            bucket: (string) $data['bucket'],
            hazardsSubstanceDataId: (int) $data['hazards_substance_data_id'],
            userId: (int) ($request->user()?->id ?? 0),
            metadataOverrides: $request->all(),
            userName: null
        );

        return back()->with('success', 'Derivation vote stored.');
    }

    public function removeVote(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'selection_id' => ['required', 'integer', 'exists:hazards_derivation_selections,id'],
        ]);

        $this->derivationService->removeVote((int) $data['selection_id']);

        return back()->with('success', 'Derivation vote removed.');
    }

    public function substanceDataJson(int $id): JsonResponse
    {
        $row = ComptoxSubstanceData::with(['substance', 'editorUser'])->find($id);
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
            'substance' => (object) [
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

    public function selectionJson(int $selectionId): JsonResponse
    {
        $selection = DerivationSelection::with(['hazardsSubstanceData', 'user'])->find($selectionId);
        if (! $selection) {
            return response()->json(['error' => 'Selection not found'], 404);
        }

        return response()->json($selection);
    }
}


