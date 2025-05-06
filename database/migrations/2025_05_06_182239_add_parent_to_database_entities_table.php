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
        Schema::table('database_entities', function (Blueprint $table) {
            //
            $table->foreignId('parent_id')->nullable()->default(null)->references('id')->on('database_entities');
            $table->boolean('show_in_dashboard')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('database_entities', function (Blueprint $table) {
            //
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->dropColumn('show_in_dashboard');
        });
    }
};
