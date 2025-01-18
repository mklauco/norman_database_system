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
        Schema::table('query_logs', function (Blueprint $table) {
            //
            $table->integer('total_count')->nullable()->default(null);
            $table->integer('actual_count')->nullable()->default(null);
            $table->string('database_key')->nullable()->default(null);
            $table->binary('query_hash')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('query_logs', function (Blueprint $table) {
            //
            $table->dropColumn('total_count');
            $table->dropColumn('actual_count');
            $table->dropColumn('database_key');
            $table->dropColumn('query_hash');
        });
    }
};
