<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use App\Models\Backend\File;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Register the BlackSea Surface Water source file (id=10011) in the `files` table.
 *
 * One-file FileSeeder — scoped to the BlackSea SW pipeline only.
 * Called by EmpodatSuspectBlackSeaSurfaceWaterSeeder so the orchestrator doesn't
 * touch unrelated file rows.
 *
 * Idempotent (updateOrCreate by id).
 */
class EmpodatSuspectBlackSeaSurfaceWaterFileSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $file = File::updateOrCreate(
            ['id' => 10011],
            [
                'original_name' => 'DCT_SW_BlackSea2025_SS_NKUA_15042026_v1.xlsx',
                'name' => 'BlackSea 2025 Surface Water Suspect Screening Results',
                'description' => 'NKUA — BlackSea 2025 suspect screening, surface water matrix (ng/L). Includes HRMS identification metadata stored in empodat_suspect_metadata.',
                'file_path' => 'empodat_suspect/DCT_SW_BlackSea2025_SS_NKUA_15042026_v1.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'database_entity_id' => 18,
                'uploaded_at' => Carbon::now(),
                'is_deleted' => false,
            ]
        );

        $verb = $file->wasRecentlyCreated ? 'Created' : 'Updated';
        $this->command->info("{$verb} File ID {$file->id}: {$file->name}");
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectBlackSeaSurfaceWaterFileSeeder
