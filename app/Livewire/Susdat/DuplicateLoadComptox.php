<?php

namespace App\Livewire\Susdat;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class DuplicateLoadComptox extends Component
{
    public $response = '';

    public $dtxsid;

    public $error = null;

    public $isLoading = true;

    public function mount($dtxsid)
    {
        return $this->dtxsid = $dtxsid;
    }

    public function init()
    {
        try {
            $this->isLoading = true;
            $this->error = null;
            $response = [];

            foreach ($this->dtxsid as $dtx) {
                try {
                    $h = Http::withHeaders([
                        'accept' => 'application/json',
                        'x-api-key' => '30348b9a-9119-418e-85eb-7f7bbf4606c8',
                    ])->get('https://api-ccte.epa.gov/chemical/detail/search/by-dtxsid/'.$dtx);

                    if ($h->successful()) {
                        $response[$dtx] = collect(json_decode($h->getBody(), true))->mapWithKeys(function ($value, $key) {
                            return [$this->remapComptoxToNorman()[$key] ?? null => $value];
                        });
                    } else {
                        throw new Exception('HTTP request failed with status: '.$h->status());
                    }
                } catch (Exception $e) {
                    $this->error = 'Reading from external source has failed: '.$e->getMessage();
                    $this->isLoading = false;

                    return;
                }
            }

            $this->response = $response;
            $this->isLoading = false;

        } catch (Exception $e) {
            $this->error = 'Reading from external source has failed: '.$e->getMessage();
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.susdat.duplicate-load-comptox', [
            'response' => $this->response,
            'dtxsid_out' => $this->dtxsid,
            'columns' => $this->remapComptoxToNorman(),
            'error' => $this->error,
            'isLoading' => $this->isLoading,
        ]);
    }

    public function remapComptoxToNorman()
    {
        return [
            'preferredName' => 'name',
            'casrn' => 'cas_number',
            'smiles' => 'smiles',
            'inchikey' => 'stdinchikey',
            'dtxsid' => 'dtxid',
            'pubchemCid' => 'pubchem_cid',
            'molFormula' => 'molecular_formula',
            'monoisotopicMass' => 'mass_iso',
        ];
    }
}
