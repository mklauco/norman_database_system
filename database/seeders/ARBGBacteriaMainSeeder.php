<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class ARBGBacteriaMainSeeder extends Seeder
{
  protected $table_prefix = 'arbg_';
  
  /**
  * Run the database seeds.
  *
  * @return void
  */
  public function run(): void
  {
    $this->command->info('Starting passive sampling main data seeding...');
    $target_table_name = $this->table_prefix.'bacteria_main';
    $now = Carbon::now();
    $startTime = microtime(true);
    $path = base_path() . '/database/seeders/seeds/'.$this->table_prefix.'tables/arb_analysis.csv';
    
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
      foreach ($rows as $r) {                    // Helper function to safely convert empty strings to null for integer fields
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
        
        // Helper function to safely convert empty strings to default string
        $safeString = function($value, $default = '') {
            if ($value === null) {
                return $default;
            }
            return (string) $value;
        };
        
        $records[] = [
            'sample_matrix_id' => $safeForeignKey($r['sample_matrix_id']),
            'sample_matrix_other' => $r['sample_matrix_other'] ?? null,
            'bacterial_group_id' => $safeForeignKey($r['bacterial_group_id']),
            'bacterial_group_other' => $r['bacterial_group_other'] ?? null,
            'concentration_data_id' => $safeForeignKey($r['concentration_data_id']),
            'ar_phenotype' => $r['ar_phenotype'] ?? null,
            'ar_phenotype_class' => $r['ar_phenotype_class'] ?? null,
            'abundance' => $safeString($r['abundance']),
            'value' => $safeString($r['value']),
            'sampling_date_day' => $safeInt($r['sampling_date_day'], 0),
            'sampling_date_month' => $safeInt($r['sampling_date_month'], 0),
            'sampling_date_year' => $r['sampling_date_year'] ?? null,
            'sampling_date_hour' => $r['sampling_date_hour'] ?? null,
            'sampling_date_minute' => $r['sampling_date_minute'] ?? null,
            'name_of_the_wider_area_of_sampling' => $r['name_of_the_wider_area_of_sampling'] ?? null,
            'river_basin_name' => $r['river_basin_name'] ?? null,
            'type_of_depth_sampling_id' => $safeInt($r['type_of_depth_sampling_id'], 0),
            'depth' => $safeString($r['depth']),
            'soil_type_id' => $safeForeignKey($r['soil_type_id']),
            'soil_texture_id' => $safeForeignKey($r['soil_texture_id']),
            'concentration_normalised' => $safeString($r['concentration_normalised']),
            'grain_size_distribution_id' => $safeForeignKey($r['grain_size_distribution_id']),
            'grain_size_distribution_other' => $r['grain_size_distribution_other'] ?? null,
            'dry_wet_ratio' => $safeString($r['dry_wet_ratio']),
            'ph' => $safeString($r['ph']),
            'total_organic_carbon' => $safeString($r['total_organic_carbon']),
            'method_id' => $safeInt($r['method_id']),
            'source_id' => $safeInt($r['source_id']),
            'coordinate_id' => $safeInt($r['coordinate_id']),
            'remark' => $r['remark'] ?? null,
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
// php artisan db:seed --class=ARBGBacteriaMainSeeder