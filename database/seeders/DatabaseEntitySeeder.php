<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\DatabaseEntity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseEntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $target_table_name = 'database_entities';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/database_entities.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $p[] = [
                'name'                    => $this->isEmptyThenNull($r['name']),
                'description'             => $this->isEmptyThenNull($r['description']),
                'image_path'              => $this->isEmptyThenNull($r['image_path']),
                'code'                    => $this->isEmptyThenNull($r['code']),
                'dashboard_route_name'    => $this->isEmptyThenNull($r['dashboard_route_name']),
                'created_at'              => $now,
                'updated_at'              => $now,
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
        $this->command->info("  DatabaseEntitySeeder completed. ");
    }

    protected function isEmptyThenNull($value) {
        return empty($value) ? null : $value;
    }
}