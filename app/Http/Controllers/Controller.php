<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

abstract class Controller
{
    //
    
    public function orderByList($id = null)
    {
        $list = [
            0 => 'asc',
            1 => 'desc',
        ];
        if (is_null($id)) {
            return $list;
        } else {
            return $list[$id];
        }
    }
    
    public function getComptoxData(array $dtxsid){
        // DATABAZA COMPTOX USEPA
        
        // URL pre požiadavku
        $url = "https://api-ccte.epa.gov/chemical/detail/search/by-dtxsid/";
        
        // Hlavičky pre požiadavku
        $headers = array(
            "accept: application/json",
            "content-type: application/json",
            "x-api-key: 30348b9a-9119-418e-85eb-7f7bbf4606c8"
        );
        
        // Dáta pre odoslanie (vo formáte JSON)
        $data = json_encode($dtxsid); // $dtxsid je pole s DTXSID hodnotami [DTXSID60144515, DTXSID9045265, ...]
        
        // Inicializácia cURL
        $ch = curl_init();
        
        // Nastavenie možností cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        
        // Vykonanie požiadavku
        $response = curl_exec($ch);
        
        // NOT WORKING - HTTP CLIENT
        // $h = Http::withHeaders(['accept'          => 'application/json','content-type'    => 'application/json','x-api-key'       => '30348b9a-9119-418e-85eb-7f7bbf4606c8'])->post('https://api-ccte.epa.gov/chemical/detail/search/by-dtxsid/["DTXSID7025219","DTXSID1058711","DTXSID3029811"]');        
        curl_close($ch);
        
        
        if($response)
        {
            $data = json_decode($response);
            $comptox = [];
            
            foreach($data as $value){
                $comptox[$value->dtxsid] = collect($value)->mapWithKeys(function($value, $key){
                    return [$this->remapComptoxToNorman()[$key] ?? null => $value];
                });
            }
            
            return $comptox;
        } else {
            return false;
        }
        
    }
    
    public function remapComptoxToNorman(){
        return [
            'preferredName'       => 'name',
            'casrn'               => 'cas_number',
            'smiles'              => 'smiles',
            'inchikey'            => 'stdinchikey',
            'dtxsid'              => 'dtxid',
            'pubchemCid'          => 'pubchem_cid',
            'molFormula'          => 'molecular_formula',
            'monoisotopicMass'    => 'mass_iso',
        ];
    }
}
