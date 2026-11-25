<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a template version to the version it replaced. Unique: a version is replaced by at most one other.
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->foreignId('previous_template_id')->nullable()->unique()->constrained('templates')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('previous_template_id');
        });
    }
};
