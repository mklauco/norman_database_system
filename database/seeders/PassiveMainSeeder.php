<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class PassiveMainSeeder extends Seeder
{
    protected $table_prefix = 'passive_';
    
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Starting passive sampling main data seeding...');
        $target_table_name = $this->table_prefix.'sampling_main';
        $now = Carbon::now();
        $startTime = microtime(true);
        $path = base_path() . '/database/seeders/seeds/'.$this->table_prefix.'tables/dct_analysis.csv';
        
        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();
        
        // Use lower memory usage options for SimpleExcelReader
        $reader = SimpleExcelReader::create($path)
            ->useDelimiter(',')
            ->headersToSnakeCase(false);
        
        // Use lazy collection to process the CSV file in chunks without loading it all
        $chunkSize = 100; // Process in small chunks to conserve memory
        $reader->getRows()
            ->chunk($chunkSize)
            ->each(function ($rows, $key) use ($target_table_name, $now, $startTime) {
                $chunkStartTime = microtime(true);
                $records = [];
                foreach ($rows as $r) {
                    // Helper function to safely convert empty strings to null for integer fields
                    $safeInt = function($value, $default = null) {
                        if ($value === '' || $value === null) {
                            return $default;
                        }
                        return (int) $value;
                    };
                    
                    // Helper function for foreign keys - converts 0 to null to avoid FK constraint issues
                    $safeForeignKey = function($value) {
                        if ($value === '' || $value === null || $value === '0' || $value === 0) {
                            return null;
                        }
                        return (int) $value;
                    };
                    
                    // Helper function to safely convert empty strings to null for float fields
                    $safeFloat = function($value, $default = null) {
                        if ($value === '' || $value === null) {
                            return $default;
                        }
                        return (float) $value;
                    };
                    
                    // Helper function to safely convert empty strings to default string
                    $safeString = function($value, $default = '') {
                        if ($value === null) {
                            return $default;
                        }
                        return (string) $value;
                    };

                    // Helper function to safely convert date values
                    $safeDate = function($value, $default = null) {
                        if (empty($value) || $value === '0000-00-00') {
                            return $default;
                        }
                        try {
                            return date('Y-m-d', strtotime($value));
                        } catch (\Exception $e) {
                            return $default;
                        }
                    };
                    
                    $records[] = [
                        'sus_id' => $safeInt($r['sus_id'], 0),
                        'country_id' => $safeString($r['country_id']),
                        'country_other' => $r['country_other'] ?? null,
                        'station_name' => $r['station_name'] ?? null,
                        'short_sample_code' => $r['short_sample_code'] ?? null,
                        'sample_code' => $r['sample_code'] ?? null,
                        'provider_code' => $r['provider_code'] ?? null,
                        'national_code' => $r['national_code'] ?? null,
                        'code_ec_wise' => $r['code_ec_wise'] ?? null,
                        'code_ec_other' => $r['code_ec_other'] ?? null,
                        'code_other' => $r['code_other'] ?? null,
                        'specific_locations' => $r['specific_locations'] ?? null,
                        'longitude_decimal' => $r['longitude_decimal'] ?? null,
                        'latitude_decimal' => $r['latitude_decimal'] ?? null,
                        'dpc_id' => $safeForeignKey($r['dpc_id']),
                        'altitude' => $r['altitude'] ?? null,
                        'dpr_id' => $safeForeignKey($r['dpr_id']),
                        'dpr_other' => $r['dpr_other'] ?? null,
                        'ds_passive_sampling_stretch' => $r['ds_passive_sampling_stretch'] ?? null,
                        'ds_stretch_start_and_end' => $safeString($r['ds_stretch_start_and_end']),
                        'ds_longitude_start_point_decimal' => $r['ds_longitude_start_point_decimal'] ?? null,
                        'ds_latitude_start_point_decimal' => $r['ds_latitude_start_point_decimal'] ?? null,
                        'ds_longitude_end_point_decimal' => $r['ds_longitude_end_point_decimal'] ?? null,
                        'ds_latitude_end_point_decimal' => $r['ds_latitude_end_point_decimal'] ?? null,
                        'ds_dpc_id' => $safeForeignKey($r['ds_dpc_id']),
                        'ds_altitude' => $r['ds_altitude'] ?? null,
                        'ds_dpr_id' => $safeForeignKey($r['ds_dpr_id']),
                        'ds_dpr_other' => $r['ds_dpr_other'] ?? null,
                        'matrix_id' => $safeForeignKey($r['matrix_id']),
                        'matrix_other' => $safeString($r['matrix_other']),
                        'type_sampling_id' => $safeInt($r['type_sampling_id'], 0),
                        'type_sampling_other' => $safeString($r['type_sampling_other']),
                        'passive_sampler_id' => $safeForeignKey($r['passive_sampler_id']),
                        'passive_sampler_other' => $safeString($r['passive_sampler_other']),
                        'sampler_type_id' => $safeForeignKey($r['sampler_type_id']),
                        'sampler_type_other' => $r['sampler_type_other'] ?? null,
                        'sampler_mass' => $safeString($r['sampler_mass']),
                        'sampler_surface_area' => $safeString($r['sampler_surface_area']),
                        'date_sampling_start_day' => $safeInt($r['date_sampling_start_day']),
                        'date_sampling_start_month' => $safeInt($r['date_sampling_start_month']),
                        'date_sampling_start_year' => $r['date_sampling_start_year'] ?? null,
                        'exposure_time_days' => $safeString($r['exposure_time_days']),
                        'exposure_time_hours' => $safeString($r['exposure_time_hours']),
                        'date_of_analysis' => $safeDate($r['date_of_analysis'], null),
                        'time_of_analysis' => $r['time_of_analysis'] ?? null,
                        'name' => $r['name'] ?? null,
                        'basin_name_id' => $safeForeignKey($r['basin_name_id']),
                        'basin_name_other' => $r['basin_name_other'] ?? null,
                        'dts_id' => $safeInt($r['dts_id']),
                        'dts_other' => $r['dts_other'] ?? null,
                        'dtm_id' => $safeInt($r['dtm_id']),
                        'dtm_other' => $r['dtm_other'] ?? null,
                        'dic_id' => $safeForeignKey($r['dic_id']),
                        'concentration_value' => $safeFloat($r['concentration_value'], 0),
                        'unit' => $safeString($r['unit']),
                        'title_of_project' => $r['title_of_project'] ?? null,
                        'ph' => $r['ph'] ?? null,
                        'temperature' => $r['temperature'] ?? null,
                        'spm_conc' => $r['spm_conc'] ?? null,
                        'salinity' => $r['salinity'] ?? null,
                        'doc' => $r['doc'] ?? null,
                        'hardness' => $r['hardness'] ?? null,
                        'o2_1' => $r['o2_1'] ?? null,
                        'o2_2' => $r['o2_2'] ?? null,
                        'bod5' => $r['bod5'] ?? null,
                        'h2s' => $r['h2s'] ?? null,
                        'p_po4' => $r['p_po4'] ?? null,
                        'n_no2' => $r['n_no2'] ?? null,
                        'tss' => $r['tss'] ?? null,
                        'p_total' => $r['p_total'] ?? null,
                        'n_no3' => $r['n_no3'] ?? null,
                        'n_total' => $r['n_total'] ?? null,
                        'remark_1' => $r['remark_1'] ?? null,
                        'remark_2' => $r['remark_2'] ?? null,
                        'am_id' => $safeInt($r['am_id'], 0),
                        'org_id' => $safeInt($r['org_id'], 0),
                        'orig_compound' => $safeString($r['orig_compound']),
                        'orig_cas_no' => $safeString($r['orig_cas_no']),
                        'p_determinand_id' => $safeString($r['p_determinand_id']),
                        'p_a_exposure_time' => $safeString($r['p_a_exposure_time']),
                        'p_a_cruise_dates' => $safeString($r['p_a_cruise_dates']),
                        'p_a_river_km' => $safeString($r['p_a_river_km']),
                        'p_a_sampler_sheets_disks_nr' => $safeString($r['p_a_sampler_sheets_disks_nr']),
                        'p_a_sample_code' => $safeString($r['p_a_sample_code']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                
                // Use insert instead of creating a separate array and then chunking it
                if (!empty($records)) {
                    try {
                        DB::table($target_table_name)->insert($records);
                        
                        $chunkEndTime = microtime(true);
                        $chunkElapsedTime = round($chunkEndTime - $chunkStartTime, 2);
                        $totalElapsedTime = round($chunkEndTime - $startTime, 2);
                        
                        $this->command->info("Processed chunk " . ($key + 1) . " with " . count($records) . " records. Chunk time: {$chunkElapsedTime}s, Total elapsed: {$totalElapsedTime}s");
                    } catch (\Exception $e) {
                        $this->command->error("Error in chunk " . ($key + 1) . ": " . $e->getMessage());
                        // Optionally log the problematic records for debugging
                        // You may want to add more detailed error handling here
                    }
                }
            });
        
        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
        
        $this->command->info('Passive sampling main data seeding completed!');
    }
}
// php artisan db:seed --class=PassiveMainSeeder