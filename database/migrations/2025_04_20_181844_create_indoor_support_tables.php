<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
    * Run the migrations.
    */
    public function up(): void
    {
        
        $lookupTables = [
            'data_dam',
            'data_dcf',
            'data_dcoe',
            'data_dcoe_old',
            'data_dcom1',
            'data_dcom2',
            'data_dcot',
            'data_dcsc',
            'data_dcse',
            'data_dcsf',
            'data_dcsi',
            'data_dcsm',
            'data_dcso1',
            'data_dcso2',
            'data_dcso3',
            'data_dcsw',
            'data_dic',
            'data_dlim',
            'data_dloc',
            'data_dpc',
            'data_dpm',
            'data_dpp',
            'data_dsm',
            'data_dsm1',
            'data_dsm2',
            'data_dsps',
            'data_dtoe',
            'data_dts',
            'data_matrix',
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
        Schema::create('indoor_'.$tableName, function (Blueprint $table) {
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
        Schema::create('indoor_'.$tableName, function (Blueprint $table) {
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
            'data_country_other',
            'data_dam',
            'data_dcf',
            'data_dcoe',
            'data_dcoe_old',
            'data_dcom1',
            'data_dcom2',
            'data_dcot',
            'data_dcsc',
            'data_dcse',
            'data_dcsf',
            'data_dcsi',
            'data_dcsm',
            'data_dcso1',
            'data_dcso2',
            'data_dcso3',
            'data_dcsw',
            'data_dic',
            'data_dlim',
            'data_dloc',
            'data_dpc',
            'data_dpm',
            'data_dpp',
            'data_dsm',
            'data_dsm1',
            'data_dsm2',
            'data_dsps',
            'data_dtoe',
            'data_dts',
            'data_matrix',
        ];
        
        // Drop each lookup table
        foreach ($lookupTables as $tableName) {
            Schema::dropIfExists('indoor_'.$tableName);
        }
    }
};
