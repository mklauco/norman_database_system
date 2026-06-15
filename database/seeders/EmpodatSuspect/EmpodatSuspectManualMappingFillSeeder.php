<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpodatSuspectManualMappingFillSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * This seeder fills the station_id, count, and ids columns in
     * empodat_suspect_xlsx_stations_mapping using the manual CSV mapping file
     * (EMPODAT_SUSPECT-mapping-JS-20251115.csv) that maps short_sample_code to xlsx_name
     */
    public function run(): void
    {
        $this->command->info('Filling empodat_suspect_xlsx_stations_mapping from manual CSV mapping...');

        $csvPath = storage_path('app/public/empodat_suspect/EMPODAT_SUSPECT-mapping-JS-20251115.csv');

        if (! file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");

            return;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->command->error("Could not open CSV file: {$csvPath}");

            return;
        }

        // Skip header row (short_saple_code,xlsx_name - note the typo in "sample")
        fgetcsv($handle);

        $updatedCount = 0;
        $notFoundStations = [];
        $notFoundMappings = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $shortSampleCode = trim($row[0]);
            $xlsxName = trim($row[1]);

            if (empty($shortSampleCode) || empty($xlsxName)) {
                continue;
            }

            // Find stations matching the short_sample_code
            $stations = DB::table('empodat_stations')
                ->where(DB::raw('LOWER(short_sample_code)'), strtolower($shortSampleCode))
                ->where(function ($query) {
                    $query->whereNull('is_deprecated')
                        ->orWhere('is_deprecated', false);
                })
                ->select('id')
                ->orderBy('id')
                ->get();

            if ($stations->isEmpty()) {
                $notFoundStations[] = $shortSampleCode;

                continue;
            }

            $stationIds = $stations->pluck('id')->toArray();
            $firstStationId = $stationIds[0];
            $stationCount = count($stationIds);
            $allStationIds = implode(', ', $stationIds);

            // Update the mapping record by xlsx_name
            $affected = DB::table('empodat_suspect_xlsx_stations_mapping')
                ->where(DB::raw('LOWER(xlsx_name)'), strtolower($xlsxName))
                ->update([
                    'station_id' => $firstStationId,
                    'count' => $stationCount,
                    'ids' => $allStationIds,
                    'updated_at' => now(),
                ]);

            if ($affected > 0) {
                $updatedCount += $affected;
                $this->command->info("Updated: {$shortSampleCode} -> station_id: {$firstStationId}");
            } else {
                $notFoundMappings[] = $xlsxName;
            }
        }

        fclose($handle);

        $this->command->info("Successfully updated {$updatedCount} rows.");

        if (! empty($notFoundStations)) {
            $this->command->warn('Stations not found for short_sample_codes: '.implode(', ', $notFoundStations));
        }

        if (! empty($notFoundMappings)) {
            $this->command->warn('Mapping records not found for xlsx_names: '.implode(', ', array_slice($notFoundMappings, 0, 10)));
            if (count($notFoundMappings) > 10) {
                $this->command->warn('... and '.(count($notFoundMappings) - 10).' more');
            }
        }
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectManualMappingFillSeeder
