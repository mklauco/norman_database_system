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
        Schema::table('empodat_suspect_main', function (Blueprint $table) {
            // Add file_id column
            $table->unsignedBigInteger('file_id')->nullable()->after('id');

            // Add foreign key constraint
            $table->foreign('file_id')
                ->references('id')
                ->on('files')
                ->onDelete('set null');

            // Add index for faster queries
            $table->index('file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_suspect_main', function (Blueprint $table) {
            // Drop foreign key and index first
            $table->dropForeign(['file_id']);
            $table->dropIndex(['file_id']);

            // Drop the column
            $table->dropColumn('file_id');
        });
    }
};
