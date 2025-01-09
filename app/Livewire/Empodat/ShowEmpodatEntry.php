<?php

namespace App\Livewire\Empodat;

use Livewire\Component;
use App\Models\Empodat\EmpodatMain;

class ShowEmpodatEntry extends Component
{

    public $showModal = false; // Tracks modal visibility
    public $recordId; // Stores the ID passed to the component
    public $empodat; // Stores data related to the ID

    public function mount($recordId = null)
    {
        $this->recordId = $recordId;
        $this->fetchData();
    }

    public function fetchData()
    {

        $empodat = EmpodatMain::query()

        // Eager load relationships (as needed)
        ->with('concetrationIndicator') 
        ->with('station') 
        ->with('analyticalMethod') 
        // ->with('substance') 
    
        // Joins
        ->leftJoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
        // ->leftJoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')
        // ->leftJoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id')
        // ->leftJoin('list_countries', 'empodat_stations.country_id', '=', 'list_countries.id')
        // ->join('empodat_data_sources', 'empodat_data_sources.id', '=', 'empodat_main.data_source_id')
        // ->join('empodat_analytical_methods', 'empodat_analytical_methods.id', '=', 'empodat_main.method_id')
        // ->join('susdat_category_substance', 'susdat_category_substance.substance_id', '=', 'empodat_main.substance_id')
        // ->join('susdat_source_substance', 'susdat_source_substance.substance_id', '=', 'empodat_main.substance_id')
    
        // Finally, constrain it to a single empodat_main.id
        ->where('empodat_main.id', $this->recordId)
    
        // Choose which columns you actually need:
        // ->select([
        //     'empodat_main.* AS empodat_main.*',
        // //     'susdat_substances.name AS substance_name',
        // //     'list_matrices.name AS matrix_name',
        //     'empodat_stations.* AS empodat_stations.*',
        // //     'list_countries.country_name',
        // //     'empodat_data_sources.laboratory1_id',
        // //     'empodat_data_sources.organisation_id',
        // //     'empodat_analytical_methods.rating',
        // //     // etc...
        // ])
    
        // Execute
        ->first();  // or ->get(), depending on whether you expect one record or multiple
// dd($empodat);
// foreach ($empodat->toArray()  as $key => $value) {
//     dd($key, $value);
// }
// dd($empodat->name);
        // Simulate fetching data based on the ID
        // Replace this with your actual data fetching logic
        if($empodat) {
            $this->empodat = $empodat;
        } else {
            $this->empodat = ['0' => 'No data found'];
        }
        


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
        return view('livewire.empodat.show-empodat-entry', [
            'empodat' => $this->empodat
        ]);
    }
}
