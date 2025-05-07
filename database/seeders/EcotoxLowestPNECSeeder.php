<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class EcotoxLowestPNECSeeder extends Seeder
{
  protected $table_prefix = 'ecotox_';
  
  /**
  * Run the database seeds.
  *
  * @return void
  */
  public function run(): void
  {
    $this->command->info('Starting Ecotox Lowest PNEC seeding...');
    $target_table_name = $this->table_prefix.'lowest_pnec';
    $now = Carbon::now();
    $startTime = microtime(true);
    $path = base_path() . '/database/seeders/seeds/'.$this->table_prefix.'tables/lowestpnec_v_round.csv';
    
    // Temporarily disable foreign key checks
    Schema::disableForeignKeyConstraints();
    
    // Use lower memory usage options for SimpleExcelReader
    $reader = SimpleExcelReader::create($path)
    ->useDelimiter(',')
    ->headersToSnakeCase(false);
    
    // Use lazy collection to process the CSV file in chunks without loading it all
    $chunkSize = 500; // Process in small chunks to conserve memory
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
        
        $records[] = [
            'sus_id' => $safeInt($r['sus_id']),
            'substance_id' => null,
            'lowest_pnec_value_1' => $safeNumeric($r['lowest_pnec_value_1']),
            'lowest_pnec_value_2' => $safeNumeric($r['lowest_pnec_value_2']),
            'lowest_pnec_value_3' => $safeNumeric($r['lowest_pnec_value_3']),
            'lowest_pnec_value_4' => $safeNumeric($r['lowest_pnec_value_4']),
            'lowest_pnec_value_5' => $safeNumeric($r['lowest_pnec_value_5']),
            'lowest_pnec_value_6' => $safeNumeric($r['lowest_pnec_value_6']),
            'lowest_pnec_value_7' => $safeNumeric($r['lowest_pnec_value_7']),
            'lowest_pnec_value_8' => $safeNumeric($r['lowest_pnec_value_8']),
            'lowest_exp_pred' => $safeInt($r['lowest_exp_pred'], 0),
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
    
    $this->command->info('Ecotox Lowest PNEC seeding completed!');
  }
}

// php artisan db:seed --class=EcotoxLowestPNECSeeder