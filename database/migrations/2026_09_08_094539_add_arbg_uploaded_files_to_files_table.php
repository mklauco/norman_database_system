<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Import the legacy ARB/G "List of Uploaded Files" (issue #28).
 *
 * Source is the legacy `arb_list` and `arg_list` tables, exported verbatim to
 * database/seeders/seeds/arbg_tables/. Our `files` table is a direct port of
 * that legacy schema, so the mapping is one column to one column.
 *
 * Ids are assigned explicitly in the 3000 block, which is unoccupied and sits
 * below files_id_seq (at 9003), so the sequence needs no adjustment and normal
 * uploads are unaffected.
 *
 *   3000-3002  ARB, legacy arb_list.list_id 1-3   -> database_entity_id 14
 *   3003-3029  ARG, legacy arg_list.list_id 1-27  -> database_entity_id 15
 *
 * Insert only. Nothing existing is read for update, changed or removed.
 */
return new class extends Migration
{
    private const ARB_ID_BASE = 3000;

    private const ARG_ID_BASE = 3003;

    private const ARB_ENTITY_ID = 14;

    private const ARG_ENTITY_ID = 15;

    private const ARB_ROWS = 3;

    private const ARG_ROWS = 27;

    private const STORAGE_DIR = 'arbg';

    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * arb_list.list_id 3 records ids 66-95, not 66-86 as the legacy row claims.
     * Its metadata was never updated after the file was re-uploaded: legacy's
     * own ARB search returns 95 records, and arbg_bacteria_main ids 66-95 all
     * carry source_id 66, the same single source the row points at. Left
     * uncorrected, bacteria records 87-95 would belong to no file.
     */
    private const ARB_CORRECTIONS = [
        3 => ['list_analysis_to' => 95, 'list_analysis_number' => 30],
    ];

    public function up(): void
    {
        $rows = array_merge(
            $this->rowsFor('arb_list.csv', self::ARB_ROWS, self::ARB_ID_BASE, self::ARB_ENTITY_ID, self::ARB_CORRECTIONS),
            $this->rowsFor('arg_list.csv', self::ARG_ROWS, self::ARG_ID_BASE, self::ARG_ENTITY_ID, []),
        );

        $ids = array_column($rows, 'id');

        $occupied = DB::table('files')->whereIn('id', $ids)->pluck('id')->all();
        if ($occupied !== []) {
            throw new RuntimeException(
                'Refusing to import ARB/G uploaded files: files.id already taken: '.implode(', ', $occupied)
            );
        }

        DB::transaction(fn () => DB::table('files')->insert($rows));
    }

    public function down(): void
    {
        DB::table('files')
            ->whereBetween('id', [self::ARB_ID_BASE, self::ARG_ID_BASE + self::ARG_ROWS - 1])
            ->whereIn('database_entity_id', [self::ARB_ENTITY_ID, self::ARG_ENTITY_ID])
            ->delete();
    }

    /**
     * @param  array<int, array<string, int>>  $corrections
     * @return list<array<string, mixed>>
     */
    private function rowsFor(string $csv, int $expectedRows, int $idBase, int $entityId, array $corrections): array
    {
        $legacy = $this->readCsv($csv);

        if (count($legacy) !== $expectedRows) {
            throw new RuntimeException(
                sprintf('%s holds %d rows, expected %d. Refusing to guess ids.', $csv, count($legacy), $expectedRows)
            );
        }

        $now = Carbon::now();

        return array_map(function (array $row) use ($idBase, $entityId, $corrections, $now): array {
            $listId = (int) $row['list_id'];
            $row = array_merge($row, $corrections[$listId] ?? []);

            return [
                'id' => $idBase + $listId - 1,
                'name' => $row['list_name'],
                'original_name' => $row['list_file'],
                'file_path' => self::STORAGE_DIR.'/'.$row['list_file'],
                'mime_type' => self::XLSX_MIME,
                'database_entity_id' => $entityId,
                'uploaded_at' => $row['list_date'],
                'is_deleted' => (bool) (int) $row['list_deleted'],
                'is_protected' => false,
                'number_of_records' => (int) $row['list_analysis_number'],
                'main_id_from' => (int) $row['list_analysis_from'],
                'main_id_to' => (int) $row['list_analysis_to'],
                'analysis_number' => (int) $row['list_analysis_number'],
                'source_id_from' => (int) $row['list_source_from'],
                'source_id_to' => (int) $row['list_source_to'],
                'source_number' => (int) $row['list_source_number'],
                'method_id_from' => (int) $row['list_method_from'],
                'method_id_to' => (int) $row['list_method_to'],
                'method_number' => (int) $row['list_method_number'],
                'list_type' => $row['list_type'],
                'note' => $row['list_note'] === '' ? null : $row['list_note'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $legacy);
    }

    /**
     * @return list<array<string, string>>
     */
    private function readCsv(string $name): array
    {
        $path = base_path('database/seeders/seeds/arbg_tables/'.$name);

        if (! is_readable($path)) {
            throw new RuntimeException("Legacy export not readable: {$path}");
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === []) {
                continue;
            }
            $rows[] = array_map(
                fn (?string $value): string => $value === null || $value === 'NULL' ? '' : $value,
                array_combine($header, $line)
            );
        }

        fclose($handle);

        return $rows;
    }
};
