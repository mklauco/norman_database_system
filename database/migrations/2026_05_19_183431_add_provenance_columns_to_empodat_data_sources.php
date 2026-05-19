<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empodat_data_sources', function (Blueprint $table) {
            $table->text('data_source_url')->nullable()->after('reference2');
            $table->text('bibliographic_source')->nullable()->after('data_source_url');
            $table->text('identifier_url')->nullable()->after('bibliographic_source');
            $table->string('orcid_no', 50)->nullable()->after('identifier_url');
            $table->string('data_provider', 255)->nullable()->after('orcid_no');
            $table->text('data_source_remark')->nullable()->after('data_provider');
        });

        Schema::table('list_data_source_organisations', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_data_organisation_id')->nullable()->unique()->after('country_id');
        });
    }

    public function down(): void
    {
        Schema::table('list_data_source_organisations', function (Blueprint $table) {
            $table->dropUnique(['legacy_data_organisation_id']);
            $table->dropColumn('legacy_data_organisation_id');
        });

        Schema::table('empodat_data_sources', function (Blueprint $table) {
            $table->dropColumn([
                'data_source_url',
                'bibliographic_source',
                'identifier_url',
                'orcid_no',
                'data_provider',
                'data_source_remark',
            ]);
        });
    }
};
