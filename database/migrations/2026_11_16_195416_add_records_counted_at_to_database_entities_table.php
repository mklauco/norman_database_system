<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('database_entities', function (Blueprint $table): void {
            $table->timestamp('records_counted_at')->nullable()->after('number_of_records');
        });
    }

    public function down(): void
    {
        Schema::table('database_entities', function (Blueprint $table): void {
            $table->dropColumn('records_counted_at');
        });
    }
};
