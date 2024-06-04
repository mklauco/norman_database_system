<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SuspectListExchangeSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'suspect_list_exchange_sources';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/susdat_source.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $p[] = [  
                'code'          => $r['ss_id'],
                'name'          => $r['ss_abbreviation'],
                'description'   => $r['ss_description'],
                'order'         => $r['ss_order'],
                'show'          => $r['ss_show'],
                'added_by'      => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
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
