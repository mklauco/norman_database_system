
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
        Schema::table('ecotox_pnec3', function (Blueprint $table) {
            if (! Schema::hasColumn('ecotox_pnec3', 'substance_id')) {
                $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances')->onUpdate('cascade')->onDelete('restrict');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecotox_pnec3', function (Blueprint $table) {
            $table->dropColumn(['substance_id']);
        });
    }
};
