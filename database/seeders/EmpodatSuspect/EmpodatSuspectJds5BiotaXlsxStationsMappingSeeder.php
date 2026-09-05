<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Insert one row per station column from JDS5 BIOTA into
 * empodat_suspect_xlsx_stations_mapping (with station_id NULL — the Fill
 * seeder resolves it via LOWER(short_sample_code) = LOWER(xlsx_name)).
 *
 * Source file: storage/app/public/empodat_suspect/SUSPECT_BIOTA_JDS5_EI_20260823.xlsx
 * Station columns: everything AFTER 'Units' and BEFORE 'mz score' (the metadata
 * block boundary), MINUS any control column. 44 columns in that block, of which
 * `JDS5-FISH-BLANK` is the control, leaving 43 stations — matching the 43
 * entries in porovnanie_sample_code_biota.xlsx.
 *
 * The control-column test is `contains "blank"`, not the TerraChem seeders'
 * `starts with "Blank"`: TerraChem prefixes those columns ("Blank 1"), JDS5
 * suffixes them ("JDS5-FISH-BLANK"). No real station code in this file contains
 * the word.
 */
class EmpodatSuspectJds5BiotaXlsxStationsMappingSeeder extends Seeder
{
    use WithoutModelEvents;

    protected const FILE_ID = 10017;

    protected const FILE_NAME = 'SUSPECT_BIOTA_JDS5_EI_20260823.xlsx';

    /** Structural marker: station columns begin AFTER this header. */
    protected const STATION_BLOCK_START_AFTER = 'Units';

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

        $this->command->info('Reading xlsx headers from JDS5 BIOTA (file_id='.self::FILE_ID.')...');

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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectJds5BiotaXlsxStationsMappingSeeder
