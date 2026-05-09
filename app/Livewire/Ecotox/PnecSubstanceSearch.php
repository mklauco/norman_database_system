<?php

namespace App\Livewire\Ecotox;

use App\Models\Ecotox\EcotoxSubstanceDistinctPnec3;
use App\Models\Susdat\Substance;
use Livewire\Component;

class PnecSubstanceSearch extends Component
{
    public $search = '';

    public $searchType = 'name';

    public $selectedSubstanceIds = [];

    public $selectedSubstances = [];

    public $existingSubstances = [];

    public function mount($existingSubstances = [])
    {
        // Set the initial substance based on the provided data
        if (! empty($existingSubstances)) {
            // Take only the first item if multiple are provided, as we now support only one selection
            $this->selectedSubstanceIds = is_array($existingSubstances) ?
                [array_values($existingSubstances)[0]] : [$existingSubstances];

            $this->applySubstanceFilter();
        }
    }

    public function render()
    {
        $results = [];
        $resultsAvailable = false;

        if (strlen($this->search) > 2) {
            // Start query to search substances
            $query = Substance::query();

            // Apply search filters
            if ($this->searchType == 'cas_number') {
                $query = $query->where('cas_number', 'LIKE', '%'.$this->search.'%');
            } elseif ($this->searchType == 'name') {
                $query = $query->where('name', 'LIKE', '%'.$this->search.'%');
            } elseif ($this->searchType == 'stdinchikey') {
                $query = $query->where('stdinchikey', 'LIKE', $this->search.'%');
            }

            // Get substances
            $substances = $query->limit(100)->get();

            // Filter to only those that have PNEC3 records
            $substanceIds = $substances->pluck('id')->toArray();
            $recordCounts = EcotoxSubstanceDistinctPnec3::whereIn('substance_id', $substanceIds)
                ->pluck('record_count', 'substance_id')
                ->toArray();

            // Add record count to each substance and filter/sort
            $results = $substances->filter(function ($substance) use ($recordCounts) {
                return isset($recordCounts[$substance->id]);
            })
                ->map(function ($substance) use ($recordCounts) {
                    $substance->pnec3_record_count = $recordCounts[$substance->id];

                    return $substance;
                })
                ->sortByDesc('pnec3_record_count')
                ->take(30)
                ->values();

            $resultsAvailable = count($results) > 0;
        }

        return view('livewire.ecotox.pnec-substance-search', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'searchType' => $this->searchType,
            'selectedSubstances' => $this->selectedSubstances,
        ]);
    }

    public function applySubstanceFilter()
    {
        // Make sure selectedSubstanceIds is an array with a single value
        if (! is_array($this->selectedSubstanceIds)) {
            $this->selectedSubstanceIds = [$this->selectedSubstanceIds];
        }

        // Keep only one substance ID (the latest one selected)
        if (count($this->selectedSubstanceIds) > 0) {
            $substanceId = end($this->selectedSubstanceIds);
            $this->selectedSubstanceIds = [$substanceId];

            // Fetch the selected substance
            $substance = Substance::find($substanceId);
            if ($substance) {
                // Check if this substance has records in EcotoxSubstanceDistinctPnec3
                $recordCount = EcotoxSubstanceDistinctPnec3::where('substance_id', $substance->id)
                    ->value('record_count') ?? 0;

                // Replace the selected substances array with just this one substance
                $this->selectedSubstances = [[
                    'id' => $substance->id,
                    'name' => $substance->name,
                    'cas_number' => $substance->cas_number,
                    'stdinchikey' => $substance->stdinchikey,
                    'pnec3_record_count' => $recordCount,
                ]];

                // Auto-submit the parent form
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
        // Clear the selection
        $this->selectedSubstanceIds = [];
        $this->selectedSubstances = [];
        $this->search = '';
    }

    public function clearFilters()
    {
        // Reset the search
        $this->search = '';
    }
}
