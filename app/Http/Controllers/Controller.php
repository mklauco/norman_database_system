<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

abstract class Controller extends ListGeneratorController
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

    public function convertToAscii($string) {
        $chars = [
            'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
            'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
            'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
            'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
            'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'þ'=>'b',
            'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', 'Ā'=>'A', 'ā'=>'a', 'Ă'=>'A', 'ă'=>'a', 'Ą'=>'A', 'ą'=>'a', 'Ć'=>'C',
            'ć'=>'c', 'Ĉ'=>'C', 'ĉ'=>'c', 'Ċ'=>'C', 'ċ'=>'c', 'Č'=>'C', 'č'=>'c', 'Ď'=>'D', 'ď'=>'d', 'Đ'=>'D',
            'đ'=>'d', 'Ē'=>'E', 'ē'=>'e', 'Ĕ'=>'E', 'ĕ'=>'e', 'Ė'=>'E', 'ė'=>'e', 'Ę'=>'E', 'ę'=>'e', 'Ě'=>'E',
            'ě'=>'e', 'Ĝ'=>'G', 'ĝ'=>'g', 'Ğ'=>'G', 'ğ'=>'g', 'Ġ'=>'G', 'ġ'=>'g', 'Ģ'=>'G', 'ģ'=>'g', 'Ĥ'=>'H',
            'ĥ'=>'h', 'Ħ'=>'H', 'ħ'=>'h', 'Ĩ'=>'I', 'ĩ'=>'i', 'Ī'=>'I', 'ī'=>'i', 'Ĭ'=>'I', 'ĭ'=>'i', 'Į'=>'I',
            'į'=>'i', 'İ'=>'I', 'ı'=>'i', 'Ĳ'=>'IJ', 'ĳ'=>'ij', 'Ĵ'=>'J', 'ĵ'=>'j', 'Ķ'=>'K', 'ķ'=>'k', 'ĸ'=>'k',
            'Ĺ'=>'L', 'ĺ'=>'l', 'Ļ'=>'L', 'ļ'=>'l', 'Ľ'=>'L', 'ľ'=>'l', 'Ŀ'=>'L', 'ŀ'=>'l', 'Ł'=>'L', 'ł'=>'l',
            'Ń'=>'N', 'ń'=>'n', 'Ņ'=>'N', 'ņ'=>'n', 'Ň'=>'N', 'ň'=>'n', 'ŉ'=>'n', 'Ŋ'=>'N', 'ŋ'=>'n', 'Ō'=>'O',
            'ō'=>'o', 'Ŏ'=>'O', 'ŏ'=>'o', 'Ő'=>'O', 'ő'=>'o', 'Œ'=>'Oe', 'œ'=>'oe', 'Ŕ'=>'R', 'ŕ'=>'r', 'Ŗ'=>'R',
            'ŗ'=>'r', 'Ř'=>'R', 'ř'=>'r', 'Ś'=>'S', 'ś'=>'s', 'Ŝ'=>'S', 'ŝ'=>'s', 'Ş'=>'S', 'ş'=>'s', 'Š'=>'S',
            'š'=>'s', 'Ţ'=>'T', 'ţ'=>'t', 'Ť'=>'T', 'ť'=>'t', 'Ŧ'=>'T', 'ŧ'=>'t', 'Ũ'=>'U', 'ũ'=>'u', 'Ū'=>'U',
            'ū'=>'u', 'Ŭ'=>'U', 'ŭ'=>'u', 'Ů'=>'U', 'ů'=>'u', 'Ű'=>'U', 'ű'=>'u', 'Ų'=>'U', 'ų'=>'u', 'Ŵ'=>'W',
            'ŵ'=>'w', 'Ŷ'=>'Y', 'ŷ'=>'y', 'Ÿ'=>'Y', 'Ź'=>'Z', 'ź'=>'z', 'Ż'=>'Z', 'ż'=>'z', 'Ž'=>'Z', 'ž'=>'z',
            'ſ'=>'s', 'ƒ'=>'f', 'Ơ'=>'O', 'ơ'=>'o', 'Ư'=>'U', 'ư'=>'u', 'Ǎ'=>'A', 'ǎ'=>'a', 'Ǐ'=>'I', 'ǐ'=>'i',
            'Ǒ'=>'O', 'ǒ'=>'o', 'Ǔ'=>'U', 'ǔ'=>'u', 'Ǖ'=>'U', 'ǖ'=>'u', 'Ǘ'=>'U', 'ǘ'=>'u', 'Ǚ'=>'U', 'ǚ'=>'u',
            'Ǜ'=>'U', 'ǜ'=>'u', 'Ǻ'=>'A', 'ǻ'=>'a', 'Ǽ'=>'AE', 'ǽ'=>'ae', 'Ǿ'=>'O', 'ǿ'=>'o'
        ];
    
        return strtr($string, $chars);
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
    
    public function getPubchemData(array $pubchemIds){
        // foreach($pubchemIds as $pcid){
            $client = new \GuzzleHttp\Client();
            $url = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'. implode(",", $pubchemIds) . '/property/MolecularFormula,MolecularWeight,InChIKey,IUPACName,Title,CanonicalSMILES,IsomericSMILES,InChI,MonoisotopicMass/JSON';
            $response = $client->request('GET', $url);
            $jsonData = json_decode($response->getBody())->PropertyTable->Properties;
            $pubchem = [];
            foreach($jsonData as $key => $value){
                $pubchem[$value->CID] = collect($value)->mapWithKeys(function($value, $key){
                    return [$this->remapPubchemToNorman()[$key] ?? null => $value];
                });
            }

            return $pubchem;
            
            // $CID = $data->CID;
            // $MolecularFormula = $data->MolecularFormula;
            // $MolecularWeight = $data->MolecularWeight;
            // $CanonicalSMILES = $data->CanonicalSMILES;
            // $IsomericSMILES = $data->IsomericSMILES;
            // $InChI = $data->InChI;
            // $InChIKey = $data->InChIKey;
            // $IUPACName = $data->IUPACName;
            // $MonoisotopicMass = $data->MonoisotopicMass;
            // $Title = $data->Title;
            
            // $CASRNarray = array();
            // // $CASarray = getCas($pcid);
            
            // foreach($CASarray as $value)
            // $CASRNarray[] = 'CAS_RN: ' . $value;
            
            // $DTXSIDarray = getDTXSID($pcid);
        // }
        
    }

    public function remapPubchemToNorman(){
        return [
            'Title'             => 'name',
            // 'casrn'               => 'cas_number',
            'CanonicalSMILES'   => 'smiles',
            'InChIKey'          => 'stdinchikey',
            'CID'               => 'pubchem_cid',
            'MolecularFormula'  => 'molecular_formula',
            'MonoisotopicMass'  => 'mass_iso',
        ];
    }

    
}
    