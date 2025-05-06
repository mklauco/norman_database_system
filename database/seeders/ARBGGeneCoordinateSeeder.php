<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class ARBGGeneCoordinateSeeder extends Seeder
{
  protected $table_prefix = 'arbg_';
  
  /**
  * Run the database seeds.
  *
  * @return void
  */
  public function run(): void
  {
    $this->command->info('Starting ARBG Gene main seeding...');
    $target_table_name = $this->table_prefix.'gene_coordinates';
    $now = Carbon::now();
    $startTime = microtime(true);
    $path = base_path() . '/database/seeders/seeds/'.$this->table_prefix.'tables/arg_coordinate.csv';
    
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
            'id' => $r['coordinate_id'],
            'country_id' => $safeString($r['country_id'], ''),
            'country_other' => $r['country_other'] ?? null,
            'station_name' => $r['station_name'] ?? null,
            'national_code' => $r['national_code'] ?? null,
            'relevant_ec_code_wise' => $r['relevant_ec_code_wise'] ?? null,
            'relevant_ec_code_other' => $r['relevant_ec_code_other'] ?? null,
            'other_code' => $r['other_code'] ?? null,
            'east_west' => $r['east_west'] ?? null,
            'longitude1' => $r['longitude1'] ?? null,
            'longitude2' => $r['longitude2'] ?? null,
            'longitude3' => $r['longitude3'] ?? null,
            'longitude_decimal' => $r['longitude_decimal'] ?? null,
            'north_south' => $r['north_south'] ?? null,
            'latitude1' => $r['latitude1'] ?? null,
            'latitude2' => $r['latitude2'] ?? null,
            'latitude3' => $r['latitude3'] ?? null,
            'latitude_decimal' => $r['latitude_decimal'] ?? null,
            'precision_coordinates_id' => $safeInt($r['precision_coordinates_id'], 0),
            'altitude' => $r['altitude'] ?? null,
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
    
    $this->command->info('ARBG Gene main seeding completed!');
  }
}
// php artisan db:seed --class=ARBGGeneCoordinateSeeder