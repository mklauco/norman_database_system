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
        $path = base_path() . '/database/seeders/seeds/database_entities.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $p[] = [
                'name'                 => $this->isEmptyThenNull($r['name']),
                'description'          => $this->isEmptyThenNull($r['description']),
                'image_path'           => $this->isEmptyThenNull($r['image_path']),
                'code'                 => $this->isEmptyThenNull($r['code']),
                'dashboard_route_name' => $this->isEmptyThenNull($r['dashboard_route_name']),
                'number_of_records'    => $this->isEmptyThenNull($r['number_of_records']),
                'parent_id'            => $this->isEmptyThenNull($r['parent_id']),
                'show_in_dashboard'    => $this->isEmptyThenNull($r['show_in_dashboard']),
                'created_at'           => $r['created_at'],
                'updated_at'           => $r['updated_at'],
            ];
        }
// id
// name
// description
// image_path
// code
// dashboard_route_name
// created_at
// updated_at
// last_update
// number_of_records
// parent_id
// show_in_dashboard
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