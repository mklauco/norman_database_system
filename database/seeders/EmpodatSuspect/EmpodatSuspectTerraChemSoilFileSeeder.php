<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use App\Models\Backend\File;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Register the TerraChem SOIL source file (id=10015) in the `files` table.
 *
 * One-file FileSeeder — scoped to the TerraChem SOIL pipeline only.
 * Called by EmpodatSuspectTerraChemSoilSeeder so the orchestrator doesn't
 * touch unrelated file rows.
 *
 * Idempotent (updateOrCreate by id).
 */
class EmpodatSuspectTerraChemSoilFileSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $file = File::updateOrCreate(
            ['id' => 10015],
            [
                'original_name' => 'TerraChem soil samples 2026-06-03 upload ready.xlsx',
                'name' => 'TerraChem SOIL Suspect Screening Results',
                'description' => 'TerraChem — suspect screening, SOIL matrix. Includes HRMS identification metadata stored in empodat_suspect_metadata.',
                'file_path' => 'empodat_suspect/TerraChem soil samples 2026-06-03 upload ready.xlsx',
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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectTerraChemSoilFileSeeder
