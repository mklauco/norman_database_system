<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ListMatricesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $target_table_name = 'list_matrices';
        DB::table($target_table_name)->truncate();
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/data_matrice.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            
            // dd($r);
            $p[] = [
                'id'         => $r['matrice_id'],
                'title'      => $this->isEmptyThenNull($r['matrice_title1']),
                'subtitle'   => $this->isEmptyThenNull($r['matrice_title2']),
                'type'       => $this->isEmptyThenNull($r['matrice_title3']),
                'name'       => $this->isEmptyThenNull($r['matrice_title']),
                'dct_name'   => $this->isEmptyThenNull($r['matrice_dct_name']),
                'unit'       => $this->isEmptyThenNull($r['matrice_unit']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
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
        // return empty($value) ? '' : $value;
        return $value;
    }

}
// php artisan db:seed --class=ListMatricesSeeder