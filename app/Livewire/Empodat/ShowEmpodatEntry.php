<?php

namespace App\Livewire\Empodat;

use Livewire\Component;

class ShowEmpodatEntry extends Component
{

    public $showModal = false; // Tracks modal visibility
    public $recordId; // Stores the ID passed to the component
    public $recordData; // Stores data related to the ID

    public function mount($recordId = null)
    {
        $this->recordId = $recordId;
        $this->fetchData();
    }

    public function fetchData()
    {
        // Simulate fetching data based on the ID
        // Replace this with your actual data fetching logic
        $this->recordData = $this->recordId
            ? "Content for record ID: {$this->recordId}"
            : "No record ID provided.";
    }

    public function openModal($id)
    {
        $this->recordId = $id;
        $this->fetchData();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }
    
    public function render()
    {
        return view('livewire.empodat.show-empodat-entry');
    }
}
