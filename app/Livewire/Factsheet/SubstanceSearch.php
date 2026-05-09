<?php

namespace App\Livewire\Factsheet;

use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class SubstanceSearch extends Component
{
    public $search = '';

    public $searchType = 'name';

    public $selectedSubstanceIds = [];

    public $selectedSubstances = [];

    public $existingSubstances = [];

    public function mount($existingSubstances = [])
    {
        if (! empty($existingSubstances)) {
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
            // Start query to get all substances (no filtering restrictions)
            $query = Substance::query();

            // Apply search filters
            if ($this->searchType == 'cas_number') {
                $searchTerm = str_replace('-', '', $this->search);
                $query = $query->where(function ($q) use ($searchTerm) {
                    $q->where('cas_number', 'ilike', '%'.$this->search.'%')
                        ->orWhere(DB::raw("REPLACE(cas_number, '-', '')"), 'ilike', '%'.$searchTerm.'%');
                });
            } elseif ($this->searchType == 'name') {
                $query = $query->where('name', 'ilike', '%'.$this->search.'%');
            } elseif ($this->searchType == 'stdinchikey') {
                $query = $query->where('stdinchikey', 'ilike', $this->search.'%');
            } elseif ($this->searchType == 'code') {
                // Strip "NS" prefix if present and search by code
                $searchCode = $this->search;
                if (strtoupper(substr($searchCode, 0, 2)) === 'NS') {
                    $searchCode = substr($searchCode, 2);
                }
                $query = $query->where('code', 'ilike', '%'.$searchCode.'%');
            }

            // Order by name and limit results
            $results = $query
                ->orderBy('name', 'asc')
                ->limit(30)
                ->get();

            if (config('app.debug')) {
                Log::info('SubstanceSearch query returned '.$results->count().' results for search: "'.$this->search.'"');
            }

            $resultsAvailable = $results->count() > 0;
        }

        return view('livewire.factsheet.substance-search', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'searchType' => $this->searchType,
            'selectedSubstances' => $this->selectedSubstances,
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

            // Fetch the selected substance
            $substance = Substance::find($substanceId);
            if ($substance) {
                $this->selectedSubstances = [[
                    'id' => $substance->id,
                    'name' => $substance->name,
                    'cas_number' => $substance->cas_number,
                    'stdinchikey' => $substance->stdinchikey,
                ]];

                $this->dispatch('substancesSelected', substances: $this->selectedSubstances);
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
