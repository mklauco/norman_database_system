<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use App\Models\Backend\File;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Register the TerraChem RODENT source file (id=10014) in the `files` table.
 *
 * One-file FileSeeder — scoped to the TerraChem RODENT pipeline only.
 * Called by EmpodatSuspectTerraChemRodentSeeder so the orchestrator doesn't
 * touch unrelated file rows.
 *
 * Idempotent (updateOrCreate by id).
 */
class EmpodatSuspectTerraChemRodentFileSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $file = File::updateOrCreate(
            ['id' => 10014],
            [
                'original_name' => 'TerraChem rodent and predator samples 2026-06-03 upload ready.xlsx',
                'name' => 'TerraChem RODENT Suspect Screening Results',
                'description' => 'TerraChem — suspect screening, RODENT and predator samples (biota matrix). Includes HRMS identification metadata stored in empodat_suspect_metadata.',
                'file_path' => 'empodat_suspect/TerraChem rodent and predator samples 2026-06-03 upload ready.xlsx',
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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectTerraChemRodentFileSeeder
