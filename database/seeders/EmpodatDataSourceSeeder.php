<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EmpodatDataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'empodat_data_sources';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/dct_data_source.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $p[] = [
                'id'                        => $r['id_data'],
                'type_data_source_id'       => $r['dts_id'] ? $r['dts_id'] : null,
                'type_data_source_other'    => $r['dts_other'] ?? null,
                'type_monitoring_id'        => $r['dtm_id'] ? $r['dtm_id'] : null,
                'type_monitoring_other'     => $r['dtm_other'] ?? null,
                'data_accessibility_id'     => $r['dda_id'] ? $r['dda_id'] : null,
                'data_accessibility_other'  => $r['dda_other'] ?? null,
                'project_title'             => $r['title_project'] ?? null,
                //'id_laboratory'             => $r['laboratory_id'] ?? null,   - deprecated  
                'author'                    => $r['author'] ?? null,
                'email'                     => $r['email'] ?? null,
                'reference1'                => $r['literature1'] ?? null,
                'reference2'                => $r['literature2'] ?? null,
                'created_at'                => $now,
                'updated_at'                => $now,
                // 'organisation_id'        => $r['organisation'],    -> move to EmpodatMainSeeder
                // 'laboratory1_id'         => $r['laboratory_name'], -> move to EmpodatMainSeeder
                // 'laboratory2_id'         => $r['laboratory_name'], -> move to EmpodatMainSeeder
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
}
