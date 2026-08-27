<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use App\Models\Backend\File;
use App\Models\EmpodatSuspect\EmpodatSuspectDataSource;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Register the BlackSea SEDIMENT source file (id=10010) in the `files`
 * table, then write its provenance row in `empodat_suspect_data_source`.
 *
 * One-file FileSeeder — scoped to the BlackSea SEDIMENT pipeline only.
 * Called by EmpodatSuspectBlackSeaSedimentSeeder so the orchestrator doesn't
 * touch unrelated file rows.
 *
 * Idempotent (updateOrCreate by id / file_id). The provenance write must
 * happen after the `files` row is saved: `empodat_suspect_data_source` is
 * guarded by a trigger that rejects any file_id whose
 * files.database_entity_id is not 18.
 */
class EmpodatSuspectBlackSeaSedimentFileSeeder extends Seeder
{
    use WithoutModelEvents;

    private const int FILE_ID = 10010;

    /**
     * Type Data Source: Monitoring data.
     */
    private const int SOURCE_DATA_ID = 1;

    /**
     * Type Monitoring: Investigative.
     */
    private const int MONITORING_TYPE_ID = 3;

    /**
     * Organisation: Environmental Institute, s.r.o. (list_data_source_organisations.id = 1).
     */
    private const int ORGANISATION_ID = 1;

    /**
     * Laboratory: Laboratory of Analytical Chemistry (list_data_source_laboratories.id = 103).
     */
    private const int LABORATORY_ID = 103;

    public function run(): void
    {
        $file = File::updateOrCreate(
            ['id' => self::FILE_ID],
            [
                'original_name' => 'DCT_SEDIMENT_BlackSea2025_SS_NKUA_15042026_v1.xlsx',
                'name' => 'BlackSea 2025 SEDIMENT Suspect Screening Results',
                'description' => 'NKUA — BlackSea 2025 suspect screening, SEDIMENT matrix (dry weight, μg/kg dw). Includes HRMS identification metadata stored in empodat_suspect_metadata.',
                'file_path' => 'empodat_suspect/DCT_SEDIMENT_BlackSea2025_SS_NKUA_15042026_v1.xlsx',
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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectBlackSeaSedimentFileSeeder
