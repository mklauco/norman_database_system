<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpodatSuspectSusdatCodeMappingsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targetTable = 'empodat_suspect_susdat_code_mappings';

        $this->command->info('Seeding code mappings from duplicity_final.csv...');

        $path = storage_path('app/public/empodat_suspect/duplicity_final.csv');

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");

            return;
        }

        // Truncate existing data
        DB::table($targetTable)->truncate();

        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->command->error('Failed to open CSV file');

            return;
        }

        // Read and validate header
        $header = fgetcsv($handle);
        if (! $header) {
            $this->command->error('Failed to read CSV header');
            fclose($handle);

            return;
        }

        // Clean header (remove BOM)
        $header = array_map(function ($h) {
            $h = str_replace("\xEF\xBB\xBF", '', $h);

            return trim($h);
        }, $header);

        $expectedHeader = ['old_legacy_norman_id', 'old_code', 'new_code'];
        if ($header !== $expectedHeader) {
            $this->command->error('Invalid CSV header. Expected: '.implode(', ', $expectedHeader));
            $this->command->error('Got: '.implode(', ', $header));
            fclose($handle);

            return;
        }

        $now = Carbon::now();
        $batch = [];
        $batchSize = 500;
        $rowCount = 0;
        $duplicateCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== 3) {
                $this->command->warn('Skipping malformed row at line '.($rowCount + 2));

                continue;
            }

            $batch[] = [
                'old_legacy_norman_id' => trim($row[0]),
                'old_code' => trim($row[1]),
                'new_code' => trim($row[2]),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $rowCount++;

            if (count($batch) >= $batchSize) {
                $duplicateCount += $this->upsertBatch($targetTable, $batch);
                $batch = [];
            }
        }

        // Insert remaining records
        if (! empty($batch)) {
            $duplicateCount += $this->upsertBatch($targetTable, $batch);
        }

        fclose($handle);

        $uniqueCount = $rowCount - $duplicateCount;
        $this->command->info("Successfully seeded {$uniqueCount} unique code mappings into {$targetTable}");
        if ($duplicateCount > 0) {
            $this->command->warn("Skipped {$duplicateCount} duplicate old_code entries from CSV");
        }
    }

    /**
     * Upsert a batch of records, counting duplicates.
     *
     * @return int Number of duplicate records in this batch
     */
    protected function upsertBatch(string $table, array $batch): int
    {
        $originalCount = count($batch);

        // Deduplicate within batch - keep last occurrence of each old_code
        $deduplicated = [];
        foreach ($batch as $record) {
            $deduplicated[$record['old_code']] = $record;
        }
        $batch = array_values($deduplicated);

        $inBatchDuplicates = $originalCount - count($batch);

        $countBefore = DB::table($table)->count();

        DB::table($table)->upsert(
            $batch,
            ['old_code'],
            ['old_legacy_norman_id', 'new_code', 'updated_at']
        );

        $countAfter = DB::table($table)->count();
        $inserted = $countAfter - $countBefore;
        $crossBatchDuplicates = count($batch) - $inserted;

        return $inBatchDuplicates + $crossBatchDuplicates;
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectSusdatCodeMappingsSeeder
