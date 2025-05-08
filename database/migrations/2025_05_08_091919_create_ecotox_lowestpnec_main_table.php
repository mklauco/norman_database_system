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
        Schema::create('ecotox_lowestpnec_main', function (Blueprint $table) {
            $table->id(); // Standard id column
            $table->unsignedInteger('lowest_id')->nullable()->default(null)->index(); // Original primary key
            $table->text('lowest_matrix')->nullable()->default(null);
            $table->unsignedInteger('sus_id')->nullable()->default(null)->index();
            $table->text('der_id')->nullable()->default(null);
            $table->text('norman_pnec_id')->nullable()->default(null);
            $table->text('lowesta_id')->nullable()->default(null);
            $table->tinyText('lowest_pnec_type')->nullable()->default(null);
            $table->tinyText('lowest_institution')->nullable()->default(null);
            $table->tinyText('lowest_test_endpoint')->nullable()->default(null);
            $table->integer('lowest_AF')->nullable()->default(null);
            $table->double('lowest_pnec_value')->nullable()->default(null);
            $table->tinyText('lowest_derivation_method')->nullable()->default(null);
            $table->integer('lowest_editor')->nullable()->default(null);
            $table->boolean('lowest_active')->nullable()->default(null);
            $table->boolean('lowest_color')->nullable()->default(null);
            $table->datetime('lowest_year')->nullable()->default(null);
            $table->boolean('lowest_pnec')->nullable()->default(null);
            $table->text('lowest_base_name')->nullable()->default(null);
            $table->text('lowest_base_id')->nullable()->default(null);
            $table->unsignedSmallInteger('lowest_sum_vote')->nullable()->default(null);
            $table->unsignedInteger('sus_id_origin')->nullable()->default(null);
            $table->timestamps(); // Added created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_lowestpnec_main');
    }
};