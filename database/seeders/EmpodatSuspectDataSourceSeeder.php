<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Legacy bulk seeder — covers files.id 10001-10008 only.
 *
 * WARNING: This seeder performs a plain `insert()` for every file with
 * database_entity_id = 18 and has NO deduplication. It does not check
 * whether a file already has a row in `empodat_suspect_data_source` before
 * inserting another one. Current-generation sources (files.id 10009+) each
 * own their provenance row via their per-source `…FileSeeder` (see
 * database/seeders/EmpodatSuspect/*FileSeeder.php), which is idempotent
 * (`updateOrCreate` keyed on file_id).
 *
 * DO NOT re-run this seeder once per-file seeders exist for a source: it
 * will re-insert a duplicate row for every file it covers (10001-10008 as
 * well as any current-generation file it happens to match on
 * database_entity_id = 18), which the unique index on `file_id` added in
 * the 2026_08_27_060459_add_unique_index_to_empodat_suspect_data_source_file_id
 * migration will now reject outright.
 */
class EmpodatSuspectDataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Assigns default data source settings to all EMPODAT Suspect files (database_entity_id = 18):
     * - Type Data Source: Monitoring data (id: 1)
     * - Type Monitoring: Investigative (id: 3)
     * - Organisation: Environmental Institute (EI), Koš, Slovakia (id: 1)
     * - Laboratory: Laboratory of Analytical Chemistry, Athens, Greece (id: 103)
     */
    public function run(): void
    {
        // IDs from referenced tables
        $sourceDataId = 1;      // Monitoring data
        $monitoringTypeId = 3;  // Investigative
        $organisationId = 1;    // Environmental Institute (EI), Koš, Slovakia
        $laboratoryId = 103;    // Laboratory of Analytical Chemistry, Athens, Greece

        // Get all file IDs for EMPODAT Suspect (database_entity_id = 18)
        $fileIds = DB::table('files')
            ->where('database_entity_id', 18)
            ->pluck('id');

        if ($fileIds->isEmpty()) {
            $this->command->info('No files found with database_entity_id = 18. Skipping seeder.');

            return;
        }

        $this->command->info("Found {$fileIds->count()} files with database_entity_id = 18.");

        // Prepare records for insertion
        $records = $fileIds->map(fn (int $fileId): array => [
            'file_id' => $fileId,
            'source_data_id' => $sourceDataId,
            'monitoring_type_id' => $monitoringTypeId,
            'organisation_id' => $organisationId,
            'laboratory_id' => $laboratoryId,
        ])->toArray();

        // Insert in chunks to avoid memory issues
        $chunkSize = 1000;
        $chunks = array_chunk($records, $chunkSize);
        $totalChunks = count($chunks);

        foreach ($chunks as $index => $chunk) {
            DB::table('empodat_suspect_data_source')->insert($chunk);
            $this->command->info('Inserted chunk '.($index + 1).'/'.$totalChunks);
        }

        $this->command->info("Successfully seeded {$fileIds->count()} records into empodat_suspect_data_source.");
    }
}
