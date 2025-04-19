<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // List of all lookup tables to create
        $lookupTables = [
            'monitor_x_adverse_outcome',
            'monitor_x_assay_format',
            'monitor_x_bioassay_name',
            'monitor_x_bioassay_performed',
            'monitor_x_bioassay_type',
            'monitor_x_cathegorial_label',
            'monitor_x_cell_line_strain',
            'monitor_x_country',
            'monitor_x_data_source',
            'monitor_x_deviation',
            'monitor_x_effect',
            'monitor_x_effect_equivalent',
            'monitor_x_effect_level',
            'monitor_x_endpoint',
            'monitor_x_fraction',
            'monitor_x_life_stage',
            'monitor_x_main_determinand',
            'monitor_x_measured_parameter',
            'monitor_x_monitoring',
            'monitor_x_positive_control',
            'monitor_x_precision_coordinates',
            'monitor_x_proxy_pressures',
            'monitor_x_sample_matrix',
            'monitor_x_sampling_technique',
            'monitor_x_solvent',
            'monitor_x_standard_substance',
            'monitor_x_test_organism',
            'monitor_x_test_system',
            'monitor_x_type_sampling',
            'monitor_x_yes_no',
            'monitor_x_yes_no_na_nr',
            'monitor_x_yes_no_na_nr_other'
        ];

        // Create each lookup table
        foreach ($lookupTables as $tableName) {
            $this->createLookupTable($tableName);
        }
    }

    /**
     * Create a standard lookup table
     *
     * @param string $tableName
     * @return void
     */
    private function createLookupTable($tableName)
    {
        Schema::create($tableName, function (Blueprint $table) {
            $table->id('id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('ordering')->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // List of all lookup tables to drop
        $lookupTables = [
            'monitor_x_adverse_outcome',
            'monitor_x_assay_format',
            'monitor_x_bioassay_name',
            'monitor_x_bioassay_performed',
            'monitor_x_bioassay_type',
            'monitor_x_cathegorial_label',
            'monitor_x_cell_line_strain',
            'monitor_x_country',
            'monitor_x_data_source',
            'monitor_x_deviation',
            'monitor_x_effect',
            'monitor_x_effect_equivalent',
            'monitor_x_effect_level',
            'monitor_x_endpoint',
            'monitor_x_fraction',
            'monitor_x_life_stage',
            'monitor_x_main_determinand',
            'monitor_x_measured_parameter',
            'monitor_x_monitoring',
            'monitor_x_positive_control',
            'monitor_x_precision_coordinates',
            'monitor_x_proxy_pressures',
            'monitor_x_sample_matrix',
            'monitor_x_sampling_technique',
            'monitor_x_solvent',
            'monitor_x_standard_substance',
            'monitor_x_test_organism',
            'monitor_x_test_system',
            'monitor_x_type_sampling',
            'monitor_x_yes_no',
            'monitor_x_yes_no_na_nr',
            'monitor_x_yes_no_na_nr_other'
        ];

        // Drop each lookup table
        foreach ($lookupTables as $tableName) {
            Schema::dropIfExists($tableName);
        }
    }
};
