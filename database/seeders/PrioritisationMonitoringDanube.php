<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class PrioritisationMonitoringDanube extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Starting monitoring_danube data seeding...');
        $target_table_name = 'prioritisation_monitoring_danube';
        $now = Carbon::now();
        $startTime = microtime(true);
        $path = base_path() . '/database/seeders/seeds/prioritisation_tables/monitoring_danube_export.csv';
        
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
                        'id' => $r['pri_no'],
                        'pri_no' => $r['pri_no'] ?? null,
                        'pri_substance' => $r['pri_substance'] ?? '',
                        'pri_cas_no' => $r['pri_cas_no'] ?? '',
                        'pri_position_prioritisation_2014' => $r['pri_position_prioritisation_2014'] ?? '',
                        'pri_category' => $r['pri_category'] ?? 0,
                        'pri_no_of_sites_where_mecsite_pnec' => $r['pri_no_of_sites_where_mecsite_pnec'] ?? 0,
                        'pri_mecsite_max' => $r['pri_mecsite_max'] ?? 0.00,
                        'pri_95th_mecsite' => $r['pri_95th_mecsite'] ?? 0.00,
                        'pri_lowest_pnec' => $r['pri_lowest_pnec'] ?? 0.00,
                        'pri_reference_key_study' => $r['pri_reference_key_study'] ?? '',
                        'pri_pnec_type' => $r['pri_pnec_type'] ?? '',
                        'pri_species' => $r['pri_species'] ?? '',
                        'pri_af' => $r['pri_af'] ?? 0,
                        'pri_extent_of_exceedence' => $r['pri_extent_of_exceedence'] ?? 0.00,
                        'pri_score_eoe' => $r['pri_score_eoe'] ?? 0.00,
                        'pri_score_foe' => $r['pri_score_foe'] ?? 0.00,
                        'pri_final_score' => $r['pri_final_score'] ?? 0.00,
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
        
        $this->command->info('Modelling Danube data seeding completed!');
    }
}
// php artisan db:seed --class="PrioritisationMonitoringDanube"