<?php

namespace Database\Seeders\Hazards;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class HazardsPikmeSeeder extends Seeder
{
    private const CHUNK_SIZE = 1000;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targetTable = 'hazards_pikme';
        $path = base_path('database/seeders/seeds/hazards/hazards_pikme.csv');
        $now = Carbon::now();
        $startTime = microtime(true);

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");
            return;
        }

        $this->command->info("Starting {$targetTable} seeding...");

        Schema::disableForeignKeyConstraints();

        try {
            DB::table($targetTable)->delete();

            $reader = SimpleExcelReader::create($path)
                ->useDelimiter(',')
                ->headersToSnakeCase(false);

            $reader->getRows()
                ->chunk(self::CHUNK_SIZE)
                ->each(function ($rows, $key) use ($targetTable, $now, $path, $startTime): void {
                    $chunkStartTime = microtime(true);
                    $records = [];

                    foreach ($rows as $row) {
                        $dtxid = $this->nullableString($row['dtxid'] ?? $row['dtxsid'] ?? null);
                        if ($dtxid === null) {
                            continue;
                        }

                        $records[] = [
                            'susdat_substance_id' => $this->nullableInt($row['susdat_substance_id'] ?? null),
                            'dtxid' => $dtxid,
                            'logkoc_pred_opera' => $this->nullableFloat($row['logkoc_pred_opera'] ?? null),
                            'koc_predrange_opera' => $this->nullableString($row['koc_predrange_opera'] ?? null),
                            'conf_index_koc_opera' => $this->nullableFloat($row['conf_index_koc_opera'] ?? null),
                            'ad_koc_opera' => $this->nullableString($row['ad_koc_opera'] ?? null),
                            'ad_index_koc_opera' => $this->nullableFloat($row['ad_index_koc_opera'] ?? null),
                            'logbcf_pred_opera' => $this->nullableFloat($row['logbcf_pred_opera'] ?? null),
                            'bcf_predrange_opera' => $this->nullableString($row['bcf_predrange_opera'] ?? null),
                            'conf_index_bcf_opera' => $this->nullableFloat($row['conf_index_bcf_opera'] ?? null),
                            'ad_bcf_opera' => $this->nullableString($row['ad_bcf_opera'] ?? null),
                            'ad_index_bcf_opera' => $this->nullableFloat($row['ad_index_bcf_opera'] ?? null),
                            'biodeg_loghalflife_pred_opera' => $this->nullableFloat($row['biodeg_loghalflife_pred_opera'] ?? null),
                            'biodeg_predrange_opera' => $this->nullableString($row['biodeg_predrange_opera'] ?? null),
                            'conf_index_biodeg_opera' => $this->nullableFloat($row['conf_index_biodeg_opera'] ?? null),
                            'ad_biodeg_opera' => $this->nullableString($row['ad_biodeg_opera'] ?? null),
                            'ad_index_biodeg_opera' => $this->nullableFloat($row['ad_index_biodeg_opera'] ?? null),
                            'loghl_pred_opera' => $this->nullableFloat($row['loghl_pred_opera'] ?? null),
                            'hl_predrange_opera' => $this->nullableString($row['hl_predrange_opera'] ?? null),
                            'conf_index_hl_opera' => $this->nullableFloat($row['conf_index_hl_opera'] ?? null),
                            'ad_hl_opera' => $this->nullableString($row['ad_hl_opera'] ?? null),
                            'source_file_name' => basename($path),
                            'imported_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (empty($records)) {
                        return;
                    }

                    DB::table($targetTable)->insert($records);

                    $chunkEndTime = microtime(true);
                    $chunkElapsedTime = round($chunkEndTime - $chunkStartTime, 2);
                    $totalElapsedTime = round($chunkEndTime - $startTime, 2);

                    $this->command->info("Processed chunk ".($key + 1)." with ".count($records)." PIKME records. Chunk time: {$chunkElapsedTime}s, Total elapsed: {$totalElapsedTime}s");
                });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->command->info("{$targetTable} seeding completed.");
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
