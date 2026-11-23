<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy dct_data_source.title_project is TEXT. Three legacy data sources
 * (159688633, 160035828, 160039259) have project titles of 267-467
 * characters, which varchar(255) cannot hold, so the legacy backfill stopped
 * on them. PostgreSQL changes varchar to text without rewriting the table and
 * every existing value stays as it is.
 *
 * down() fails once a title longer than 255 characters is stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empodat_data_sources', function (Blueprint $table): void {
            $table->text('project_title')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('empodat_data_sources', function (Blueprint $table): void {
            $table->string('project_title')->nullable()->default(null)->change();
        });
    }
};
