<?php

namespace App\Livewire\Hazards;

use App\Models\Hazards\ComptoxSubstanceData;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SubstanceSearch extends Component
{
    public $search = '';

    public $searchType = 'name';

    public $selectedSubstanceIds = [];

    public $selectedSubstances = [];

    public $existingSubstances = [];

    public $formId = 'searchHazards';

    public function mount($existingSubstances = [], $formId = 'searchHazards')
    {
        $this->formId = $formId ?: 'searchHazards';

        if (! empty($existingSubstances)) {
            $this->selectedSubstanceIds = is_array($existingSubstances)
                ? [array_values($existingSubstances)[0]]
                : [$existingSubstances];

            $this->applySubstanceFilter();
        }
    }

    public function render()
    {
        $results = [];
        $resultsAvailable = false;

        if (strlen($this->search) > 2) {
            $query = Substance::query()
                ->whereExists(static function ($exists): void {
                    $exists->select(DB::raw('1'))
                        ->from('hazards_comptox_substance_data')
                        ->whereColumn('hazards_comptox_substance_data.susdat_substance_id', 'susdat_substances.id')
                        ->whereNull('hazards_comptox_substance_data.withdrawn_at');
                });

            $term = $this->escapeLike((string) $this->search);

            if ($this->searchType === 'cas_number') {
                $query->where('cas_number', 'ilike', '%'.$term.'%');
            } elseif ($this->searchType === 'name') {
                $query->where('name', 'ilike', '%'.$term.'%');
            } elseif ($this->searchType === 'stdinchikey') {
                $query->where('stdinchikey', 'ilike', $term);
            } elseif ($this->searchType === 'norman_susdat_id') {
                $searchCode = (string) $this->search;
                if (strtoupper(substr($searchCode, 0, 2)) === 'NS') {
                    $searchCode = substr($searchCode, 2);
                }
                $query->where('code', 'ilike', '%'.$this->escapeLike($searchCode).'%');
            }

            $results = $query->orderBy('name')->limit(30)->get();

            if ($results->isNotEmpty()) {
                $counts = ComptoxSubstanceData::query()
                    ->active()
                    ->whereIn('susdat_substance_id', $results->pluck('id'))
                    ->selectRaw('susdat_substance_id, COUNT(*) as records_count')
                    ->groupBy('susdat_substance_id')
                    ->pluck('records_count', 'susdat_substance_id');

                foreach ($results as $result) {
                    $result->hazards_record_count = (int) ($counts[$result->id] ?? 0);
                }

                $results = $results->sortByDesc('hazards_record_count')->values();
            }

            $resultsAvailable = $results->isNotEmpty();
        }

        return view('livewire.hazards.substance-search', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'searchType' => $this->searchType,
            'selectedSubstances' => $this->selectedSubstances,
            'formId' => $this->formId,
        ]);
    }

    /**
     * Escapes the LIKE wildcards so the user's text is matched literally.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public function applySubstanceFilter()
    {
        if (! is_array($this->selectedSubstanceIds)) {
            $this->selectedSubstanceIds = [$this->selectedSubstanceIds];
        }

        if (count($this->selectedSubstanceIds) > 0) {
            $substanceId = end($this->selectedSubstanceIds);
            $this->selectedSubstanceIds = [$substanceId];

            $substance = Substance::find($substanceId);
            if ($substance) {
                $recordCount = ComptoxSubstanceData::query()
                    ->active()
                    ->where('susdat_substance_id', $substance->id)
                    ->count();

                $this->selectedSubstances = [[
                    'id' => $substance->id,
                    'name' => $substance->name,
                    'cas_number' => $substance->cas_number,
                    'code' => $substance->code,
                    'stdinchikey' => $substance->stdinchikey,
                    'hazards_record_count' => $recordCount,
                ]];

                $this->dispatch('autoSubmitForm');
            } else {
                $this->selectedSubstances = [];
            }
        } else {
            $this->selectedSubstances = [];
        }

        $this->search = '';
    }

    public function removeSubstance($substanceId)
    {
        $this->selectedSubstanceIds = [];
        $this->selectedSubstances = [];
        $this->search = '';
    }

    public function clearFilters()
    {
        $this->search = '';
    }
}
