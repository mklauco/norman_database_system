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
        Schema::table('files', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
            $table->foreignId('project_id')->nullable()->default(null)->constrained('projects')->onDelete('restrict');
        });
    }

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            //
            // Drop columns
            $table->dropColumn('is_deleted');

        });
    }
};
