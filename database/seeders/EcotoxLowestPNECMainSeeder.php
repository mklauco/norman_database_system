<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class EcotoxLowestPNECMainSeeder extends Seeder
{
  protected $table_prefix = 'ecotox_';
  
  /**
  * Run the database seeds.
  *
  * @return void
  */
  public function run(): void
  {
    $this->command->info('Starting Ecotox Lowest PNEC Main seeding...');
    $target_table_name = $this->table_prefix.'lowestpnec_main';
    $now = Carbon::now();
    $startTime = microtime(true);
    $path = base_path() . '/database/seeders/seeds/'.$this->table_prefix.'tables/lowestpnec.csv';
    
    // Temporarily disable foreign key checks
    Schema::disableForeignKeyConstraints();
    
    // Use lower memory usage options for SimpleExcelReader
    $reader = SimpleExcelReader::create($path)
    ->useDelimiter(',')
    ->headersToSnakeCase(false);
    
    // Debug: Output the headers from the CSV file
    $headers = null;
    try {
        $firstRow = $reader->getRows()->first();
        if ($firstRow) {
            $headers = array_keys($firstRow);
            $this->command->info('CSV headers: ' . implode(', ', $headers));
        }
    } catch (\Exception $e) {
        $this->command->error('Error reading CSV headers: ' . $e->getMessage());
    }
    
    // Use lazy collection to process the CSV file in chunks without loading it all
    $chunkSize = 1000; // Process in small chunks to conserve memory
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
        
        // Helper function to safely convert empty strings to null for numeric fields
        $safeNumeric = function($value, $default = null) {
            if ($value === '' || $value === null || !is_numeric($value)) {
                return $default;
            }
            return (float) $value;
        };
        
        // Helper function for date fields
        $safeDate = function($value, $default = null) {
            if ($value === '' || $value === null) {
                return $default;
            }
            try {
                return Carbon::parse($value);
            } catch (\Exception $e) {
                return $default;
            }
        };
        
        $records[] = [
            'lowest_id' => $safeInt($r['lowest_id'] ?? null),
            'lowest_matrix' => $r['lowest_matrix'] ?? null,
            'sus_id' => $safeInt($r['sus_id'] ?? null),
            'der_id' => $r['der_id'] ?? null,
            'norman_pnec_id' => $r['norman_pnec_id'] ?? null,
            'lowesta_id' => $r['lowesta_id'] ?? null,
            'lowest_pnec_type' => $r['lowest_pnec_type'] ?? null,
            'lowest_institution' => $r['lowest_institution'] ?? null,
            'lowest_test_endpoint' => $r['lowest_test_endpoint'] ?? null,
            'lowest_AF' => $safeInt($r['lowest_AF'] ?? null),
            'lowest_pnec_value' => $safeNumeric($r['lowest_pnec_value'] ?? null),
            'lowest_derivation_method' => $r['lowest_derivation_method'] ?? null,
            'lowest_editor' => $safeInt($r['lowest_editor'] ?? null),
            'lowest_active' => (isset($r['lowest_active']) && $r['lowest_active'] !== '') ? (bool)$r['lowest_active'] : null,
            'lowest_color' => (isset($r['lowest_color']) && $r['lowest_color'] !== '') ? (bool)$r['lowest_color'] : null,
            'lowest_year' => $safeDate($r['lowest_year'] ?? null),
            'lowest_pnec' => (isset($r['lowest_pnec']) && $r['lowest_pnec'] !== '') ? (bool)$r['lowest_pnec'] : null,
            'lowest_base_name' => $r['lowest_base_name'] ?? null,
            'lowest_base_id' => $r['lowest_base_id'] ?? null,
            'lowest_sum_vote' => $safeInt($r['lowest_sum_vote'] ?? null),
            'sus_id_origin' => $safeInt($r['sus_id_origin'] ?? null),
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
    
    $this->command->info('Ecotox Lowest PNEC Main seeding completed!');
  }
}

// php artisan db:seed --class=EcotoxLowestPNECMainSeeder