<?php

namespace Database\Seeders\EmpodatSuspect;

use App\Models\Backend\File;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmpodatSuspectFileSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * Creates/updates File records for the 8 legacy Empodat Suspect data sources.
     * BlackSea (10009–10011), TerraChem (10012–10015), and any new sources have
     * their own dedicated per-source FileSeeders — e.g.
     * EmpodatSuspectBlackSeaSedimentFileSeeder. Each pipeline orchestrator calls
     * only the FileSeeder relevant to its source.
     */
    public function run(): void
    {
        $this->command->info('Creating/updating File records for Empodat Suspect legacy sources...');

        $files = [
            [
                'id' => 10001,
                'original_name' => 'OK_CONNECT 1_suspect screening results_ng g dry weight_1192 - SEDIMENT.xlsx',
                'name' => 'CONNECT 1 SEDIMENT Suspect Screening Results',
                'description' => 'CONNECT 1 suspect screening results - SEDIMENT data',
                'file_path' => 'empodat_suspect/OK_CONNECT 1_suspect screening results_ng g dry weight_1192 - SEDIMENT.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'database_entity_id' => 18,
            ],
            [
                'id' => 10002,
                'original_name' => 'OK_CONNECT 1_suspect screening results_ng g wet weight_1192 - BIOTA.xlsx',
                'name' => 'CONNECT 1 BIOTA Suspect Screening Results',
                'description' => 'CONNECT 1 suspect screening results - BIOTA data',
                'file_path' => 'empodat_suspect/OK_CONNECT 1_suspect screening results_ng g wet weight_1192 - BIOTA.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'database_entity_id' => 18,
            ],
            [
                'id' => 10003,
                'original_name' => 'OK_CONNECT 2_suspect screening results_ng g dry weight_1192 - SEDIMENTS.xlsx',
                'name' => 'CONNECT 2 SEDIMENTS Suspect Screening Results',
                'description' => 'CONNECT 2 suspect screening results - SEDIMENTS data',
                'file_path' => 'empodat_suspect/OK_CONNECT 2_suspect screening results_ng g dry weight_1192 - SEDIMENTS.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'database_entity_id' => 18,
            ],
            [
                'id' => 10004,
                'original_name' => 'OK_CONNECT 2_suspect screening results_ng g wet weight_1192 - BIOTA.xlsx',
                'name' => 'CONNECT 2 BIOTA Suspect Screening Results',
                'description' => 'CONNECT 2 suspect screening results - BIOTA data',
                'file_path' => 'empodat_suspect/OK_CONNECT 2_suspect screening results_ng g wet weight_1192 - BIOTA.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'database_entity_id' => 18,
            ],
            [
                'id' => 10005,
                'original_name' => 'OK_HELCOM PreEMPT_suspect screening results_ng g dry weight_1980 - SEDIMENTS.xlsx',
                'name' => 'HELCOM PreEMPT SEDIMENTS Suspect Screening Results',
                'description' => 'HELCOM PreEMPT suspect screening results - SEDIMENTS data',
                'file_path' => 'empodat_suspect/OK_HELCOM PreEMPT_suspect screening results_ng g dry weight_1980 - SEDIMENTS.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'database_entity_id' => 18,
            ],
            [
                'id' => 10006,
                'original_name' => 'OK_HELCOM PreEMPT_suspect screening results_ng g wet weight_1980 - BIOTA.xlsx',
                'name' => 'HELCOM PreEMPT BIOTA Suspect Screening Results',
                'description' => 'HELCOM PreEMPT suspect screening results - BIOTA data',
                'file_path' => 'empodat_suspect/OK_HELCOM PreEMPT_suspect screening results_ng g wet weight_1980 - BIOTA.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'database_entity_id' => 18,
            ],
            [
                'id' => 10007,
                'original_name' => 'OK_LIFE APEX_suspect screening results_ng g wet weight_333.csv',
                'name' => 'LIFE APEX Suspect Screening Results',
                'description' => 'LIFE APEX suspect screening results data',
                'file_path' => 'empodat_suspect/OK_LIFE APEX_suspect screening results_ng g wet weight_333.csv',
                'mime_type' => 'text/csv',
                'database_entity_id' => 18,
            ],
            [
                'id' => 10008,
                'original_name' => 'OK_UBA-HELCOM_suspect screening results_ng g wet weight_1204.xlsx',
                'name' => 'UBA-HELCOM Suspect Screening Results',
                'description' => 'UBA-HELCOM suspect screening results - BIOTA data',
                'file_path' => 'empodat_suspect/OK_UBA-HELCOM_suspect screening results_ng g wet weight_1204.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'database_entity_id' => 18,
            ],
        ];

        $now = Carbon::now();
        $created = 0;
        $updated = 0;

        foreach ($files as $fileData) {
            $file = File::updateOrCreate(
                ['id' => $fileData['id']],
                array_merge($fileData, [
                    'uploaded_at' => $now,
                    'is_deleted' => false,
                ])
            );

            if ($file->wasRecentlyCreated) {
                $created++;
                $this->command->info("Created File ID {$file->id}: {$file->name}");
            } else {
                $updated++;
                $this->command->info("Updated File ID {$file->id}: {$file->name}");
            }
        }

        $this->command->newLine();
        $this->command->info("Summary: {$created} files created, {$updated} files updated");
        $this->command->info('All Empodat Suspect File records are ready.');
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectFileSeeder
