<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Susdat\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\Models\SLE\SuspectListExchangeSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SusdatCategorySourceJoinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // get existing ids
        echo 'Getting source/vategory ids...' . PHP_EOL;
        $existingSourceids = SuspectListExchangeSource::select('id', 'code')->get()->keyby('code')->toArray();
        $existingCategoryids = Category::pluck('id')->toArray();
        
        // clean join table
        $target_table_name = 'susdat_category_source';
        DB::table($target_table_name)->delete();


        echo 'Seeding ' .$target_table_name. PHP_EOL;
        $logFileNameSrc = base_path() . '/database/seeders/seeds/'.$target_table_name.'_src.log';
        $logFileNameCat = base_path() . '/database/seeders/seeds/'.$target_table_name.'_cat.log';
        file_put_contents($logFileNameSrc, '');
        file_put_contents($logFileNameCat, '');

        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/susdat_category_source.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $category_id = (int)ltrim($r['sus_cat_id'], '0');
            $source_code = $r['sus_cat_source'];
            $category_ok = in_array($category_id, $existingCategoryids);
            $source_ok = array_key_exists($source_code, $existingSourceids);
            // dd($category_ok, $source_ok, $source_code, $existingSourceids[$source_code]['id']);
            if($category_ok && $source_ok){
                $p[] = [
                    'category_id'    => $category_id,
                    'source_id'     => $existingSourceids[$source_code]['id'],
                ];
            } elseif(!$category_ok) {
                $message = "Skipping existing category_id: ".$category_id."\n";
                file_put_contents($logFileNameCat, $message, FILE_APPEND);
            } elseif(!$source_ok) {
                $message = "Skipping existing source_id: ".$source_code."\n";
                file_put_contents($logFileNameSrc, $message, FILE_APPEND);
            } else {
                $message = "Something wrong with category_id and source_id: ".$category_id." - ".$source_code."\n";
                file_put_contents($logFileNameSrc, $message, FILE_APPEND);
                file_put_contents($logFileNameCat, $message, FILE_APPEND);
            }
        }
        $chunkSize = 1000;
        $chunks = array_chunk($p, $chunkSize);
        $k = 0;
        $count = ceil(count($p) / $chunkSize) - 1;
        foreach($chunks as $c){
            echo ($k++)."/".$count."; \n";
            DB::table($target_table_name)->insertOrFail($c);
        }
    }
}
