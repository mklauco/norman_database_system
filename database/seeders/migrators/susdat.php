<?php

namespace Database\Seeders\Migrators;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\MariaDB\Susdat as OldData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class susdat extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('substances')->delete();
        $count = OldData::count();
        $batchSize = 1000;
        $batches = ceil($count / $batchSize);
        for ($i = 0; $i < $batches; $i++) {
            echo "Processing batch " . ($i + 1) . " of " . $batches . PHP_EOL;
            $batch = OldData::where('sus_id', '>', $i * $batchSize)->where('sus_id', '<=', ($i + 1) * $batchSize)->get();        
            $p = [];
            foreach($batch as $item) {
                $metaDataKeys = [
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
                ];
                $p[] = [
                    'id' => (int)ltrim($item->sus_id, '0'),
                    'code' => $item->sus_id,
                    'metadata' => json_encode($item->only($metaDataKeys)),
                ];
            }
            DB::table('substances')->insert($p);
        }
        

    }
}

// php artisan make:seeder Migrators/susdat 
// php artisan make:model MariaDB/Susdat 
// php artisan db:seed --class=Database\Seeders\migrators\susdat

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