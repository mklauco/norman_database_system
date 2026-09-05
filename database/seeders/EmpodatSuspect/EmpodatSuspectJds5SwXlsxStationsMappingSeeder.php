<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Insert one row per station column from JDS5 SURFACE WATER into
 * empodat_suspect_xlsx_stations_mapping (with station_id NULL — the Fill seeder
 * resolves it via LOWER(sample_code) = LOWER(xlsx_name)).
 *
 * Source file: storage/app/public/empodat_suspect/SUSPECT_SW_JDS5_EI_20260708.xlsx
 *
 * ⚠️ THIS FILE HAS NO `Units` COLUMN. Every other suspect source ends Block A
 * with `Units`, and every other seeder uses it as STATION_BLOCK_START_AFTER.
 * Here the header goes straight from `BasedonHRMSLibrary` to the control
 * column, so the marker is `BasedonHRMSLibrary` instead. Leaving it as `Units`
 * makes array_search return false and the station list comes back empty —
 * silently, with no error.
 *
 * Station columns: everything AFTER 'BasedonHRMSLibrary' and BEFORE 'mz score',
 * MINUS the control column `Blank JDS5 SW`. 50 columns in that block, so 49
 * stations — matching the 49 rows in porovnanie_sample_code_sw.xlsx exactly.
 */
class EmpodatSuspectJds5SwXlsxStationsMappingSeeder extends Seeder
{
    use WithoutModelEvents;

    protected const FILE_ID = 10020;

    protected const FILE_NAME = 'SUSPECT_SW_JDS5_EI_20260708.xlsx';

    /** Structural marker: station columns begin AFTER this header. */
    protected const STATION_BLOCK_START_AFTER = 'BasedonHRMSLibrary';

    /** Boundary marker: HRMS metadata block (mz score, isotopicfit score, ...) begins HERE. */
    protected const METADATA_BOUNDARY = 'mz score';

    public function run(): void
    {
        $target = 'empodat_suspect_xlsx_stations_mapping';
        $path = storage_path('app/public/empodat_suspect/'.self::FILE_NAME);

        if (! file_exists($path)) {
            $this->command->error("Source file not found: {$path}");

            return;
        }

        $this->command->info('Reading xlsx headers from JDS5 SURFACE WATER (file_id='.self::FILE_ID.')...');

        $firstRow = SimpleExcelReader::create($path)->getRows()->first();
        if (! $firstRow) {
            $this->command->error('Source file appears empty (no header row)');

            return;
        }

        $header = $this->cleanHeader(array_keys($firstRow));
        $stationCols = $this->extractStationColumns($header);

        if (empty($stationCols)) {
            $this->command->error('No station columns identified — check boundary markers');

            return;
        }

        $this->command->info('Identified '.count($stationCols).' station columns (after "'.self::STATION_BLOCK_START_AFTER.'", before "'.self::METADATA_BOUNDARY.'")');

        $now = Carbon::now();
        $inserted = 0;
        $skipped = 0;

        foreach ($stationCols as $xlsxName) {
            $exists = DB::table($target)
                ->where('xlsx_name', $xlsxName)
                ->where('file_id', self::FILE_ID)
                ->exists();

            if ($exists) {
                $this->command->info("Skipping duplicate (file_id, xlsx_name): {$xlsxName}");
                $skipped++;

                continue;
            }

            DB::table($target)->insert([
                'xlsx_name' => $xlsxName,
                'file_id' => self::FILE_ID,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        $this->command->info("Done: {$inserted} inserted, {$skipped} skipped (file_id=".self::FILE_ID.')');
    }

    /**
     * @param  array<int, string>  $header
     * @return array<int, string>
     */
    protected function cleanHeader(array $header): array
    {
        return array_map(
            fn (string $h): string => trim(str_replace("\xEF\xBB\xBF", '', $h)),
            $header
        );
    }

    /**
     * @param  array<int, string>  $header
     * @return array<int, string>
     */
    protected function extractStationColumns(array $header): array
    {
        $startIndex = array_search(self::STATION_BLOCK_START_AFTER, $header, true);
        if ($startIndex === false) {
            $this->command->warn('Boundary column "'.self::STATION_BLOCK_START_AFTER.'" not found in header');

            return [];
        }

        $stationCols = [];
        $headerCount = count($header);

        for ($i = $startIndex + 1; $i < $headerCount; $i++) {
            $name = $header[$i] ?? '';

            if ($name === self::METADATA_BOUNDARY) {
                break;
            }

            if ($name === '' || $name === null) {
                continue;
            }

            if (stripos($name, 'blank') !== false) {
                continue;
            }

            $stationCols[] = $name;
        }

        return $stationCols;
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectJds5SwXlsxStationsMappingSeeder
