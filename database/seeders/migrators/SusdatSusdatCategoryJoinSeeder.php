<?php

namespace Database\Seeders\Migrators;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Susdat\Substances;
use Illuminate\Support\Facades\DB;
use App\Models\MariaDB\Susdat as OldData;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SusdatSusdatCategoryJoinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'susdat_category_substance';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/susdat_category_join.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        $k = 0;
        foreach($rows as $r) {
            $p[] = [
                'substance_id'    => (int)ltrim($r['sus_id'], '0'),
                'category_id'     => $r['sus_cat_id'],
            ];            
            $k++;
            if($k >= 1001){
                break;
            }
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

// php artisan db:seed --class=Database\Seeders\migrators\SusdatSusdatCategoryJoinSeeder