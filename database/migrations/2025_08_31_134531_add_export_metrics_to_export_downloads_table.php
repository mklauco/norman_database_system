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
        Schema::table('export_downloads', function (Blueprint $table) {
            $table->bigInteger('file_size_bytes')->nullable()->after('record_count');
            $table->string('file_size_formatted')->nullable()->after('file_size_bytes');
            $table->decimal('processing_time_seconds', 8, 2)->nullable()->after('file_size_formatted');
            $table->timestamp('started_at')->nullable()->after('processing_time_seconds');
            $table->timestamp('completed_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_downloads', function (Blueprint $table) {
            $table->dropColumn([
                'file_size_bytes',
                'file_size_formatted',
                'processing_time_seconds',
                'started_at',
                'completed_at',
            ]);
        });
    }
};
