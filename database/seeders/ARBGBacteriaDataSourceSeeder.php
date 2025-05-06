<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class ARBGBacteriaDataSourceSeeder extends Seeder
{
  protected $table_prefix = 'arbg_';
  
  /**
  * Run the database seeds.
  *
  * @return void
  */
  public function run(): void
  {
    $this->command->info('Starting ARBG Bacteria data source main seeding...');
    $target_table_name = $this->table_prefix.'bacteria_data_source';
    $now = Carbon::now();
    $startTime = microtime(true);
    $path = base_path() . '/database/seeders/seeds/'.$this->table_prefix.'tables/arb_data_source.csv';
    
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
        
        // Helper function to safely convert empty strings to default string
        $safeString = function($value, $default = '') {
            if ($value === null) {
                return $default;
            }
            return (string) $value;
        };
        
        $records[] = [
            'id' => $safeInt($r['source_id']),
            'type_of_data_source_id' => $safeInt($r['type_of_data_source_id'], 0),
            'type_of_monitoring_id' => $safeInt($r['type_of_monitoring_id'], 0),
            'type_of_monitoring_other' => $r['type_of_monitoring_other'] ?? null,
            'title_of_project' => $r['title_of_project'] ?? null,
            'organisation' => $r['organisation'] ?? null,
            'e_mail' => $r['e_mail'] ?? null,
            'laboratory' => $r['laboratory'] ?? null,
            'laboratory_id' => $r['laboratory_id'] ?? null,
            'references_literature_1' => $r['references_literature_1'] ?? null,
            'references_literature_2' => $r['references_literature_2'] ?? null,
            'author' => $r['author'] ?? null,
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
    
    $this->command->info('ARBG Bacteria data source main seeding completed!');
  }
}
// php artisan db:seed --class=ARBGBacteriaDataSourceSeeder