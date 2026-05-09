<?php

namespace App\Livewire\Ecotox;

use App\Models\Ecotox\EcotoxSubstanceDistinctPnec3;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class QualitySubstanceSearch extends Component
{
    public $search = '';

    public $searchType = 'name';

    public $selectedSubstanceIds = []; // Track selected substance ID (singular since we're using radio buttons)

    public $selectedSubstances = []; // Store selected substance (just one)

    public $existingSubstances = []; // Provided substances for initialization

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
            // OPTIMIZATION 1: Use a join instead of plucking all IDs into memory
            // This avoids loading potentially thousands of IDs into PHP memory

            // Build the query using a join for better performance
            $query = Substance::query()
                ->join('ecotox_pnec3_substance_distinct', 'susdat_substances.id', '=', 'ecotox_pnec3_substance_distinct.substance_id')
                ->select([
                    'susdat_substances.*',
                    'ecotox_pnec3_substance_distinct.record_count as ecotox_record_count',
                ])
                ->where('ecotox_pnec3_substance_distinct.record_count', '>', 0); // Only get substances with records

            // Apply search filters with proper indexing hints
            if ($this->searchType == 'cas_number') {
                // Use exact match for CAS number if possible, or pattern match
                $searchTerm = str_replace('-', '', $this->search); // Remove dashes for flexible matching
                $query = $query->where(function ($q) use ($searchTerm) {
                    $q->where('susdat_substances.cas_number', 'ilike', '%'.$this->search.'%')
                        ->orWhere(DB::raw("REPLACE(susdat_substances.cas_number, '-', '')"), 'ilike', '%'.$searchTerm.'%');
                });
            } elseif ($this->searchType == 'name') {
                // OPTIMIZATION 2: Use full-text search if available, otherwise use indexed column
                $query = $query->where('susdat_substances.name', 'ilike', '%'.$this->search.'%');
            } elseif ($this->searchType == 'stdinchikey') {
                // StdInChIKey should be exact or prefix match for better performance
                $query = $query->where('susdat_substances.stdinchikey', 'ilike', $this->search.'%');
            }

            // OPTIMIZATION 3: Order by record count and limit
            $results = $query
                ->orderBy('ecotox_pnec3_substance_distinct.record_count', 'desc')
                ->orderBy('susdat_substances.name', 'asc')
                ->limit(30)
                ->get();

            // Debug logging (can be removed in production)
            if (config('app.debug')) {
                Log::info('QualitySubstanceSearch query returned '.$results->count().' results for search: "'.$this->search.'"');
            }

            $resultsAvailable = $results->count() > 0;
        }

        return view('livewire.ecotox.quality-substance-search', [
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

            // OPTIMIZATION 4: Fetch substance with record count in a single query
            $substance = Substance::query()
                ->leftJoin('ecotox_pnec3_substance_distinct', 'susdat_substances.id', '=', 'ecotox_pnec3_substance_distinct.substance_id')
                ->select([
                    'susdat_substances.*',
                    DB::raw('COALESCE(ecotox_pnec3_substance_distinct.record_count, 0) as ecotox_record_count'),
                ])
                ->where('susdat_substances.id', $substanceId)
                ->first();

            if ($substance) {
                // Replace the selected substances array with just this one substance
                $this->selectedSubstances = [[
                    'id' => $substance->id,
                    'name' => $substance->name,
                    'cas_number' => $substance->cas_number,
                    'stdinchikey' => $substance->stdinchikey,
                    'ecotox_record_count' => $substance->ecotox_record_count,
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

    /**
     * Optional: Cache the count of available substances to avoid repeated counts
     */
    protected function getAvailableSubstanceCount()
    {
        return Cache::remember('ecotox_pnec3_substance_count', 300, function () {
            return EcotoxSubstanceDistinctPnec3::where('record_count', '>', 0)->count();
        });
    }
}
