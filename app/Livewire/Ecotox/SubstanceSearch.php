<?php

namespace App\Livewire\Ecotox;

use Livewire\Component;
use App\Models\Susdat\Substance;
use App\Models\Ecotox\EcotoxSubstanceDistinct;

class SubstanceSearch extends Component
{
    public $search = '';
    public $searchType = 'name';
    public $selectedSubstanceIds = []; // Track selected substance IDs
    public $selectedSubstances = []; // Store selected substances
    public $existingSubstances = []; // Provided substances for initialization
    
    public function mount($existingSubstances = [])
    {
        // Set the initial substances based on the provided data
        if(!empty($existingSubstances)) {
            $this->selectedSubstanceIds = $existingSubstances;
            $this->applySubstanceFilter();
        }
    }
    
    public function render()
    {
        $results = [];
        $resultsAvailable = false;
        $ecotoxSubstanceIds = EcotoxSubstanceDistinct::pluck('substance_id')->toArray();
        
        if(strlen($this->search) > 2) {
            $results = Substance::whereIn('id', $ecotoxSubstanceIds)->orderBy('id', 'asc');
            if($this->searchType == 'cas_number') {
                $results = $results->where('cas_number', 'ilike', '%' . $this->search . '%');
            } elseif($this->searchType == 'name') {
                $results = $results->where('name', 'ilike', '%' . $this->search . '%');
            } elseif($this->searchType == 'stdinchikey') {
                $results = $results->where('stdinchikey', 'ilike', $this->search);
            } else{
                $results = $results->where('id', '<=', 30);
            }
            
            $results = $results->limit(30)->get();
            $resultsAvailable = true;
        }
        return view('livewire.ecotox.substance-search', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'searchType' => $this->searchType,
            'selectedSubstances' => $this->selectedSubstances, // Pass selected substances to the view
        ]);
        
        
    }
    
    public function applySubstanceFilter()
    {
        // Fetch the selected substances based on their IDs
        
        if (!is_array($this->selectedSubstanceIds)) {
            $this->selectedSubstanceIds = [$this->selectedSubstanceIds];
        }
        
        $this->selectedSubstances = Substance::whereIn('id', $this->selectedSubstanceIds)
        ->get()
        ->map(function ($substance) {
            return [
                'id' => $substance->id,
                'name' => $substance->name,
                'cas_number' => $substance->cas_number,
                'stdinchikey' => $substance->stdinchikey,
            ];
        })
        ->toArray();
        $this->search = '';
    }
    
    public function removeSubstance($substanceId)
    {
        // Remove the substance from the selected list
        
        $this->selectedSubstanceIds = array_filter($this->selectedSubstanceIds, function ($id) use ($substanceId) {
            return (string) $id !== (string) $substanceId;
        });
        
        // Reapply the filter to update selectedSubstances
        $this->applySubstanceFilter();
    }
    
    public function clearFilters()
    {
        // Reset only the list of selected substances
        $this->search = '';
    }
    
    
}


// public function render()
// {
//     return view('livewire.backend.substance-search');
// }