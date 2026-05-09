<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
