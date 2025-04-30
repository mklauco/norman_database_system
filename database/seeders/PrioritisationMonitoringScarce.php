<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class PrioritisationMonitoringScarce extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Starting monitoring_scarce data seeding...');
        $target_table_name = 'prioritisation_monitoring_scarce';
        $now = Carbon::now();
        $startTime = microtime(true);
        $path = base_path() . '/database/seeders/seeds/prioritisation_tables/monitoring_scarce_export.csv';
        
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
                        'id' => $r['pri_nr'],
                        'pri_nr' => $r['pri_nr'] ?? null,
                        'pri_use_for_priority_list' => $r['pri_use_for_priority_list'] ?? '',
                        'pri_substance' => $r['pri_substance'] ?? '',
                        'pri_cas_no' => $r['pri_cas_no'] ?? '',
                        'pri_no_sites_new' => $r['pri_no_sites_new'] ?? 0,
                        'pri_no_sites_where_mecsite_pnec_new' => $r['pri_no_sites_where_mecsite_pnec_new'] ?? 0,
                        'pri_mec95_new' => $r['pri_mec95_new'] ?? 0.00,
                        'pri_mecsite_max_new' => $r['pri_mecsite_max_new'] ?? 0.00,
                        'pri_loq_min' => $r['pri_loq_min'] ?? 0.00,
                        'pri_cat' => $r['pri_cat'] ?? 0,
                        'pri_lowest_pnec' => $r['pri_lowest_pnec'] ?? 0.00,
                        'pri_pnec_type' => $r['pri_pnec_type'] ?? '',
                        'pri_reference_pnec' => $r['pri_reference_pnec'] ?? '',
                        'pri_max_exceedance' => $r['pri_max_exceedance'] ?? 0.00,
                        'pri_extent_of_exceedence' => $r['pri_extent_of_exceedence'] ?? 0.00,
                        'pri_score_eoe' => $r['pri_score_eoe'] ?? 0.00,
                        'pri_score_foe' => $r['pri_score_foe'] ?? 0.00,
                        'pri_score_total' => $r['pri_score_total'] ?? 0.00,
                        'pri_loq_exceedance' => $r['pri_loq_exceedance'] ?? 0.00,
                        'pri_substance_new' => $r['pri_substance_new'] ?? '',
                        'pri_no_of_sites_mecsite_pnec_new' => $r['pri_no_of_sites_mecsite_pnec_new'] ?? 0,
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
// php artisan db:seed --class="PrioritisationMonitoringScarce"