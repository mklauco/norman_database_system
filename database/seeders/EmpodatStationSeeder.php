<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EmpodatStationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Increase memory limit to handle large datasets
        ini_set('memory_limit', '2G');

        $target_table_name = 'empodat_stations';
        DB::table($target_table_name)->truncate();
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/stations.csv';

        $this->command->info('Starting station seeding...');

        // Process CSV in chunks to avoid memory exhaustion
        $chunkSize = 1000;
        $chunkNumber = 0;

        SimpleExcelReader::create($path)
            ->getRows()
            ->chunk($chunkSize)
            ->each(function ($rows) use ($target_table_name, $now, &$chunkNumber) {
                $records = [];

                foreach ($rows as $r) {
                    $records[] = [
                        'name' => $this->isEmptyThenNull($r['station_name']),
                        'country' => $this->isEmptyThenNull($r['country']),
                        'country_other' => $this->isEmptyThenNull($r['country_other']),
                        'national_name' => $this->isEmptyThenNull($r['national_name']),
                        'short_sample_code' => $this->isEmptyThenNull($r['short_sample_code']),
                        'sample_code' => $this->isEmptyThenNull($r['sample_code']),
                        'provider_code' => $this->isEmptyThenNull($r['provider_code']),
                        'code_ec_wise' => $this->isEmptyThenNull($r['code_ec_wise']),
                        'code_ec_other' => $this->isEmptyThenNull($r['code_ec_other']),
                        'code_other' => $this->isEmptyThenNull($r['code_other']),
                        'longitude' => (float) rtrim($this->isEmptyThenNull($r['longitude_decimal']), ','),
                        'latitude' => (float) rtrim($this->isEmptyThenNull($r['latitude_decimal']), ','),
                        'specific_locations' => $this->isEmptyThenNull($r['specific_locations']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($records)) {
                    DB::table($target_table_name)->insert($records);
                    $chunkNumber++;
                    $this->command->info("Processed chunk {$chunkNumber} with ".count($records).' records');
                }
            });

        $this->command->info('Station seeding completed!');
    }

    protected function isEmptyThenNull($value)
    {
        // return empty($value) ? '' : $value;
        return $value;
    }
}
// php artisan db:seed --class=EmpodatStationSeeder
