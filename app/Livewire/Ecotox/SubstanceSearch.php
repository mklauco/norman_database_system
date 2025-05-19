<?php

namespace App\Livewire\Ecotox;

use Livewire\Component;
use App\Models\Susdat\Substance;
use App\Models\Ecotox\EcotoxSubstanceDistinct;
use Illuminate\Support\Facades\DB;

class SubstanceSearch extends Component
{
    public $search = '';
    public $searchType = 'name';
    public $selectedSubstanceIds = []; // Track selected substance ID (singular since we're using radio buttons)
    public $selectedSubstances = []; // Store selected substance (just one)
    public $existingSubstances = []; // Provided substances for initialization
    
    public function mount($existingSubstances = [])
    {
        // Set the initial substance based on the provided data
        if(!empty($existingSubstances)) {
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
        
        if(strlen($this->search) > 2) {
            // Get substance IDs that exist in the EcotoxSubstanceDistinct table
            $ecotoxSubstanceIds = EcotoxSubstanceDistinct::pluck('substance_id')->toArray();
            
            // Start query to get substances
            $query = Substance::whereIn('id', $ecotoxSubstanceIds);
            
            // Apply search filters
            if($this->searchType == 'cas_number') {
                $query = $query->where('cas_number', 'ilike', '%' . $this->search . '%');
            } elseif($this->searchType == 'name') {
                $query = $query->where('name', 'ilike', '%' . $this->search . '%');
            } elseif($this->searchType == 'stdinchikey') {
                $query = $query->where('stdinchikey', 'ilike', $this->search);
            }
            
            // Add additional info about record count
            $results = $query->select([
                'susdat_substances.*',
                DB::raw('(SELECT record_count FROM ecotox_main_3_substance_distinct WHERE substance_id = susdat_substances.id) as ecotox_record_count')
            ])
            ->orderBy('ecotox_record_count', 'desc')
            ->limit(30)
            ->get();
            
            $resultsAvailable = count($results) > 0;
        }
        
        return view('livewire.ecotox.substance-search', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'searchType' => $this->searchType,
            'selectedSubstances' => $this->selectedSubstances,
        ]);
    }
    
    public function applySubstanceFilter()
    {
        // Make sure selectedSubstanceIds is an array with a single value
        if (!is_array($this->selectedSubstanceIds)) {
            $this->selectedSubstanceIds = [$this->selectedSubstanceIds];
        }
        
        // Keep only one substance ID (the latest one selected)
        if (count($this->selectedSubstanceIds) > 0) {
            $substanceId = end($this->selectedSubstanceIds);
            $this->selectedSubstanceIds = [$substanceId];
            
            // Fetch the selected substance
            $substance = Substance::find($substanceId);
            if ($substance) {
                // Check if this substance has records in EcotoxSubstanceDistinct
                $recordCount = EcotoxSubstanceDistinct::where('substance_id', $substance->id)
                    ->value('record_count') ?? 0;
                    
                // Replace the selected substances array with just this one substance
                $this->selectedSubstances = [[
                    'id' => $substance->id,
                    'name' => $substance->name,
                    'cas_number' => $substance->cas_number,
                    'stdinchikey' => $substance->stdinchikey,
                    'ecotox_record_count' => $recordCount
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