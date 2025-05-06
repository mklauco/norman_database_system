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
        Schema::create('arbg_gene_data_source', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type_of_data_source_id')->default(0)->comment('Type of data source');
            $table->unsignedTinyInteger('type_of_monitoring_id')->default(0)->comment('Type of monitoring');
            $table->longtext('type_of_monitoring_other')->nullable()->comment('Type of monitoring - other');
            $table->longtext('title_of_project')->nullable()->comment('Title of project');
            $table->longtext('organisation')->nullable()->comment('Organisation');
            $table->longtext('e_mail')->nullable()->comment('E-mail');
            $table->longtext('laboratory')->nullable()->comment('Laboratory');
            $table->longtext('laboratory_id')->nullable()->comment('Laboratory ID');
            $table->longtext('references_literature_1')->nullable()->comment('References / literature 1');
            $table->longtext('references_literature_2')->nullable()->comment('References / literature 2');
            $table->longtext('author')->nullable()->comment('Author');
            
            $table->timestamps();
            
            // Indexes
            $table->index('type_of_data_source_id');
            $table->index('type_of_monitoring_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbg_gene_data_source');
    }
};
