<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Link ARB/G records to the uploaded file they came from (issue #28).
 *
 * Legacy expresses the link as an id range on arb_list / arg_list
 * (list_analysis_from .. list_analysis_to). Those ranges are now on
 * files.main_id_from / main_id_to, so the column is derived from them rather
 * than from any new information.
 *
 * Soft-deleted legacy rows are skipped when backfilling: arg_list row 17 is
 * superseded by row 24 over the identical range 1728-1769, and only the live
 * row may own those records. Among live rows the ranges tile the data with no
 * overlap and no gap, so every record resolves to exactly one file.
 *
 * Must run after the migration that imports the ARB/G rows into `files`.
 */
return new class extends Migration
{
    private const ARB_ENTITY_ID = 14;

    private const ARG_ENTITY_ID = 15;

    /** @var array<string, int> */
    private const TABLES = [
        'arbg_bacteria_main' => self::ARB_ENTITY_ID,
        'arbg_gene_main' => self::ARG_ENTITY_ID,
    ];

    public function up(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->unsignedBigInteger('file_id')->nullable();
                $blueprint->index('file_id', $table.'_file_id_index');
                $blueprint->foreign('file_id', $table.'_file_id_foreign')
                    ->references('id')->on('files')
                    ->nullOnDelete();
            });
        }

        foreach (self::TABLES as $table => $entityId) {
            $this->backfill($table, $entityId);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropForeign($table.'_file_id_foreign');
                $blueprint->dropIndex($table.'_file_id_index');
                $blueprint->dropColumn('file_id');
            });
        }
    }

    private function backfill(string $table, int $entityId): void
    {
        $files = DB::table('files')
            ->where('database_entity_id', $entityId)
            ->where('is_deleted', false)
            ->whereNotNull('main_id_from')
            ->whereNotNull('main_id_to')
            ->orderBy('id')
            ->get(['id', 'main_id_from', 'main_id_to']);

        foreach ($files as $file) {
            DB::table($table)
                ->whereBetween('id', [$file->main_id_from, $file->main_id_to])
                ->update(['file_id' => $file->id]);
        }
    }
};
