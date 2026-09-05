<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Insert one row per station column from TerraChem SOIL 2nd batch into
 * empodat_suspect_xlsx_stations_mapping (with station_id NULL — the Fill seeder
 * resolves it via LOWER(sample_code) = LOWER(xlsx_name)).
 *
 * Source file: storage/app/public/empodat_suspect/TerraChem Soil 2nd Batch.xlsx
 * Station columns: everything AFTER 'Units' and BEFORE 'mz score'. 17 columns,
 * none of them a control column — this file carries no Blank column at all,
 * unlike the other TerraChem sources. The `contains "blank"` test is kept
 * anyway; it is a no-op here.
 */
class EmpodatSuspectTerraChemSoilBatch2XlsxStationsMappingSeeder extends Seeder
{
    use WithoutModelEvents;

    protected const FILE_ID = 10021;

    protected const FILE_NAME = 'TerraChem Soil 2nd Batch.xlsx';

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

        $this->command->info('Reading xlsx headers from TerraChem SOIL 2nd batch (file_id='.self::FILE_ID.')...');

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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectTerraChemSoilBatch2XlsxStationsMappingSeeder
