<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('query_logs', function (Blueprint $table): void {
            $table->index('created_at', 'query_logs_created_at_index');
            $table->index(['user_id', 'created_at'], 'query_logs_user_id_created_at_index');
            $table->index(['database_key', 'created_at'], 'query_logs_database_key_created_at_index');
        });

        Schema::table('user_login_retentions', function (Blueprint $table): void {
            $table->index('login_datetime', 'user_login_retentions_login_datetime_index');
            $table->index('user_id', 'user_login_retentions_user_id_index');
        });

        Schema::table('export_downloads', function (Blueprint $table): void {
            $table->index('created_at', 'export_downloads_created_at_index');
            $table->index('user_id', 'export_downloads_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('query_logs', function (Blueprint $table): void {
            $table->dropIndex('query_logs_created_at_index');
            $table->dropIndex('query_logs_user_id_created_at_index');
            $table->dropIndex('query_logs_database_key_created_at_index');
        });

        Schema::table('user_login_retentions', function (Blueprint $table): void {
            $table->dropIndex('user_login_retentions_login_datetime_index');
            $table->dropIndex('user_login_retentions_user_id_index');
        });

        Schema::table('export_downloads', function (Blueprint $table): void {
            $table->dropIndex('export_downloads_created_at_index');
            $table->dropIndex('export_downloads_user_id_index');
        });
    }
};
