<?php

declare(strict_types=1);

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
        Schema::table('empodat_analytical_methods', function (Blueprint $table) {
            $table->decimal('uncertainty_loq_range_min')->nullable()->after('uncertainty_loq')->comment('in %');
            $table->decimal('uncertainty_loq_range_max')->nullable()->after('uncertainty_loq_range_min')->comment('in %');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_analytical_methods', function (Blueprint $table) {
            $table->dropColumn(['uncertainty_loq_range_min', 'uncertainty_loq_range_max']);
        });
    }
};
