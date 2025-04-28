<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class PrioritisationModellingDanube extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Starting modelling_danube data seeding...');
        $target_table_name = 'prioritisation_modelling_danube';
        $now = Carbon::now();
        $startTime = microtime(true);
        $path = base_path() . '/database/seeders/seeds/prioritisation_tables/modelling_danube_export.csv';
        
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
                        'id' => $r['pri_id'],
                        'pri_id' => $r['pri_id'],
                        'pri_cas' => $r['pri_cas'] ?? '',
                        'pri_name' => $r['pri_name'] ?? '',
                        'pri_emissions' => $r['pri_emissions'] ?? '',
                        'pri_correct' => $r['pri_correct'] ?? '',
                        'pri_score1' => $r['pri_score1'] ?? 0.00,
                        'pri_score2' => $r['pri_score2'] ?? 0.00,
                        'pri_score3' => $r['pri_score3'] ?? 0.00,
                        'pri_score4' => $r['pri_score4'] ?? 0.00,
                        'pri_score5' => $r['pri_score5'] ?? 0.00,
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
// php artisan db:seed --class="PrioritisationModellingDanube"