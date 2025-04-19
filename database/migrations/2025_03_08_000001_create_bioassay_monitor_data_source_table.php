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
        Schema::create('bioassay_monitor_data_source', function (Blueprint $table) {
            $table->id();
            $table->text('m_ds_organisation')->comment('Organisation');
            $table->text('m_ds_address')->comment('Address');
            $table->text('m_ds_country')->comment('Country');
            $table->text('m_ds_laboratory')->comment('Laboratory');
            $table->text('m_ds_author')->comment('Author');
            $table->text('m_ds_email')->comment('E-mail');
            $table->unsignedTinyInteger('m_data_source_id')->comment('Type of data source');
            $table->unsignedTinyInteger('m_monitoring_id')->comment('Type of monitoring');
            $table->text('m_ds_monitoring_other')->comment('Other');
            $table->text('m_ds_project')->comment('Title of project');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bioassay_monitor_data_source');
    }
};
