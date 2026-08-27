<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Populate `empodat_suspect_substances` from BlackSea BIOTA.
 *
 * One row per unique (NORMAN_ID, Name) pair found in the source file,
 * with file_id = 10009. Preserves NKUA's source spelling of `Name`, which can
 * differ from `susdat_substances.name` — useful for triage when NORMAN_ID
 * doesn't resolve.
 *
 * Idempotent: rows existing for (norman_id, file_id) are skipped.
 *
 * See: Empodat-Suspect-3-new-source.md §1 (substance identifier column)
 */
class EmpodatSuspectBlackSeaBiotaSubstancesSeeder extends Seeder
{
    use WithoutModelEvents;

    protected const FILE_ID = 10009;

    protected const FILE_NAME = 'DCT_BIOTA_BlackSea2025_SS_NKUA_15042026_v1.xlsx';

    public function run(): void
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', '7200');

        $path = storage_path('app/public/empodat_suspect/'.self::FILE_NAME);
        if (! file_exists($path)) {
            $this->command->error("Source file not found: {$path}");

            return;
        }

        $this->command->info('Streaming BlackSea BIOTA substances (file_id='.self::FILE_ID.')...');

        $existing = DB::table('empodat_suspect_substances')
            ->where('file_id', self::FILE_ID)
            ->pluck('norman_id')
            ->map(fn (?string $v): string => (string) $v)
            ->flip()
            ->all();

        $reader = SimpleExcelReader::create($path);
        $substances = [];
        $rowCount = 0;
        $skippedEmpty = 0;
        $startTime = microtime(true);

        DB::beginTransaction();

        try {
            foreach ($reader->getRows() as $row) {
                $normanId = trim((string) ($row['NORMAN_ID'] ?? ''));
                $name = trim((string) ($row['Name'] ?? ''));

                if ($normanId === '' || $name === '') {
                    $skippedEmpty++;

                    continue;
                }

                $key = $normanId.'|'.$name;
                if (! isset($substances[$key]) && ! isset($existing[$normanId])) {
                    $substances[$key] = [
                        'norman_id' => $normanId,
                        'name' => $name,
                        'file_id' => self::FILE_ID,
                    ];
                }

                $rowCount++;
                if ($rowCount % 5000 === 0) {
                    $this->command->info("  processed {$rowCount} rows, "
                        .count($substances).' new substances queued...');
                    gc_collect_cycles();
                }
            }

            if (! empty($substances)) {
                $chunks = array_chunk(array_values($substances), 500);
                foreach ($chunks as $chunk) {
                    DB::table('empodat_suspect_substances')->insert($chunk);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $totalTime = round(microtime(true) - $startTime, 2);
        $this->command->info("Done in {$totalTime}s — processed {$rowCount} rows, inserted "
            .count($substances).' new substances'
            .($skippedEmpty > 0 ? " (skipped {$skippedEmpty} rows with empty NORMAN_ID or Name)" : '')
            .'.');
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectBlackSeaBiotaSubstancesSeeder
