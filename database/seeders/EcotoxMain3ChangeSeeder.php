<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EcotoxMain3ChangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'ecotox_main_3_changes';
        DB::table($target_table_name)->truncate();
        $path = base_path().'/database/seeders/seeds/ecotox_tables/ecotox3_change.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];

        foreach ($rows as $r) {
            $changeDate = $this->parseDateTime($r['change_date']);
            $p[] = [
                'column_name' => $this->isEmptyThenNull($r['change_item']),
                'user_id' => $this->isEmptyThenNull($r['user_id']),
                'change_date' => $changeDate,
                'ecotox_id' => $this->isEmptyThenNull($r['ecotox_id']),
                'change_old' => $this->isEmptyThenNull($r['change_old']),
                'change_new' => $this->isEmptyThenNull($r['change_new']),
                'change_type' => $this->isEmptyThenNull($r['change_type']),
                'created_at' => $changeDate,
                'updated_at' => $changeDate,
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

        $this->command->info('EcotoxMain3ChangeSeeder completed. Seeded '.count($p).' records.');
    }

    protected function isEmptyThenNull($value)
    {
        return empty($value) ? null : $value;
    }

    protected function parseDateTime($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}

// php artisan db:seed --class=EcotoxMain3ChangeSeeder
