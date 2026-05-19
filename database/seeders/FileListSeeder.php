<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class FileListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Updates existing file records with DCT list fields from CSV.
     */
    public function run(): void
    {
        $this->command->info('Starting File DCT list fields update from CSV...');
        $targetTableName = 'files';
        $startTime = microtime(true);

        $csvPath = base_path().'/database/seeders/seeds/dct_list.csv';

        if (! file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");

            return;
        }

        $reader = SimpleExcelReader::create($csvPath)
            ->useDelimiter(',')
            ->headersToSnakeCase(false);

        $chunkSize = 100;
        $updatedCount = 0;
        $notFoundCount = 0;

        $reader->getRows()
            ->chunk($chunkSize)
            ->each(function ($rows, $key) use ($targetTableName, $startTime, &$updatedCount, &$notFoundCount) {
                $chunkStartTime = microtime(true);

                foreach ($rows as $r) {
                    $fileId = $this->safeInt($r['list_id']);

                    if ($fileId === null) {
                        continue;
                    }

                    $exists = DB::table($targetTableName)->where('id', $fileId)->exists();

                    if (! $exists) {
                        $notFoundCount++;

                        continue;
                    }

                    $updateData = [
                        'main_id_from' => $this->safeInt($r['list_analysis_from']),
                        'main_id_to' => $this->safeInt($r['list_analysis_to']),
                        'analysis_number' => $this->safeInt($r['list_analysis_number']),
                        'source_id_from' => $this->safeInt($r['list_source_from']),
                        'source_id_to' => $this->safeInt($r['list_source_to']),
                        'source_number' => $this->safeInt($r['list_source_number']),
                        'method_id_from' => $this->safeInt($r['list_method_from']),
                        'method_id_to' => $this->safeInt($r['list_method_to']),
                        'method_number' => $this->safeInt($r['list_method_number']),
                        'list_type' => $this->safeString($r['list_type']),
                        'note' => $this->safeString($r['list_note']),
                        'matrice_dct' => $this->safeInt($r['matrice_dct']),
                        'doi' => $this->safeString($r['list_doi']),
                        'updated_at' => Carbon::now(),
                    ];

                    try {
                        DB::table($targetTableName)
                            ->where('id', $fileId)
                            ->update($updateData);

                        $updatedCount++;
                    } catch (\Exception $e) {
                        $this->command->error("Error updating file ID {$fileId}: ".$e->getMessage());
                    }
                }

                $chunkEndTime = microtime(true);
                $chunkElapsedTime = round($chunkEndTime - $chunkStartTime, 2);
                $totalElapsedTime = round($chunkEndTime - $startTime, 2);

                $this->command->info('Processed chunk '.($key + 1).". Chunk time: {$chunkElapsedTime}s, Total elapsed: {$totalElapsedTime}s");
            });

        $this->command->info('File DCT list fields update completed!');
        $this->command->info("Updated: {$updatedCount} files");
        $this->command->info("Not found: {$notFoundCount} files");
    }

    /**
     * Helper function to safely convert empty strings to null for integer fields
     */
    private function safeInt(mixed $value, ?int $default = null): ?int
    {
        if ($value === '' || $value === null) {
            return $default;
        }

        return (int) $value;
    }

    /**
     * Helper function to safely convert empty strings to default string
     */
    private function safeString(mixed $value, ?string $default = null): ?string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}
// php artisan db:seed --class=FileListSeeder
