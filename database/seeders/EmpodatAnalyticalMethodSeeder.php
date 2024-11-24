<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EmpodatAnalyticalMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'empodat_analytical_methods';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/dct_analytical_method.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $p[] = [
                'id'                                    => $r['id_method'],
                'lod'                                   => $r['am_lod'] ? $r['am_lod'] : null,
                'loq'                                   => $r['am_loq'] ? $r['am_loq'] : null,
                'uncertainty_loq'                       => $r['am_uncertainty_loq'] ? $r['am_uncertainty_loq'] : null,
                'coverage_factor_id'                    => $r['dcf_id'] ? $r['dcf_id'] : null,
                'sample_preparation_method_id'          => $r['dpm_id'] ? $r['dpm_id'] : null,
                'sample_preparation_method_other'       => $r['dpm_other'] ? $r['dpm_id'] : null,
                'analytical_method_id'                  => $r['dam_id'] ? $r['dam_id'] : null,
                'analytical_method_other'               => $r['dam_other'] ? $r['dam_other'] : null,
                'standardised_method_id'                => $r['dsm_id'] ? $r['dsm_id'] : null,
                'standardised_method_other'             => $r['dsm_other'] ? $r['dsm_other'] : null,
                'standardised_method_number'            => $r['am_number'] ? $r['am_number'] : null,
                'validated_method_id'                   => $r['dp_id'] ? $r['dp_id'] : null,
                'corrected_recovery_id'                 => $r['am_corrected_recovery'] ? $r['am_corrected_recovery'] : null,
                'field_blank_id'                        => $r['am_field_blank'] ? $r['am_field_blank'] : null,
                'iso_id'                                => $r['am_iso'] ? $r['am_iso'] : null,
                'given_analyte_id'                      => $r['am_given_analyte'] ? $r['am_given_analyte'] : null,
                'laboratory_participate_id'             => $r['am_laboratory_participate'] ? $r['am_laboratory_participate'] : null,
                'summary_performance_id'                => $r['dsp_id'] ? $r['dsp_id'] : null,
                'control_charts_id'                     => $r['am_control_charts'] ? $r['am_control_charts'] : null,
                'internal_standards_id'                 => $r['am_internal_standards'] ? $r['am_internal_standards'] : null,
                'authority_id'                          => $r['am_authority'] ? $r['am_authority'] : null,
                'rating'                                => $r['rating'] ? $r['rating'] : null,
                'remark'                                => $r['am_remark'] ? $r['am_remark'] : null,
                'sampling_method_id'                    => $r['dsmo_id'] ? $r['dsmo_id'] : null,
                'sampling_collection_device_id'         => $r['dscd_id'] ? $r['dscd_id'] : null,
                'foa'                                   => $r['am_foa'] ? $r['am_foa'] : null,  
                'created_at'                            => $now,
                'updated_at'                            => $now,
            ];
        }

        $chunkSize = 1000;
        $chunks = array_chunk($p, $chunkSize);
        $k = 0;
        $count = ceil(count($p) / $chunkSize) - 1;
        foreach($chunks as $c){
            echo ($k++)."/".$count."; \n";
            DB::table($target_table_name)->insert($c);
        }
        $this->command->info("  EmpodatAnalyticalMethodSeeder completed. ");
    }
}
