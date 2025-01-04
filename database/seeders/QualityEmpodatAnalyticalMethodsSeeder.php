<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class QualityEmpodatAnalyticalMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $target_table_name = 'list_quality_empodat_analytical_methods';
        $now = Carbon::now();
        // $path = base_path() . '/database/seeders/seeds/database_entities.csv';
        // $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        $p[] = [
            'name' => 'Adequately supported by quality-related information',
            'min_rating' => 68,
            'max_rating' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $p[] = [
            'name' => 'Supported by limited quality-related information',
            'min_rating' => 52,
            'max_rating' => 68,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $p[] = [
            'name' => 'Minimal quality-related information',
            'min_rating' => 22,
            'max_rating' => 52,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $p[] = [
            'name' => 'Not supported by quality-related information',
            'min_rating' => 0,
            'max_rating' => 22,
            'created_at' => $now,
            'updated_at' => $now,
        ];


        $chunkSize = 1000;
        $chunks = array_chunk($p, $chunkSize);
        $k = 0;
        $count = ceil(count($p) / $chunkSize) - 1;
        foreach($chunks as $c){
            echo ($k++)."/".$count."; \n";
            DB::table($target_table_name)->insert($c);
        }
        $this->command->info("  DatabaseEntitySeeder completed. ");
    }

    protected function isEmptyThenNull($value) {
        return empty($value) ? null : $value;
    }
}