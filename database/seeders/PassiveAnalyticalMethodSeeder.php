<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class PassiveAnalyticalMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting passive_analytical_method seeding...');

        $tableName = 'passive_analytical_method';
        $path = base_path().'/database/seeders/seeds/passive_tables/data_tables/dct_analytical_method.csv';

        // Check if file exists
        if (! file_exists($path)) {
            $this->command->error("File not found: $path");

            return;
        }

        // Clear existing data
        DB::table($tableName)->delete();
        $now = Carbon::now();

        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();

        try {
            $reader = SimpleExcelReader::create($path)
                ->useDelimiter(',')
                ->headersToSnakeCase(false);

            $chunkSize = 100;
            $reader->getRows()
                ->chunk($chunkSize)
                ->each(function ($rows, $key) use ($tableName, $now) {
                    $records = [];
                    foreach ($rows as $r) {
                        $records[] = [
                            'id' => $r['id'] ?? null,
                            'am_unit' => $this->nullIfEmpty($r['am_unit'] ?? null),
                            'am_detection_limit' => $this->nullIfEmpty($r['am_detection_limit'] ?? null),
                            'am_quantification_limit' => $this->nullIfEmpty($r['am_quantification_limit'] ?? null),
                            'dpm_id' => $this->nullIfEmpty($r['dpm_id'] ?? null),
                            'dpm_other' => $this->nullIfEmpty($r['dpm_other'] ?? null),
                            'dam_id' => $this->nullIfEmpty($r['dam_id'] ?? null),
                            'dam_other' => $this->nullIfEmpty($r['dam_other'] ?? null),
                            'dsm_id' => $this->nullIfEmpty($r['dsm_id'] ?? null),
                            'dsm_number' => $this->nullIfEmpty($r['dsm_number'] ?? null),
                            'dsm_other' => $this->nullIfEmpty($r['dsm_other'] ?? null),
                            'dp_id' => $this->nullIfEmpty($r['dp_id'] ?? null),
                            'am_extraction_recovery_correction' => $this->nullIfEmpty($r['am_extraction_recovery_correction'] ?? null),
                            'am_field_blank_check' => $this->nullIfEmpty($r['am_field_blank_check'] ?? null),
                            'am_lab_iso17025' => $this->nullIfEmpty($r['am_lab_iso17025'] ?? null),
                            'am_lab_accredited' => $this->nullIfEmpty($r['am_lab_accredited'] ?? null),
                            'am_interlab_studies' => $this->nullIfEmpty($r['am_interlab_studies'] ?? null),
                            'am_interlab_summary' => $this->nullIfEmpty($r['am_interlab_summary'] ?? null),
                            'am_control_charts' => $this->nullIfEmpty($r['am_control_charts'] ?? null),
                            'am_authority_control' => $this->nullIfEmpty($r['am_authority_control'] ?? null),
                            'am_remark' => $this->nullIfEmpty($r['am_remark'] ?? null),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (! empty($records)) {
                        try {
                            DB::table($tableName)->insert($records);
                            $this->command->info('Processed chunk '.($key + 1).' with '.count($records)." records for table: $tableName");
                        } catch (\Exception $e) {
                            $this->command->error("Error inserting into $tableName: ".$e->getMessage());
                        }
                    }
                });
        } catch (\Exception $e) {
            $this->command->error("Error processing file $path: ".$e->getMessage());
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->command->info('passive_analytical_method seeding completed!');
    }

    /**
     * Return null if value is empty or 'NULL' string
     */
    private function nullIfEmpty(?string $value): ?string
    {
        if ($value === null || $value === '' || strtoupper($value) === 'NULL') {
            return null;
        }

        return $value;
    }
}

// php artisan db:seed --class="PassiveAnalyticalMethodSeeder"
