<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empodat_analytical_methods', function (Blueprint $table) {
            $table->dropForeign(['sample_preparation_method_id']);
            $table->dropForeign(['analytical_method_id']);
            $table->dropForeign(['standardised_method_id']);

            $table->unsignedBigInteger('legacy_given_analyte_id')->nullable()->after('given_analyte_id');
            $table->unsignedBigInteger('legacy_laboratory_participate_id')->nullable()->after('laboratory_participate_id');
        });
    }

    public function down(): void
    {
        Schema::table('empodat_analytical_methods', function (Blueprint $table) {
            $table->dropColumn(['legacy_given_analyte_id', 'legacy_laboratory_participate_id']);

            $table->foreign('sample_preparation_method_id')->references('id')->on('list_sample_preparation_methods');
            $table->foreign('analytical_method_id')->references('id')->on('list_analytical_methods');
            $table->foreign('standardised_method_id')->references('id')->on('list_standardised_methods');
        });
    }
};
