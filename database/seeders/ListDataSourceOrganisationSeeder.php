<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class ListDataSourceOrganisationSeeder extends Seeder
{
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'list_data_source_organisations';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/data_organisation.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        foreach($rows as $r) {
            $p[] = [
                'id'            => $r['id_new'],
                'name'          => $r['organisation_name_en'] ? $r['organisation_name_en'] : null,
                'city'          => $r['city'] ? $r['city'] : null,
                'acronym'       => $r['acronym'] ? $r['acronym'] : null,
                'country_id'    => $r['country'] ? $r['country'] : null,
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
}
