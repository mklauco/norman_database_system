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
            $hazardsSubstanceIds = ComptoxSubstanceData::query()
                ->whereNotNull('susdat_substance_id')
                ->distinct()
                ->pluck('susdat_substance_id')
                ->toArray();

            if (! empty($hazardsSubstanceIds)) {
                $query = Substance::whereIn('id', $hazardsSubstanceIds);

                if ($this->searchType === 'cas_number') {
                    $query->where('cas_number', 'ilike', '%'.$this->search.'%');
                } elseif ($this->searchType === 'name') {
                    $query->where('name', 'ilike', '%'.$this->search.'%');
                } elseif ($this->searchType === 'stdinchikey') {
                    $query->where('stdinchikey', 'ilike', $this->search);
                } elseif ($this->searchType === 'norman_susdat_id') {
                    $searchCode = $this->search;
                    if (strtoupper(substr($searchCode, 0, 2)) === 'NS') {
                        $searchCode = substr($searchCode, 2);
                    }
                    $query->where('code', 'ilike', '%'.$searchCode.'%');
                }

                $results = $query->select([
                    'susdat_substances.*',
                    DB::raw('(SELECT COUNT(*) FROM hazards_comptox_substance_data WHERE susdat_substance_id = susdat_substances.id) as hazards_record_count'),
                ])
                    ->orderBy('hazards_record_count', 'desc')
                    ->limit(30)
                    ->get();

                $resultsAvailable = count($results) > 0;
            }
        }

        return view('livewire.hazards.substance-search', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'searchType' => $this->searchType,
            'selectedSubstances' => $this->selectedSubstances,
            'formId' => $this->formId,
        ]);
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
