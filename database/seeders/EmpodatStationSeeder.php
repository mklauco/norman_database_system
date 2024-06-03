<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EmpodatStationSeeder extends Seeder
{
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'empodat_stations';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/dct_analysis_stations.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $p[] = [
                'name'                => $this->isEmptyThenNull($r['station_name']),
                'country'             => $this->isEmptyThenNull($r['country']),
                'country_other'       => $this->isEmptyThenNull($r['country_other']),
                'national_name'       => $this->isEmptyThenNull($r['national_name']),
                'short_sample_code'   => $this->isEmptyThenNull($r['short_sample_code']),
                'sample_code'         => $this->isEmptyThenNull($r['sample_code']),
                'provider_code'       => $this->isEmptyThenNull($r['provider_code']),
                'code_ec_wise'        => $this->isEmptyThenNull($r['code_ec_wise']),
                'code_ec_other'       => $this->isEmptyThenNull($r['code_ec_other']),
                'code_other'          => $this->isEmptyThenNull($r['code_other']),
                'longitude'           => $this->isEmptyThenNull($r['longitude_decimal']),
                'latitude'            => $this->isEmptyThenNull($r['latitude_decimal']),
                'specific_locations'  => $this->isEmptyThenNull($r['specific_locations']),
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        }

        $chunkSize = 1000;
        $chunks = array_chunk($p, $chunkSize);
        $k = 0;
        $count = ceil(count($p) / $chunkSize) - 1;
        foreach($chunks as $c){
            echo ($k++)."/".$count."; \n";
            DB::table($target_table_name)->insert($c);
        }

    }

    protected function isEmptyThenNull($value) {
        return empty($value) ? null : $value;
    }
}
