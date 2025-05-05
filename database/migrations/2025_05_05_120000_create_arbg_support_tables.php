<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

  private $tablePrefix = 'arbg_';
    /**
    * Run the migrations.
    */
    public function up(): void
    {
        
        $lookupTables = [
            'data_analytical_method',
            'data_bacteria_isolation_method',
            'data_bacterial_group',
            'data_concentration',
            'data_concentration_data',
            'data_grain_size_distribution',
            'data_interpretation_criteria',
            'data_non_targeted_analysis',
            'data_phenotype_determination_method',
            'data_precision_coordinates',
            'data_sample_matrix',
            'data_soil_texture',
            'data_soil_type',
            'data_standardised_analytical_method',
            'data_targeted_analysis',
            'data_type_of_data_source',
            'data_type_of_depth_sampling',
            'data_type_of_monitoring',
            'data_type_of_sample',
        ];

        $lookupTablesAbbr = [
            'data_country',

        ];
        
        // Create each lookup table
        foreach ($lookupTables as $tableName) {
            $this->createLookupTable($tableName);
        }
        
        // Create each lookup table
        foreach ($lookupTablesAbbr as $tableName) {
            $this->createLookupTableAbbr($tableName);
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
        Schema::create($this->tablePrefix.$tableName, function (Blueprint $table) {
            $table->id('id');
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('ordering')->default(0)->nullable();
            $table->timestamps();
        });
    }
    
    private function createLookupTableAbbr($tableName)
    {
        Schema::create($this->tablePrefix.$tableName, function (Blueprint $table) {
            $table->id('id');
            $table->string('abbreviation', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('ordering')->default(0)->nullable();
            $table->timestamps();
        });
    }
    
    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        
        $lookupTables = [
            'data_country',
            'data_analytical_method',
            'data_bacteria_isolation_method',
            'data_bacterial_group',
            'data_concentration',
            'data_concentration_data',
            'data_grain_size_distribution',
            'data_interpretation_criteria',
            'data_non_targeted_analysis',
            'data_phenotype_determination_method',
            'data_precision_coordinates',
            'data_sample_matrix',
            'data_soil_texture',
            'data_soil_type',
            'data_standardised_analytical_method',
            'data_targeted_analysis',
            'data_type_of_data_source',
            'data_type_of_depth_sampling',
            'data_type_of_monitoring',
            'data_type_of_sample',
        ];
        
        // Drop each lookup table
        foreach ($lookupTables as $tableName) {
            Schema::dropIfExists($this->tablePrefix.$tableName);
        }
    }
};
