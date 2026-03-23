<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hazards_substance_classifications', function (Blueprint $table) {
            $table->integer('p_all_points')->nullable()->after('p_total_points');
            $table->integer('b_all_points')->nullable()->after('b_total_points');
            $table->integer('m_all_points')->nullable()->after('m_total_points');
            $table->integer('t_all_points')->nullable()->after('t_total_points');
        });
    }

    public function down(): void
    {
        Schema::table('hazards_substance_classifications', function (Blueprint $table) {
            $table->dropColumn([
                'p_all_points',
                'b_all_points',
                'm_all_points',
                't_all_points',
            ]);
        });
    }
};
