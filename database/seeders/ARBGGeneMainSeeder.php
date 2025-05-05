<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class ARBGGeneMainSeeder extends Seeder
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
    $target_table_name = $this->table_prefix.'gene_main';
    $now = Carbon::now();
    $startTime = microtime(true);
    $path = base_path() . '/database/seeders/seeds/'.$this->table_prefix.'tables/arg_analysis.csv';
    
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
            'gene_name' => $safeString($r['gene_name']),
            'gene_description' => $r['gene_description'] ?? null,
            'gene_family' => $r['gene_family'] ?? null,
            'associated_phenotype' => $r['associated_phenotype'] ?? null,
            'monogenic_phenotype' => $r['monogenic_phenotype'] ?? null,
            'forward_primer' => $r['forward_primer'] ?? null,
            'reverse_primer' => $r['reverse_primer'] ?? null,
            'dye_probe_based' => $r['dye_probe_based'] ?? null,
            'probe_sequence' => $r['probe_sequence'] ?? null,
            'plasmid_genome_standards' => $r['plasmid_genome_standards'] ?? null,
            'multi_drug_resistance_phenotype' => $r['multi_drug_resistance_phenotype'] ?? null,
            'genetic_marker' => $r['genetic_marker'] ?? null,
            'genetic_marker_specify' => $r['genetic_marker_specify'] ?? null,
            'common_bacterial_host' => $r['common_bacterial_host'] ?? null,
            'concentration_data_id' => $safeInt($r['concentration_data_id'], 0),
            'concentration_id' => $safeForeignKey($r['concentration_id']),
            'concentration_abundance_per_ml' => $safeString($r['concentration_abundance_per_ml']),
            'concentration_abundance_per_ng' => $safeString($r['concentration_abundance_per_ng']),
            'concentration_abundance' => $safeString($r['concentration_abundance']),
            'prevalence' => $safeString($r['prevalence']),
            'sampling_date_day' => $safeInt($r['sampling_date_day'], 0),
            'sampling_date_month' => $safeInt($r['sampling_date_month'], 0),
            'sampling_date_year' => $r['sampling_date_year'] ?? null,
            'sampling_date_hour' => $r['sampling_date_hour'] ?? null,
            'sampling_date_minute' => $r['sampling_date_minute'] ?? null,
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
    
    $this->command->info('ARBG Gene main seeding completed!');
  }
}
// php artisan db:seed --class=ARBGGeneMainSeeder