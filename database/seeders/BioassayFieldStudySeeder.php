<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class BioassayFieldStudySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting bioassay field study seeding...');

        $target_table_name = 'bioassay_field_studies';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/bioassay_tables/bioassay_monitor_bioassays_field_studies.csv';

        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();

        // Use lower memory usage options for SimpleExcelReader
        $reader = SimpleExcelReader::create($path)
            ->useDelimiter(',')
            ->headersToSnakeCase(false);

        // Use lazy collection to process the CSV file in chunks without loading it all
        $chunkSize = 50; // Increased from 100
        $reader->getRows()
            ->chunk($chunkSize)
            ->each(function ($rows, $key) use ($target_table_name, $now) {
                $records = [];

                foreach ($rows as $r) {
                    $records[] = [
                        'id' => $r['m_bfs_id'],
                        'm_sd_id' => $r['m_sd_id'] ?? 0,
                        'm_ds_id' => $r['m_ds_id'] ?? 0,
                        'm_auxiliary_sample_identification' => $r['m_auxiliary_sample_identification'] ?? '',
                        'm_bioassay_type_id' => $r['m_bioassay_type_id'] ?? 0,
                        'm_bioassay_type_other' => $r['m_bioassay_type_other'] ?? '',
                        'm_bioassay_name_id' => $r['m_bioassay_name_id'] ?? 0,
                        'bioassay_name_other' => $r['m_bfs_bioassay_name_other'] ?? '',
                        'm_adverse_outcome_id' => $r['m_adverse_outcome_id'] ?? 0,
                        'adverse_outcome_other' => $r['m_bfs_adverse_outcome_other'] ?? '',
                        'm_test_organism_id' => $r['m_test_organism_id'] ?? 0,
                        'test_organism_other' => $r['m_bfs_test_organism_other'] ?? '',
                        'm_cell_line_strain_id' => $r['m_cell_line_strain_id'] ?? 0,
                        'cell_line_strain_other' => $r['m_bfs_cell_line_strain_other'] ?? '',
                        'm_endpoint_id' => $r['m_endpoint_id'] ?? 0,
                        'endpoint_other' => $r['m_bfs_endpoint_other'] ?? '',
                        'm_effect_id' => $r['m_effect_id'] ?? 0,
                        'effect_other' => $r['m_bfs_effect_other'] ?? '',
                        'm_measured_parameter_id' => $r['m_measured_parameter_id'] ?? 0,
                        'measured_parameter_other' => $r['m_bfs_measured_parameter_other'] ?? '',
                        'exposure_duration' => $r['m_bfs_exposure_duration'] ?? '',
                        'effect_significantly' => $r['m_bfs_effect_significantly'] ?? '',
                        'maximal_tested_ref' => $r['m_bfs_maximal_tested_ref'] ?? '',
                        'dose_response_relationship' => $r['m_bfs_dose_response_relationship'] ?? '',
                        'm_main_determinand_id' => $r['m_main_determinand_id'] ?? 0,
                        'main_determinand_other' => $r['m_bfs_main_determinand_other'] ?? '',
                        'value_determinand' => $r['m_bfs_value_determinand'] ?? '',
                        'm_effect_equivalent_id' => $r['m_effect_equivalent_id'] ?? 0,
                        'effect_equivalent_other' => $r['m_bfs_effect_equivalent_other'] ?? '',
                        'value_effect_equivalent' => $r['m_bfs_value_effect_equivalent'] ?? '',
                        'm_standard_substance_id' => $r['m_standard_substance_id'] ?? 0,
                        'standard_substance_other' => $r['m_bfs_standard_substance_other'] ?? '',
                        'limit_of_detection' => $r['m_bfs_limit_of_detection'] ?? '',
                        'limit_of_quantification' => $r['m_bfs_limit_of_quantification'] ?? '',
                        'date_performed_month' => $r['m_bfs_date_performed_month'] ?? '',
                        'date_performed_year' => $r['m_bfs_date_performed_year'] ?? '',
                        'bioassay_performed' => $r['m_bfs_bioassay_performed'] ?? '',
                        'guideline' => $r['m_bfs_guideline'] ?? '',
                        'deviation' => $r['m_bfs_deviation'] ?? '',
                        'describe_deviation' => $r['m_bfs_describe_deviation'] ?? '',
                        'm_assay_format_id' => $r['m_assay_format_id'] ?? 0,
                        'assay_format_other' => $r['m_bfs_assay_format_other'] ?? '',
                        'm_solvent_id' => $r['m_solvent_id'] ?? 0,
                        'solvent_other' => $r['m_bfs_solvent_other'] ?? '',
                        'max_solvent_concentration' => $r['m_bfs_max_solvent_concentration'] ?? '',
                        'test_medium' => $r['m_bfs_test_medium'] ?? '',
                        'm_test_system_id' => $r['m_test_system_id'] ?? 0,
                        'test_system_other' => $r['m_bfs_test_system_other'] ?? '',
                        'no_organisms' => $r['m_bfs_no_organisms'] ?? '',
                        'age_organisms' => $r['m_bfs_age_organisms'] ?? '',
                        'm_life_stage_id' => $r['m_life_stage_id'] ?? 0,
                        'life_stage_other' => $r['m_bfs_life_stage_other'] ?? '',
                        'no_experiment_repetitions' => $r['m_bfs_no_experiment_repetitions'] ?? '',
                        'no_replicates_per_treatment' => $r['m_bfs_no_replicates_per_treatment'] ?? '',
                        'no_concentration_treatments' => $r['m_bfs_no_concentration_treatments'] ?? '',
                        'm_effect_level_id' => $r['m_effect_level_id'] ?? 0,
                        'effect_level_other' => $r['m_bfs_effect_level_other'] ?? '',
                        'cv_main_determinand' => $r['m_bfs_cv_main_determinand'] ?? '',
                        'average_cv_resopnse' => $r['m_bfs_average_cv_resopnse'] ?? '',
                        'statistical_assessment' => $r['m_bfs_statistical_assessment'] ?? '',
                        'significance_level' => $r['m_bfs_significance_level'] ?? '',
                        'statistical_calculation' => $r['m_bfs_statistical_calculation'] ?? '',
                        'positive_control_tested' => $r['m_bfs_positive_control_tested'] ?? '',
                        'm_positive_control_id' => $r['m_positive_control_id'] ?? 0,
                        'positive_control_other' => $r['m_bfs_positive_control_other'] ?? '',
                        'compliance_guideline_values' => $r['m_bfs_compliance_guideline_values'] ?? '',
                        'compliance_long_term' => $r['m_bfs_compliance_long_term'] ?? '',
                        'solvent_control_tested' => $r['m_bfs_solvent_control_tested'] ?? '',
                        'respective_blank_sample' => $r['m_bfs_respective_blank_sample'] ?? '',
                        'temperature_test' => $r['m_bfs_temperature_test'] ?? '',
                        'temperature_compliance' => $r['m_bfs_temperature_compliance'] ?? '',
                        'ph_sample_test' => $r['m_bfs_ph_sample_test'] ?? '',
                        'ph_sample_adjusted' => $r['m_bfs_ph_sample_adjusted'] ?? '',
                        'ph_compliance' => $r['m_bfs_ph_compliance'] ?? '',
                        'do_sample_test' => $r['m_bfs_do_sample_test'] ?? '',
                        'do_compliance' => $r['m_bfs_do_compliance'] ?? '',
                        'conductivity_sample_test' => $r['m_bfs_conductivity_sample_test'] ?? '',
                        'conductivity_compliance' => $r['m_bfs_conductivity_compliance'] ?? '',
                        'ammonium_measured' => $r['m_bfs_ammonium_measured'] ?? '',
                        'ammonium_compliance' => $r['m_bfs_ammonium_compliance'] ?? '',
                        'light_intensity' => $r['m_bfs_light_intensity'] ?? '',
                        'photoperiod' => $r['m_bfs_photoperiod'] ?? '',
                        'reference_method' => $r['m_bfs_reference_method'] ?? '',
                        'reference_paper' => $r['m_bfs_reference_paper'] ?? '',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Use insert instead of creating a separate array and then chunking it
                DB::table($target_table_name)->insert($records);

                $this->command->info("Processed chunk " . ($key + 1) . " with " . count($records) . " records");
            });

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();

        $this->command->info('Bioassay field study seeding completed!');
    }

    /**
     * Alternative method using direct database import for MySQL
     * Uncomment and use this method if your MySQL server allows LOAD DATA LOCAL INFILE
     */
    /*
    public function runDirect(): void
    {
        $this->command->info('Starting direct bioassay field study import...');

        $target_table_name = 'bioassay_field_studies';
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $path = base_path() . '/database/seeders/seeds/bioassay_monitor_bioassays_field_studies.csv';

        // Disable foreign key checks
        Schema::disableForeignKeyConstraints();

        try {
            // This requires MySQL with LOCAL INFILE enabled
            DB::connection()->getPdo()->setAttribute(\PDO::MYSQL_ATTR_LOCAL_INFILE, true);

            // Create temporary table with the same structure as the CSV
            DB::statement("DROP TABLE IF EXISTS temp_bioassay_import");
            DB::statement("CREATE TEMPORARY TABLE temp_bioassay_import LIKE monitor_bioassays_field_studies");

            // Import CSV to temp table
            DB::statement("
                LOAD DATA LOCAL INFILE '" . str_replace('\\', '\\\\', $path) . "'
                INTO TABLE temp_bioassay_import
                FIELDS TERMINATED BY ','
                ENCLOSED BY '\"'
                LINES TERMINATED BY '\\n'
                IGNORE 1 LINES
            ");

            // Insert from temp table to target table with column mapping
            DB::statement("
                INSERT INTO $target_table_name (
                    id, m_sd_id, m_ds_id, m_auxiliary_sample_identification,
                    m_bioassay_type_id, m_bioassay_type_other, m_bioassay_name_id,
                    bioassay_name_other,
                    -- ... other columns ...
                    created_at, updated_at
                )
                SELECT
                    m_bfs_id, m_sd_id, m_ds_id, m_auxiliary_sample_identification,
                    m_bioassay_type_id, m_bioassay_type_other, m_bioassay_name_id,
                    m_bfs_bioassay_name_other,
                    -- ... other columns ...
                    '$now', '$now'
                FROM temp_bioassay_import
            ");

            $count = DB::table($target_table_name)->count();
            $this->command->info("Direct import completed. Total records: $count");

        } catch (\Exception $e) {
            $this->command->error("Direct import failed: " . $e->getMessage());
            $this->command->info("Please use the standard method.");
        }

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
    }
    */
}

// php artisan db:seed --class="BioassayFieldStudySeeder"
