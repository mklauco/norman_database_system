<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class BioassaysMonitorDataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $target_table_name = 'bioassay_monitor_data_source';
        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/bioassay_tables/monitor_data_source.csv';

        if (!file_exists($path)) {
            $this->command->error("File not found: $path");
            return;
        }

        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();

        try {
            $rows = SimpleExcelReader::create($path)->getRows();
            $records = [];

            foreach ($rows as $r) {
                $records[] = [
                    'id' => $r['m_ds_id'],
                    'm_ds_organisation' => $r['m_ds_organisation'] ?? '',
                    'm_ds_address' => $r['m_ds_address'] ?? '',
                    'm_ds_country' => $r['m_ds_country'] ?? '',
                    'm_ds_laboratory' => $r['m_ds_laboratory'] ?? '',
                    'm_ds_author' => $r['m_ds_author'] ?? '',
                    'm_ds_email' => $r['m_ds_email'] ?? '',
                    'm_data_source_id' => $r['m_data_source_id'] ?? 0,
                    'm_monitoring_id' => $r['m_monitoring_id'] ?? 0,
                    'm_ds_monitoring_other' => $r['m_ds_monitoring_other'] ?? '',
                    'm_ds_project' => $r['m_ds_project'] ?? '',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $chunkSize = 1000;
            $chunks = array_chunk($records, $chunkSize);
            $k = 0;
            $count = ceil(count($records) / $chunkSize);

            foreach ($chunks as $chunk) {
                $this->command->info("Processing chunk " . ($k + 1) . "/" . $count);
                DB::table($target_table_name)->insert($chunk);
                $k++;
            }

            $this->command->info("Successfully seeded $target_table_name with " . count($records) . " records");

        } catch (\Exception $e) {
            $this->command->error("Error seeding $target_table_name: " . $e->getMessage());
        } finally {
            // Re-enable foreign key checks
            Schema::enableForeignKeyConstraints();
        }
    }

}


// php artisan db:seed --class="BioassaysMonitorDataSourceSeeder"
