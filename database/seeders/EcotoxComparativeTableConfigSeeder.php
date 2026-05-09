<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EcotoxComparativeTableConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'ecotox_comparative_table_configs';
        DB::table($target_table_name)->truncate();
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/ecotox_tables/table_header.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];

        foreach ($rows as $r) {
            $p[] = [
                'group' => $this->isEmptyThenNull($r['tab_group']),
                'header' => $this->isEmptyThenNull($r['tab_h1']),
                'header_2' => $this->isEmptyThenNull($r['tab_h2']),
                'column_name' => $this->isEmptyThenNull($r['tab_name']),
                'column_id' => $this->isEmptyThenNull($r['tab_id']),
                'is_editable' => $this->convertEditToBoolean($r['tab_edit']),
                'input_type' => $this->isEmptyThenNull($r['tab_type']),
                'description' => $this->isEmptyThenNull($r['tab_description']),
                'order' => $this->isEmptyThenNull($r['tab_order']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $chunkSize = 2000;
        $chunks = array_chunk($p, $chunkSize);
        $k = 0;
        $count = ceil(count($p) / $chunkSize) - 1;
        foreach ($chunks as $c) {
            echo ($k++).'/'.$count."; \n";
            DB::table($target_table_name)->insert($c);
        }
    }

    protected function isEmptyThenNull($value)
    {
        return empty($value) ? null : $value;
    }

    protected function convertEditToBoolean($value)
    {
        // Convert 0 to false, 1 to true
        if ($value === '0' || $value === 0) {
            return false;
        } elseif ($value === '1' || $value === 1) {
            return true;
        }

        // Return the original value if it's not 0 or 1
        return $value;
    }
}

// php artisan db:seed --class=EcotoxComparativeTableConfigSeeder
