<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class BioassaysMonitoringDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $target_table_name = 'bioassay_monitor_sample_data';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/bioassay_tables/bioassay_monitor_sample_data.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];

        foreach($rows as $r) {
            $p[] = [
                'id' => $r['m_sd_id'],
                'm_ds_id' => $r['m_ds_id'] ?? 0,
                'auxiliary_sample_identification' => $r['m_sd_auxiliary_sample_identification'] ?? '',
                'm_country_id' => $r['m_country_id'] ?? 0,
                'country_other' => $r['m_sd_country_other'] ?? '',
                'station_name' => $r['m_sd_station_name'] ?? '',
                'station_national_code' => $r['m_sd_station_national_code'] ?? '',
                'station_ec_code_wise' => $r['m_sd_station_ec_code_wise'] ?? '',
                'station_ec_code_other' => $r['m_sd_station_ec_code_other'] ?? '',
                'other_station_code' => $r['m_sd_other_station_code'] ?? '',
                'longitude' => $r['m_sd_longitude'] ?? '',
                'latitude' => $r['m_sd_latitude'] ?? '',
                'm_precision_coordinates_id' => $r['m_precision_coordinates_id'] ?? 0,
                'altitude' => $r['m_sd_altitude'] ?? '',
                'm_sample_matrix_id' => $r['m_sample_matrix_id'] ?? 0,
                'sample_matrix_other' => $r['m_sd_sample_matrix_other'] ?? '',
                'm_type_sampling_id' => $r['m_type_sampling_id'] ?? 0,
                'm_sampling_technique_id' => $r['m_sampling_technique_id'] ?? 0,
                'sampling_technique_other' => $r['m_sd_sampling_technique_other'] ?? '',
                'sampling_start_day' => $r['m_sd_sampling_start_day'] ?? 0,
                'sampling_start_month' => $r['m_sd_sampling_start_month'] ?? 0,
                'sampling_start_year' => $r['m_sd_sampling_start_year'] ?? 0,
                'sampling_start_hour' => $r['m_sd_sampling_start_hour'] ?? 0,
                'sampling_start_minute' => $r['m_sd_sampling_start_minute'] ?? 0,
                'sampling_duration_days' => $r['m_sd_sampling_duration_days'] ?? 0,
                'sampling_duration_hours' => $r['m_sd_sampling_duration_hours'] ?? 0,
                'm_fraction_id' => $r['m_fraction_id'] ?? 0,
                'fraction_other' => $r['m_sd_fraction_other'] ?? '',
                'name' => $r['m_sd_name'] ?? '',
                'river_basin_name' => $r['m_sd_river_basin_name'] ?? '',
                'river_km' => $r['m_sd_river_km'] ?? '',
                'm_proxy_pressures_id' => $r['m_proxy_pressures_id'] ?? 0,
                'proxy_pressures_other' => $r['m_sd_proxy_pressures_other'] ?? '',
                'sampling_depth' => $r['m_sd_sampling_depth'] ?? '',
                'surface_area' => $r['m_sd_surface_area'] ?? '',
                'salinity_mean' => $r['m_sd_salinity_mean'] ?? '',
                'spm_concentration' => $r['m_sd_spm_concentration'] ?? '',
                'ph' => $r['m_sd_ph'] ?? '',
                'temperature' => $r['m_sd_temperature'] ?? '',
                'dissolved_organic_carbon' => $r['m_sd_dissolved_organic_carbon'] ?? '',
                'conductivity' => $r['m_sd_conductivity'] ?? '',
                'guideline' => $r['m_sd_guideline'] ?? '',
                'reference' => $r['m_sd_reference'] ?? '',
                'created_at' => $now,
                'updated_at' => $now,
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
    }

}


// php artisan db:seed --class="BioassaysMonitoringDataSeeder"
