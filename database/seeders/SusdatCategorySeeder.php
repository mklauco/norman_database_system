<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SusdatCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $target_table_name = 'susdat_categories';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/susdat_category.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $p[] = [  
                'id'            => $r['sus_cat_id'],
                'name'          => $this->isEmptyThenNull($r['sus_cat_name']),
                'abbreviation'  => $this->extractStringBetweenParentheses($r['sus_cat_name']),
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
        return empty($value) ? null : $this->removeStringAfterParenthesis($value);
    }

    function extractStringBetweenParentheses($text) {
        // Define the regular expression pattern
        $pattern = '/\(([^)]+)\)/';
    
        // Perform the regex match
        if (preg_match($pattern, $text, $matches)) {
            // Return the matched string without the parentheses
            return $matches[1];
        } else {
            // Return null if no match found
            return null;
        }
    }

    function removeStringAfterParenthesis($string) {
        // Use a regular expression to find the opening parenthesis and remove everything after it
        return preg_replace('/\s*\(.*$/', '', $string);
    }
    
}

// php artisan db:seed --class=SusdatCategorySeeder
