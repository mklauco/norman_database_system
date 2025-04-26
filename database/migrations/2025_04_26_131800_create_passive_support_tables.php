<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

  private $tablePrefix = 'passive_';
    /**
    * Run the migrations.
    */
    public function up(): void
    {
        
        $lookupTables = [
          'data_basin_name',
          'data_fraction',
          'data_ind_concentration',
          'data_matrix',
          'data_passive_sampler',
          'data_precision_coordinates',
          'data_protocols',
          'data_proxy_pressures',
          'data_sample_preparation_method',
          'data_sampler_type',
          'data_standardised_method',
          'data_type_data_source',
          'data_type_monitoring',
          'data_type_sampling',
        ];

        $lookupTablesAbbr = [
            'data_country',
            'data_country_other',
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
            'data_basin_name',
            'data_fraction',
            'data_ind_concentration',
            'data_matrix',
            'data_passive_sampler',
            'data_precision_coordinates',
            'data_protocols',
            'data_proxy_pressures',
            'data_sample_preparation_method',
            'data_sampler_type',
            'data_standardised_method',
            'data_type_data_source',
            'data_type_monitoring',
            'data_type_sampling',
        ];
        
        // Drop each lookup table
        foreach ($lookupTables as $tableName) {
            Schema::dropIfExists($this->tablePrefix.$tableName);
        }
    }
};
