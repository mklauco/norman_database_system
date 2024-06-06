<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class ListTypeDataSourceSeeder extends Seeder
{
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'list_type_data_sources';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/data_type_data_source.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $p[] = [
                'id'                  => $r['dts_id'],
                'name'                => $r['dts_name'],
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
}
