<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class SupportingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==================================================
        // TEMPLATE
        // ==================================================

        // $target_table_name = 'target_table_name';
        // $now = Carbon::now();
        // $path = $pathCsv = base_path() . '/database/seeders/FILENAME.csv';;
        // $rows = SimpleExcelReader::create($path)->getRows();
        // $p = [];
        // foreach($rows as $r) {
        //     $p[] = [
        //         'target_column' => $r['csv_column'],
        //     ];
        // }

        // $chunkSize = 1000;
        // $chunks = array_chunk($p, $chunkSize);
        // $k = 0;
        // $count = ceil(count($p) / $chunkSize) - 1;
        // foreach($chunks as $c){
        //     echo ($k++)."/".$count."; ";
        //     DB::table($target_table_name)->insert($c);
        // }
    }
}
