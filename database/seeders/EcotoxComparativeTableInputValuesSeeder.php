<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EcotoxComparativeTableInputValuesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'ecotox_comparative_table_input_values';
        DB::table($target_table_name)->truncate();
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/ecotox_tables/table_value.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];

        // TASK: get column_name from ecotox_comparative_table_configs table
        $column_names = DB::table('ecotox_comparative_table_configs')
            ->pluck('column_name', 'column_id');

        foreach ($rows as $r) {
            $p[] = [
                'val_id' => $this->isEmptyThenNull($r['val_id']),
                'column_id' => $this->isEmptyThenNull($r['tab_id']),
                'column_name' => $column_names[$this->isEmptyThenNull($r['tab_id'])],
                'input_value' => $this->isEmptyThenNull($r['val_value']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $chunkSize = 2000;
        $chunks = array_chunk($p, $chunkSize);
        $k = 0;
        $count = ceil(count($p) / $chunkSize) - 1;
        foreach ($chunks as $c) {
            echo $k++.'/'.$count."; \n";
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

// php artisan db:seed --class=EcotoxComparativeTableInputValuesSeeder
