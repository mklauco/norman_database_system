<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class PassiveDataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting passive_data_source seeding...');

        $tableName = 'passive_data_source';
        $path = base_path().'/database/seeders/seeds/passive_tables/data_tables/dct_data_source.csv';

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
                            'org_name' => $this->nullIfEmpty($r['org_name'] ?? null),
                            'org_city' => $this->nullIfEmpty($r['org_city'] ?? null),
                            'org_country' => $this->nullIfEmpty($r['org_country'] ?? null),
                            'org_lab1_name' => $this->nullIfEmpty($r['org_lab1_name'] ?? null),
                            'org_lab1_city' => $this->nullIfEmpty($r['org_lab1_city'] ?? null),
                            'org_lab1_country' => $this->nullIfEmpty($r['org_lab1_country'] ?? null),
                            'org_lab2_name' => $this->nullIfEmpty($r['org_lab2_name'] ?? null),
                            'org_lab2_city' => $this->nullIfEmpty($r['org_lab2_city'] ?? null),
                            'org_lab2_country' => $this->nullIfEmpty($r['org_lab2_country'] ?? null),
                            'org_family_name' => $this->nullIfEmpty($r['org_family_name'] ?? null),
                            'org_first_name' => $this->nullIfEmpty($r['org_first_name'] ?? null),
                            'org_email' => $this->nullIfEmpty($r['org_email'] ?? null),
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

        $this->command->info('passive_data_source seeding completed!');
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

// php artisan db:seed --class="PassiveDataSourceSeeder"
