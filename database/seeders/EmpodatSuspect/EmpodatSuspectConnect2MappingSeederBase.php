<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Shared body of the two CONNECT 2 station-mapping seeders (10003, 10004).
 *
 * Inserts one `empodat_suspect_xlsx_stations_mapping` row per station column of
 * the source spreadsheet, with `station_id` left NULL — the matching Fill
 * seeder resolves it. This is the same two-step every other source uses.
 *
 * Station columns are the header entries AFTER `Units` and BEFORE `mz score`,
 * the boundary of the HRMS metadata block. The v2 CONNECT 2 files carry that
 * block, which the previous (legacy-format) versions did not.
 *
 * The two concrete seeders differ only in constants, so the logic cannot drift
 * between SEDIMENTS and BIOTA.
 *
 * Expects this file's mapping rows to have been cleared first (see
 * {@see \Database\Seeders\EmpodatSuspect\Traits\DeletesSuspectFileData}); it
 * refuses to run if rows for this file_id already exist, rather than
 * duplicating them.
 */
abstract class EmpodatSuspectConnect2MappingSeederBase extends Seeder
{
    use WithoutModelEvents;

    protected const STATION_BLOCK_START_AFTER = 'Units';

    protected const METADATA_BOUNDARY = 'mz score';

    /**
     * files.id this spreadsheet belongs to — the only file_id this seeder writes.
     */
    abstract protected function fileId(): int;

    /**
     * Spreadsheet basename inside storage/app/public/empodat_suspect/.
     */
    abstract protected function fileName(): string;

    /**
     * Station columns the v2 file is expected to contain. A mismatch aborts:
     * the row count of the whole import depends on this number, so a silent
     * change here is exactly the kind of drift that must not pass unnoticed.
     */
    abstract protected function expectedStationColumns(): int;

    public function run(): void
    {
        $fileId = $this->fileId();
        $path = storage_path('app/public/empodat_suspect/'.$this->fileName());

        if (! file_exists($path)) {
            throw new RuntimeException("Source file not found: {$path}");
        }

        $this->command->info("Reading station headings from {$this->fileName()} (file_id={$fileId})...");

        $firstRow = SimpleExcelReader::create($path)->getRows()->first();

        if (! $firstRow) {
            throw new RuntimeException('Source file appears to be empty (no header row).');
        }

        $stationColumns = $this->extractStationColumns(array_keys($firstRow));

        if (count($stationColumns) !== $this->expectedStationColumns()) {
            throw new RuntimeException(sprintf(
                'ABORTED: expected %d station column(s) for file_id=%d, found %d: %s. '
                .'Nothing was inserted.',
                $this->expectedStationColumns(),
                $fileId,
                count($stationColumns),
                implode(', ', $stationColumns),
            ));
        }

        if ($this->alreadySeeded($stationColumns, $fileId)) {
            return;
        }

        $this->assertHeadingsUnused($stationColumns, $fileId);

        $now = Carbon::now();
        $rows = array_map(static fn (string $name): array => [
            'xlsx_name' => $name,
            'file_id' => $fileId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $stationColumns);

        DB::table('empodat_suspect_xlsx_stations_mapping')->insert($rows);

        $this->command->info('Inserted '.count($rows)." mapping row(s) for file_id={$fileId} (station_id still NULL):");

        foreach ($stationColumns as $name) {
            $this->command->line('  - '.$name);
        }
    }

    /**
     * Idempotency, the way every other mapping seeder in this module behaves:
     * re-running against an already-populated table is a no-op, so the full
     * reload ({@see EmpodatSuspectResetAndReseedSeeder}) can call this seeder
     * without duplicating rows.
     *
     * The no-op is granted only when the stored headings are EXACTLY the ones
     * this spreadsheet contains. A partial or different set means the table
     * holds a previous version of this file, which must be cleared first —
     * inserting on top would mix two generations of headings.
     *
     * @param  list<string>  $stationColumns
     */
    private function alreadySeeded(array $stationColumns, int $fileId): bool
    {
        $stored = DB::table('empodat_suspect_xlsx_stations_mapping')
            ->where('file_id', $fileId)
            ->pluck('xlsx_name')
            ->all();

        if ($stored === []) {
            return false;
        }

        $expected = $stationColumns;
        sort($stored);
        sort($expected);

        if ($stored === $expected) {
            $this->command->info('  '.count($stored)." mapping row(s) already present for file_id={$fileId} and identical to this file's headings — nothing to do.");

            return true;
        }

        throw new RuntimeException(sprintf(
            'ABORTED: file_id=%d already has %d mapping row(s), and they do not match this spreadsheet. '
            .'Stored but absent from the file: %s. In the file but not stored: %s. '
            .'Clear this file\'s data before re-importing.',
            $fileId,
            count($stored),
            implode(', ', array_diff($stored, $expected)) ?: '(none)',
            implode(', ', array_diff($expected, $stored)) ?: '(none)',
        ));
    }

    /**
     * A heading already owned by another file would make this file's rows
     * point at that file's mapping row, and would later be deleted with it.
     *
     * @param  list<string>  $stationColumns
     */
    private function assertHeadingsUnused(array $stationColumns, int $fileId): void
    {
        $clashes = DB::table('empodat_suspect_xlsx_stations_mapping')
            ->whereIn('xlsx_name', $stationColumns)
            ->where('file_id', '!=', $fileId)
            ->get(['id', 'file_id', 'xlsx_name']);

        if ($clashes->isEmpty()) {
            return;
        }

        $detail = $clashes
            ->map(fn (object $row): string => $row->xlsx_name.' (file_id='.$row->file_id.')')
            ->implode(', ');

        throw new RuntimeException(
            "ABORTED: these headings are already mapped under a different file_id: {$detail}. Nothing was inserted."
        );
    }

    /**
     * Header entries after `Units` and before `mz score`, BOM-stripped and
     * trimmed exactly as the Main seeder cleans them, so what is stored is what
     * will later be looked up.
     *
     * @param  list<string>  $header
     * @return list<string>
     */
    protected function extractStationColumns(array $header): array
    {
        $header = array_map(
            static fn (string $h): string => trim(str_replace("\xEF\xBB\xBF", '', $h)),
            $header
        );

        $startIndex = array_search(self::STATION_BLOCK_START_AFTER, $header, true);

        if ($startIndex === false) {
            throw new RuntimeException('No "'.self::STATION_BLOCK_START_AFTER.'" column in the header — cannot locate the station block.');
        }

        $columns = [];
        $count = count($header);

        for ($i = $startIndex + 1; $i < $count; $i++) {
            $name = $header[$i];

            if ($name === self::METADATA_BOUNDARY) {
                break;
            }

            if ($name === '') {
                continue;
            }

            $columns[] = $name;
        }

        return $columns;
    }
}
