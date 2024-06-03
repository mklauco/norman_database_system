<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Susdat\Category;
use Illuminate\Database\Seeder;
use App\Models\Susdat\Substance;
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
        // get existing ids
        echo 'Getting substance ids...' . PHP_EOL;
        $existingSubstanceids = Substance::pluck('id')->toArray();
        $existingCategoryids = Category::pluck('id')->toArray();
        
        // clean join table
        $target_table_name = 'susdat_category_substance';
        DB::table($target_table_name)->delete();

        
        echo 'Seeding ' .$target_table_name. PHP_EOL;
        $logFileNameCat = base_path() . '/database/seeders/seeds/susdat_category_join_cat.log';
        $logFileNameSub = base_path() . '/database/seeders/seeds/susdat_category_join_sub.log';
        file_put_contents($logFileNameCat, '');
        file_put_contents($logFileNameSub, '');
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/susdat_category_join.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        $k = 0;
        foreach($rows as $r) {
            $substance_id = (int)ltrim($r['sus_id'], '0');
            $category_id = $r['sus_cat_id'];
            $substance_ok = in_array($substance_id, $existingSubstanceids);
            $category_ok = in_array($category_id, $existingCategoryids);

            if($substance_ok && $category_ok){
                $p[] = [
                    'substance_id'    => $substance_id,
                    'category_id'     => $category_id,
                ];
            } elseif(!$substance_ok) {
                $message = "Skipping existing substance_id: ".$substance_id."\n";
                file_put_contents($logFileNameSub, $message, FILE_APPEND);
            } elseif(!$category_ok) {
                $message = "Skipping existing category_id: ".$category_id."\n";
                file_put_contents($logFileNameCat, $message, FILE_APPEND);
            } else {
                $message = "Something wrong with substance_id and category_id: ".$substance_id." - ".$category_id."\n";
                file_put_contents($logFileNameCat, $message, FILE_APPEND);
                file_put_contents($logFileNameSub, $message, FILE_APPEND);
            }
            
        }

        $chunkSize = 2000;
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

// php artisan db:seed --class=Database\Seeders\SusdatSusdatCategoryJoinSeeder