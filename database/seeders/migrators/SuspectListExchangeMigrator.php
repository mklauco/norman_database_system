<?php

namespace Database\Seeders\Migrators;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use App\Models\MariaDB\SLE as OldData;
use App\Models\SLE\SuspectListExchange;
use App\Models\SLE\SuspectListExchangeSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SuspectListExchangeMigrator extends Seeder
{
  /**
  * Run the database seeds.
  */
  public function run(): void
  {
    //
    // get existing ids
    echo 'Getting substance ids...' . PHP_EOL;
    $existingSubstanceids = Substance::pluck('id')->toArray();
    $existingSourceids = SuspectListExchangeSource::select('id', 'code')->get()->keyby('code')->toArray();
    $logFileNameSub = base_path() . '/database/seeders/seeds/suspect_list_exchanges.log';
    file_put_contents($logFileNameSub, '');
    SuspectListExchange::query()->delete();
    $count = OldData::count();
    $batchSize = 1500;
    $batches = ceil($count / $batchSize);
    $time_start = microtime(true);
    $metadata_general = [
      'preferred_name',
      'other',
      'status',
      'status_text',
      'new_sus_id',
    ]; 
    // $batches = 2;
    $now = Carbon::now();
    for ($i = 0; $i < $batches; $i++) {
      $time_start_for = microtime(true); 
      echo "Processing batch " . ($i + 1) . " of " . $batches;
      $batch = OldData::where('id', '>', $i * $batchSize)->where('id', '<=', ($i + 1) * $batchSize)->get();        
      $p = [];
      foreach($batch as $item) {
        try{
          $source_id = $existingSourceids['S'.$item->{'sle'}]['id'];
        } catch (\Exception $e) {
          echo "Error: ".$e->getMessage().PHP_EOL;
          continue;
        }
        $substance_id = (int)ltrim($item->susdat_id, '0');
        $substance_ok = in_array($substance_id, $existingSubstanceids);
        if ($substance_id == 0 || !$substance_ok){
          $message = "Skipping existing substance_id: ".$substance_id.' writting substance_id = NULL for id='.$item->{'id'}."\n";
          file_put_contents($logFileNameSub, $message, FILE_APPEND);
          $substance_id = null;
        }

        $p[] = [
          'id'                  => $item->{'id'},
          'source_id'           => $source_id,
          'substance_id'        => $substance_id,
          'name'                => $item->{'name'},
          'name_iupac'          => $item->{'iupac_name'},
          'cas_number'          => $item->{'cas'},
          'smiles'              => $item->{'smiles'},
          'stdinchi'            => $item->{'inchi'},
          'stdinchikey'         => $item->{'inchikey'},
          'pubchem_cid'         => $item->{'pubchem_cid'},
          'chemspider_id'       => $item->{'chemspider_id'},
          'dtxid'               => $item->{'dtxsid'},
          'molecular_formula'   => $item->{'molecular_formula'},
          'mass_iso'            => floatval(str_replace(',', '.', (string)$item->{'monoisotopic_mass'})),
          'molecular_weight'    => floatval(str_replace(',', '.', (string)$item->{'molecular_weight'})),
          'metadata_general'    => json_encode($item->only($metadata_general)),
          'added_by'            => 1,
          'created_at'          => $now,
          'updated_at'          => $now,
        ];
      }
      SuspectListExchange::insert($p);
      unset($p);
      unset($batch);
      $time_end_for = microtime(true);
      $execution_time = $time_end_for- $time_start_for;
      echo " | time taken: ".$execution_time." sec".PHP_EOL;
    }
    $time_end = microtime(true);
    $execution_time = $time_end - $time_start;
    echo 'Migrating Susdat took '.$execution_time.' sec'.PHP_EOL;
  }
  
  protected function checkTimeStamp($id, $t, $now)
  {
    if (is_null($t)) {
      return $now;
    } elseif ($t == '0000-00-00') {
      return $now;
    } elseif (Carbon::parse($t)->isValid()) {
      return $now;
    } else{
      return Carbon::parse($t)->toDateTimeString();
    }
    
  }
}

// php artisan make:model MariaDB/SLE 
// php artisan db:seed --class=Database\Seeders\migrators\SuspectListExchangeMigrator