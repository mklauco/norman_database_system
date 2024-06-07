<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\Models\SLE\SuspectListExchangeSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SusdatSourceSubstanceJoinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // get existing ids
        echo 'Getting source/category ids...' . PHP_EOL;
        $existingSourceids = SuspectListExchangeSource::select('id', 'code')->get()->keyby('code')->toArray();
        $existingSubstanceids = Substance::pluck('id')->toArray();
        
        // clean join table
        $target_table_name = 'susdat_source_substance';
        DB::table($target_table_name)->delete();


        echo 'Seeding ' .$target_table_name. PHP_EOL;
        $logFileNameSrc = base_path() . '/database/seeders/seeds/'.$target_table_name.'_src.log';
        $logFileNameSub = base_path() . '/database/seeders/seeds/'.$target_table_name.'_sub.log';
        file_put_contents($logFileNameSrc, '');
        file_put_contents($logFileNameSub, '');

        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/sle_sle_susdat_id.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $substance_id = (int)ltrim($r['susdat_id'], '0');
            $source_code = 'S'.$r['sle'];
            $substance_ok = in_array($substance_id, $existingSubstanceids);
            $source_ok = array_key_exists($source_code, $existingSourceids);
            // dd($substance_ok, $source_ok, $source_code, $existingSourceids[$source_code]['id']);
            if($substance_ok && $source_ok){
                $p[] = [
                    'substance_id'  => $substance_id,
                    'source_id'     => $existingSourceids[$source_code]['id'],
                ];
            } elseif(!$substance_ok) {
                $message = "Skipping existing substance_id: ".$substance_id."\n";
                file_put_contents($logFileNameSub, $message, FILE_APPEND);
            } elseif(!$source_ok) {
                $message = "Skipping existing source_id: ".$source_code."\n";
                file_put_contents($logFileNameSrc, $message, FILE_APPEND);
            } else {
                $message = "Something wrong with substance_id and source_id: ".$substance_id." - ".$source_code."\n";
                file_put_contents($logFileNameSrc, $message, FILE_APPEND);
                file_put_contents($logFileNameSub, $message, FILE_APPEND);
            }
        }
        $chunkSize = 1000;
        $chunks = array_chunk($p, $chunkSize);
        $k = 0;
        $count = ceil(count($p) / $chunkSize) - 1;
        foreach($chunks as $c){
            echo ($k++)."/".$count."; \n";
            DB::table($target_table_name)->insertOrIgnore($c);
        }
    }
}

// php artisan db:seed --class="SusdatSourceSubstanceJoinSeeder"