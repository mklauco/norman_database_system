<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class EcotoxPNEC3Seeder extends Seeder
{
  protected $table_prefix = 'ecotox_';
  
  /**
  * Run the database seeds.
  *
  * @return void
  */
  public function run(): void
  {
    $this->command->info('Starting Ecotox PNEC3 seeding...');
    $target_table_name = $this->table_prefix.'pnec3';
    $now = Carbon::now();
    $startTime = microtime(true);
    $path = base_path() . '/database/seeders/seeds/'.$this->table_prefix.'tables/ecotox_pnec3.csv';
    
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
        
        // Helper function for date fields
        $safeDate = function($value, $default = null) {
            if ($value === '' || $value === null) {
                return $default;
            }
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return $default;
            }
        };
        
        $records[] = [
            'norman_pnec_id' => $r['norman_pnec_id'] ?? null,
            'norman_dataset_id' => $r['norman_dataset_id'] ?? null,
            'data_source_name' => $r['data_source_name'] ?? null,
            'data_source_link' => $r['data_source_link'] ?? null,
            'data_source_id' => $r['data_source_id'] ?? null,
            'study_title' => $r['study_title'] ?? null,
            'authors' => $r['authors'] ?? null,
            'year' => $r['year'] ?? null,
            'bibliographic_source' => $r['bibliographic_source'] ?? null,
            'dossier_available' => $r['dossier_available'] ?? null,
            'sus_id' => $safeInt($r['sus_id'] ?? null),
            'cas' => $r['cas'] ?? null,
            'substance_name' => $r['substance_name'] ?? null,
            'country_or_region' => $r['country_or_region'] ?? null,
            'institution' => $r['institution'] ?? null,
            'matrix_habitat' => $r['matrix_habitat'] ?? null,
            'legal_status' => $r['legal_status'] ?? null,
            'protected_asset' => $r['protected_asset'] ?? null,
            'pnec_type' => $r['pnec_type'] ?? null,
            'pnec_type_country' => $r['pnec_type_country'] ?? null,
            'monitoring_frequency' => $r['monitoring_frequency'] ?? null,
            'concentration_specification' => $r['concentration_specification'] ?? null,
            'taxonomic_group' => $r['taxonomic_group'] ?? null,
            'scientific_name' => $r['scientific_name'] ?? null,
            'endpoint' => $r['endpoint'] ?? null,
            'effect_measurement' => $r['effect_measurement'] ?? null,
            'duration' => $r['duration'] ?? null,
            'exposure_regime' => $r['exposure_regime'] ?? null,
            'measured_or_nominal' => $r['measured_or_nominal'] ?? null,
            'test_item' => $r['test_item'] ?? null,
            'purity' => $r['purity'] ?? null,
            'AF' => $r['AF'] ?? null,
            'justification' => $r['justification'] ?? null,
            'derivation_method' => $r['derivation_method'] ?? null,
            'value' => $r['value'] ?? null,
            'ecotox_id' => $r['ecotox_id'] ?? null,
            'remarks' => $r['remarks'] ?? null,
            'reliability_study' => $safeInt($r['reliability_study'] ?? null),
            'reliability_score' => $r['reliability_score'] ?? null,
            'institution_study' => $r['institution_study'] ?? null,
            'vote' => $r['vote'] ?? null,
            'regulatory_context' => $r['regulatory_context'] ?? null,
            'concentration_qualifier' => $r['concentration_qualifier'] ?? null,
            'concentration_value' => $r['concentration_value'] ?? null,
            'link_directive' => $r['link_directive'] ?? null,
            'date' => $safeDate($r['date'] ?? null),
            'use_study' => $r['use_study'] ?? null,
            'editor' => $safeInt($r['editor'] ?? null),
            'color_tx' => $safeInt($r['color_tx'] ?? null),
            'publication_year' => $safeInt($r['publication_year'] ?? null),
            'pnec_quality_class' => $r['pnec_quality_class'] ?? null,
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
    
    $this->command->info('Ecotox PNEC3 seeding completed!');
  }
}

// php artisan db:seed --class=EcotoxPNEC3Seeder