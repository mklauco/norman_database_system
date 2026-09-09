<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Fills `list_sewage_sludges` from the legacy `data_sewage_sludge.csv` export (issue #22).
 *
 * Backs `empodat_matrix_sewage_sludge.dss_id`.
 *
 * Legacy ids are preserved 1:1, so the raw ids already stored on the
 * `empodat_matrix_*` columns resolve without remapping. Rows are written
 * with `upsert` on the primary key so a re-run is a no-op and corrects
 * drift instead of failing on a duplicate key.
 */
class ListSewageSludgeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/data_sewage_sludge.csv';
        $rows = SimpleExcelReader::create($path)->getRows();

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'id' => (int) $row['dss_id'],
                'name' => trim((string) $row['dss_name']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($payload === []) {
            $this->command->warn('data_sewage_sludge.csv is empty — nothing seeded into list_sewage_sludges.');

            return;
        }

        DB::table('list_sewage_sludges')->upsert($payload, ['id'], ['name', 'updated_at']);

        $this->command->info(sprintf('list_sewage_sludges: %d rows seeded.', count($payload)));
    }
}
