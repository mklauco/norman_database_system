<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use App\Models\Backend\File;
use App\Models\EmpodatSuspect\EmpodatSuspectDataSource;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Register the JDS5 BIOTA source file (id=10017) in the `files` table, then
 * write its provenance row in `empodat_suspect_data_source`.
 *
 * One-file FileSeeder — scoped to the JDS5 BIOTA pipeline only. Called by
 * EmpodatSuspectJds5BiotaSeeder so the orchestrator doesn't touch unrelated
 * file rows.
 *
 * Idempotent (looked up by id / file_id). The `files` row is written with
 * forceFill, not updateOrCreate: `id` is absent from File::$fillable, so mass
 * assignment silently discards it and a row that does not exist yet — which is
 * the case everywhere for 10017 — would be created on the broken files_id_seq
 * instead of the reserved id. The seeder is authoritative about which id this
 * source occupies, so it asserts the id it got before going on.
 *
 * The provenance write must happen after the `files` row is saved:
 * `empodat_suspect_data_source` is guarded by a trigger that rejects any
 * file_id whose files.database_entity_id is not 18.
 *
 * ⚠️ The provenance constants below are the module default — all 16 existing
 * suspect sources carry exactly 1 / 3 / 1 / 103. Laboratory 103 is "Laboratory
 * of Analytical Chemistry, Athens"; this file's name says the analysis was done
 * by EI, whose own laboratory row is id 1. Kept at 103 for consistency with
 * every other source; change both here and for the other 16 if that default is
 * ever revisited.
 */
class EmpodatSuspectJds5BiotaFileSeeder extends Seeder
{
    use WithoutModelEvents;

    private const int FILE_ID = 10017;

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
        $file = File::find(self::FILE_ID) ?? new File;
        $isNew = ! $file->exists;

        $file->forceFill([
            'id' => self::FILE_ID,
            'original_name' => 'SUSPECT_BIOTA_JDS5_EI_20260823.xlsx',
            'name' => 'JDS5 BIOTA Suspect Screening Results',
            'description' => 'Joint Danube Survey 5 — suspect screening, fish samples (biota matrix), analysed by the Environmental Institute. Includes HRMS identification metadata stored in empodat_suspect_metadata.',
            'file_path' => 'empodat_suspect/SUSPECT_BIOTA_JDS5_EI_20260823.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'database_entity_id' => 18,
            'uploaded_at' => Carbon::now(),
            'is_deleted' => false,
        ])->save();

        if ((int) $file->id !== self::FILE_ID) {
            throw new RuntimeException(
                'Refusing to continue: the files row landed on id '.$file->id.' instead of '.self::FILE_ID.'.'
            );
        }

        $verb = $isNew ? 'Created' : 'Updated';
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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectJds5BiotaFileSeeder
