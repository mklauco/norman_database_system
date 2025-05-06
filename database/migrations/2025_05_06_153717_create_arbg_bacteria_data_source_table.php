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
        Schema::create('arbg_bacteria_data_source', function (Blueprint $table) {
            $table->id();
            
            $table->tinyInteger('type_of_data_source_id')->default(0)->comment('Type of data source');
            $table->unsignedTinyInteger('type_of_monitoring_id')->default(0)->comment('Type of monitoring');
            $table->string('type_of_monitoring_other')->nullable()->comment('Type of monitoring - other');
            $table->string('title_of_project')->nullable()->comment('Title of project');
            $table->string('organisation')->nullable()->comment('Organisation');
            $table->string('e_mail')->nullable()->comment('E-mail');
            $table->string('laboratory')->nullable()->comment('Laboratory');
            $table->string('laboratory_id')->nullable()->comment('Laboratory ID');
            $table->text('references_literature_1')->nullable()->comment('References / literature 1');
            $table->text('references_literature_2')->nullable()->comment('References / literature 2');
            $table->string('author')->nullable()->comment('Author');
            
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
        Schema::dropIfExists('arbg_bacteria_data_source');
    }
};