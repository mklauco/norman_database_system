<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class SarsCov2SourceSeeder extends Seeder
{
    public function run(): void
    {
        // Disable query logging for large inserts
        DB::disableQueryLog();

        $filePath = base_path() . '/database/seeders/seeds_sars/sars0.xlsx';
        $targetTable = 'sars_cov_file_uploads';

        // Stream rows from the CSV in chunks of 1,000
        SimpleExcelReader::create($filePath)
            ->getRows()
            ->chunk(1000)
            ->each(function ($rowsChunk) use ($targetTable) {
                // Transform each row in the chunk into the structure you need
                $formattedRows = $rowsChunk->map(function ($r) {
                    return [
                        'id' => $r['source_id'],
                        'filename' => $r['sars_source'] ?: null,
                        'directory' => $r['sars_source_dir'] ?: null,
                        'is_available' => $r['noexport'] ?: null,
                        'created_at' => $r['sars_save'] ?: null,
                    ];
                });

                // Bulk-insert the chunk
                DB::table($targetTable)->insert($formattedRows->toArray());
            });
    }
}

// php artisan db:seed --class=Database\Seeders\SarsCov2SourceSeeder