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
        Schema::table('literature_temp_main', function (Blueprint $table) {
            $table->unsignedBigInteger('matrix_id')->nullable()->default(null)->after('tissue_id');

            // Add foreign key constraint
            $table->foreign('matrix_id')
                ->references('id')
                ->on('list_matrices')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('literature_temp_main', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['matrix_id']);

            // Then drop the column
            $table->dropColumn('matrix_id');
        });
    }
};
