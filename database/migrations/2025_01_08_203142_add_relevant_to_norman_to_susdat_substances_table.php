<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('susdat_substances', function (Blueprint $table) {
            //
            $table->tinyInteger('relevant_to_norman')->after('code')->default(1);
        });
        // write sql query that will update the relevant_to_norman column to false for substances with IDS grater thatn 400000
        DB::statement('UPDATE susdat_substances SET relevant_to_norman = 0 WHERE id >= 400000');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('susdat_substances', function (Blueprint $table) {
            //
            $table->dropColumn('relevant_to_norman');
        });
    }
};
