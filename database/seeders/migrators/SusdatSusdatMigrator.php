<?php

namespace Database\Seeders\Migrators;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use App\Models\MariaDB\Susdat as OldData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SusdatSusdatMigrator extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('susdat_substances')->truncate();

$new_susdat_ids = DB::connection('norman-mariadb')
    ->table('a_substances')
    ->select('id', 'code')
    ->get()
    ->pluck('id', 'code')  // creates ['code' => id]
    ->toArray();

        $count = OldData::max('sus_id'); // max id instead of count.. lebo idecka su neusporiadane :(
        // dd($count);
        $batchSize = 2000;
        $batches = ceil(($count / $batchSize) / 1000)*1000;
        echo 'Max id: '.$count.' | '.'number of batches:'.$batches.PHP_EOL;
        $time_start = microtime(true);
        $metadata_synonyms = [
            'Synonyms ChemSpider',
            'Reliability of Synonyms ChemSpider',
        ];
        $metadata_ms_ready = [
            'MS_Ready_SMILES',
            'MS_Ready_StdInChI',
            'MS_Ready_StdInChIKey',
        ];
        $metadata_cas = [
            'CAS_RN Dashboard',
            'CAS_RN PubChem',
            'CAS_RN Cactus',
            'CAS_RN ChemSpider',
            'Reliability of CAS_ChemSpider',
        ];
        $metadata_general = [
            '[M+H]+',
            '[M-H]-',
            'Pred_RTI_Positive_ESI',
            'Uncertainty_RTI_pos',
            'Pred_RTI_Negative_ESI',
            'Uncertainty_RTI_neg',
            'Tetrahymena_pyriformis_toxicity',
            'IGC50_48_hr_ug/L',
            'Uncertainty_Tetrahymena_pyriformis_toxicity',
            'Daphnia_toxicity',
            'LC50_48_hr_ug/L',
            'Uncertainty_Daphnia_toxicity',
            'Algae_toxicity',
            'EC50_72_hr_ug/L',
            'Uncertainty_Algae_toxicity',
            'Pimephales_promelas_toxicity',
            'LC50_96_hr_ug/L',
            'Uncertainty_Pimephales_promelas_toxicity',
            'logKow_EPISuite',
            'Exp_logKow_EPISuite',
            'ChemSpider ID based on InChIKey_19032018',
            'alogp_ChemSpider',
            'xlogp_ChemSpider',
            'Lowest P-PNEC (QSAR) [ug/L]',
            'Species',
            'Uncertainty',
            'ExposureScore_Water_KEMI',
            'HazScore_EcoChronic_KEMI',
            'ValidationLevel_KEMI',
            'Prob_of_GC',
            'Prob_RPLC',
            'Pred_Chromatography',
            'Prob_of_both_Ionization_Source',
            'Prob_EI',
            'Prob_ESI',
            'Pred_Ionization_source',
            'Prob_both_ESI_mode',
            'Prob_plusESI',
            'Prob_minusESI',
            'Pred_ESI_mode',
            'Preferable_Platform_by_decision_Tree',
        ]; 
        // $batches = 2;
        $now = Carbon::now();
        for ($i = 0; $i <= $batches; $i++) {
            $time_start_for = microtime(true); 
            echo "Processing batch " . ($i + 1) . " of " . $batches;
            // $batch = OldData::select('sus_id', 'sus_name')->where('sus_id', '>', $i * $batchSize)->where('sus_id', '<=', ($i + 1) * $batchSize)->get();        
            $batch = OldData::where('sus_id', '>', $i * $batchSize)->where('sus_id', '<=', ($i + 1) * $batchSize)->get();        
            $p = [];
            foreach($batch as $item) {
                $p[] = [
                    // 'id'                => (int)ltrim($item->sus_id, '0'),
                    'id' => $new_susdat_ids[$item->sus_id],
                    'code'              => $item->sus_id,
                    'name'              => $item->sus_name,
                    'name_dashboard'    => $item->{'Name Dashboard'},
                    'name_chemspider'   => $item->{'Name ChemSpider'},
                    'name_iupac'        => $item->{'Name IUPAC'},
                    'cas_number'        => ltrim($item->{'sus_cas'}, 'CAS_RN: '),
                    'smiles'            => $item->{'SMILES'},
                    'smiles_dashboard'  => $item->{'SMILES Dashboard'},
                    'stdinchi'          => $item->{'StdInChI'},
                    'stdinchikey'       => $item->{'StdInChIKey'},
                    'pubchem_cid'       => $item->{'PubChem_CID'},
                    'chemspider_id'     => $item->{'ChemSpiderID'},
                    'dtxid'             => $item->{'DTXSID'},
                    'molecular_formula' => $item->{'Molecular_Formula'},
                    'mass_iso'          => is_numeric($item->{'Monoiso_Mass'}) ? $item->{'Monoiso_Mass'} : null,
                    'metadata_synonyms' => json_encode($item->only($metadata_synonyms)),
                    'metadata_cas'      => json_encode($item->only($metadata_cas)),
                    'metadata_ms_ready' => json_encode($item->only($metadata_ms_ready)),
                    'metadata_general'  => json_encode($item->only($metadata_general)),
                    // 'created_at'        => $now,
                    'created_at'        => $this->checkTimeStamp($item->sus_id, $item->{'c_at'}, $now), // TAKES TOO LONG TO PARSE
                    'updated_at'        => $now,
                    'added_by'          => null,
                ];
            }
            Substance::insert($p);
            unset($p);
            unset($batch);
            $time_end_for = microtime(true);
            $execution_time = $time_end_for- $time_start_for;
            echo " | time taken: ".$execution_time.' sec | ID range: ['.($i * $batchSize).', '.($i + 1) * $batchSize.']'.PHP_EOL;
        }
        $time_end = microtime(true);
        $execution_time = $time_end - $time_start;
        echo 'Migrating Susdat took '.$execution_time.' sec '.PHP_EOL;
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

// php artisan make:seeder Migrators/susdat 
// php artisan make:model MariaDB/Susdat 
// php artisan db:seed --class=Database/Seeders/migrators/SusdatSusdatMigrator

// sus_id
// sus_name
// Name Dashboard
// Name ChemSpider
// Name IUPAC
// Synonyms ChemSpider
// Reliability of Synonyms ChemSpider
// sus_cas
// CAS_RN Dashboard
// CAS_RN PubChem
// CAS_RN Cactus
// CAS_RN ChemSpider
// Reliability of CAS_ChemSpider
// Validation Level
// SMILES
// SMILES Dashboard
// StdInChI
// StdInChIKey
// MS_Ready_SMILES
// MS_Ready_StdInChI
// MS_Ready_StdInChIKey
// Source
// PubChem_CID
// ChemSpiderID
// DTXSID
// Molecular_Formula
// Monoiso_Mass
// [M+H]+
// [M-H]-
// Pred_RTI_Positive_ESI
// Uncertainty_RTI_pos
// Pred_RTI_Negative_ESI
// Uncertainty_RTI_neg
// Tetrahymena_pyriformis_toxicity
// IGC50_48_hr_ug/L
// Uncertainty_Tetrahymena_pyriformis_toxicity
// Daphnia_toxicity
// LC50_48_hr_ug/L
// Uncertainty_Daphnia_toxicity
// Algae_toxicity
// EC50_72_hr_ug/L
// Uncertainty_Algae_toxicity
// Pimephales_promelas_toxicity
// LC50_96_hr_ug/L
// Uncertainty_Pimephales_promelas_toxicity
// logKow_EPISuite
// Exp_logKow_EPISuite
// ChemSpider ID based on InChIKey_19032018
// alogp_ChemSpider
// xlogp_ChemSpider
// Lowest P-PNEC (QSAR) [ug/L]
// Species
// Uncertainty
// ExposureScore_Water_KEMI
// HazScore_EcoChronic_KEMI
// ValidationLevel_KEMI
// Prob_of_GC
// Prob_RPLC
// Pred_Chromatography
// Prob_of_both_Ionization_Source
// Prob_EI
// Prob_ESI
// Pred_Ionization_source
// Prob_both_ESI_mode
// Prob_plusESI
// Prob_minusESI
// Pred_ESI_mode
// Preferable_Platform_by_decision_Tree
// sus_synonyms
// sus_remark
// sus_name_20231115
// sle_id
// created_at