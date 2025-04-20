<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class IndoorMainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Starting indoor main data seeding...');
        $target_table_name = 'indoor_main';
        $now = Carbon::now();
        $startTime = microtime(true);
        $path = base_path() . '/database/seeders/seeds/indoor_tables/dct_analysis.csv';
        
        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();
        
        // Use lower memory usage options for SimpleExcelReader
        $reader = SimpleExcelReader::create($path)
            ->useDelimiter(',')
            ->headersToSnakeCase(false);
        
        // Use lazy collection to process the CSV file in chunks without loading it all
        $chunkSize = 1000; // Process in small chunks to conserve memory
        $reader->getRows()
            ->chunk($chunkSize)
            ->each(function ($rows, $key) use ($target_table_name, $now, $startTime) {
                $chunkStartTime = microtime(true);
                $records = [];
                foreach ($rows as $r) {
                    $records[] = [
                        'id' => $r['id'] ?? null,
                        'sus_id' => $r['sus_id'] ?? 0,
                        'country' => $r['country'] ?? '',
                        'country_other' => $r['country_other'] ?? '',
                        'station_name' => $r['station_name'] ?? '',
                        'national_name' => $r['national_name'] ?? '',
                        'short_sample_code' => $r['short_sample_code'] ?? '',
                        'sample_code' => $r['sample_code'] ?? '',
                        'provider_code' => $r['provider_code'] ?? '',
                        'code_ec' => $r['code_ec'] ?? '',
                        'code_other' => $r['code_other'] ?? '',
                        'east_west' => $r['east_west'] ?? '',
                        'longitude_d' => $r['longitude_d'] ?? '',
                        'longitude_m' => $r['longitude_m'] ?? '',
                        'longitude_s' => $r['longitude_s'] ?? '',
                        'longitude_decimal' => $r['longitude_decimal'] ?? '',
                        'north_south' => $r['north_south'] ?? '',
                        'latitude_d' => $r['latitude_d'] ?? '',
                        'latitude_m' => $r['latitude_m'] ?? '',
                        'latitude_s' => $r['latitude_s'] ?? '',
                        'latitude_decimal' => $r['latitude_decimal'] ?? '',
                        'dpc_id' => $r['dpc_id'] ?? 0,
                        'altitude' => $r['altitude'] ?? '',
                        'matrix_id' => $r['matrix_id'] ?? 0,
                        'matrix_other' => $r['matrix_other'] ?? '',
                        'dcot_id' => $r['dcot_id'] ?? 0,
                        'dic_id' => $r['dic_id'] ?? 0,
                        'concentration_value' => $r['concentration_value'] ?? 0,
                        'concentration_unit' => $r['concentration_unit'] ?? '',
                        'estimated_age' => $r['estimated_age'] ?? 0,
                        'sampling_date_y' => $r['sampling_date_y'] ?? null,
                        'sampling_date_m' => $r['sampling_date_m'] ?? null,
                        'sampling_date_d' => $r['sampling_date_d'] ?? null,
                        'sampling_date_t' => $r['sampling_date_t'] ?? null,
                        'sampling_duration' => $r['sampling_duration'] ?? '',
                        'dtoe_id' => $r['dtoe_id'] ?? 0,
                        'dcoe_id' => $r['dcoe_id'] ?? 0,
                        'dcoe_other' => $r['dcoe_other'] ?? '',
                        'id_method' => $r['id_method'] ?? 0,
                        'id_data' => $r['id_data'] ?? 0,
                        'remark' => $r['remark'] ?? '',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                
                // Use insert instead of creating a separate array and then chunking it
                DB::table($target_table_name)->insert($records);
                
                $chunkEndTime = microtime(true);
                $chunkElapsedTime = round($chunkEndTime - $chunkStartTime, 2);
                $totalElapsedTime = round($chunkEndTime - $startTime, 2);
                
                $this->command->info("Processed chunk " . ($key + 1) . " with " . count($records) . " records. Chunk time: {$chunkElapsedTime}s, Total elapsed: {$totalElapsedTime}s");
            });
        
        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
        
        $this->command->info('Indoor main data seeding completed!');
    }
}
// php artisan db:seed --class="IndoorMainSeeder"