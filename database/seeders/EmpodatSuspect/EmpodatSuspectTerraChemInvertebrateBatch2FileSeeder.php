<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use App\Models\Backend\File;
use App\Models\EmpodatSuspect\EmpodatSuspectDataSource;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Register the TerraChem INVERTEBRATE 2nd batch source file (id=10016) in the
 * `files` table, then write its provenance row in `empodat_suspect_data_source`.
 *
 * One-file FileSeeder — scoped to the TerraChem INVERTEBRATE 2nd batch pipeline
 * only. Called by EmpodatSuspectTerraChemInvertebrateBatch2Seeder so the
 * orchestrator doesn't touch unrelated file rows.
 *
 * Idempotent (updateOrCreate by id / file_id). The provenance write must
 * happen after the `files` row is saved: `empodat_suspect_data_source` is
 * guarded by a trigger that rejects any file_id whose
 * files.database_entity_id is not 18.
 *
 * ⚠️ On the development database a row 10016 already exists from a web upload
 * (user 545, 2026-08-17) whose original_name and file_path both carry a
 * "FINAL" suffix the physical file never had. updateOrCreate rewrites both to
 * the real on-disk name below. Columns this seeder does not list —
 * is_protected, uploaded_by, number_of_records, main_id_from/main_id_to — are
 * deliberately left as they are; the id ranges are filled by the web UI's
 * Rescan after the import, not here.
 */
class EmpodatSuspectTerraChemInvertebrateBatch2FileSeeder extends Seeder
{
    use WithoutModelEvents;

    private const int FILE_ID = 10016;

    /**
     * Type Data Source: Monitoring data.
     */
    private const int SOURCE_DATA_ID = 1;

    /**
     * Type Monitoring: Investigative.
     */
    private const int MONITORING_TYPE_ID = 3;

    /**
     * Organisation: Environmental Institute (EI), Koš, Slovakia (list_data_source_organisations.id = 1).
     */
    private const int ORGANISATION_ID = 1;

    /**
     * Laboratory: Laboratory of Analytical Chemistry, Athens, Greece (list_data_source_laboratories.id = 103).
     */
    private const int LABORATORY_ID = 103;

    public function run(): void
    {
        $file = File::updateOrCreate(
            ['id' => self::FILE_ID],
            [
                'original_name' => 'TerraChem Invertebrates 2nd Batch.xlsx',
                'name' => 'TerraChem INVERTEBRATE 2nd Batch Suspect Screening Results',
                'description' => 'TerraChem — suspect screening, INVERTEBRATE samples, 2nd batch (biota matrix). Includes HRMS identification metadata stored in empodat_suspect_metadata.',
                'file_path' => 'empodat_suspect/TerraChem Invertebrates 2nd Batch.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'database_entity_id' => 18,
                'uploaded_at' => Carbon::now(),
                'is_deleted' => false,
            ]
        );

        $verb = $file->wasRecentlyCreated ? 'Created' : 'Updated';
        $this->command->info("{$verb} File ID {$file->id}: {$file->name}");

        EmpodatSuspectDataSource::updateOrCreate(
            ['file_id' => self::FILE_ID],
            [
                'source_data_id' => self::SOURCE_DATA_ID,
                'monitoring_type_id' => self::MONITORING_TYPE_ID,
                'organisation_id' => self::ORGANISATION_ID,
                'laboratory_id' => self::LABORATORY_ID,
            ]
        );

        $this->command->info("Ensured empodat_suspect_data_source provenance for File ID {$file->id}.");
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectTerraChemInvertebrateBatch2FileSeeder
