<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * uncertainty_loq was numeric(8,2) and rounded legacy values such as 0.003 to
 * 0.00. It becomes double precision, like lod and loq. Existing values are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE empodat_analytical_methods ALTER COLUMN uncertainty_loq TYPE double precision');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE empodat_analytical_methods ALTER COLUMN uncertainty_loq TYPE numeric(8,2)');
    }
};
