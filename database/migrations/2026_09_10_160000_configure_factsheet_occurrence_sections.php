<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Wires up the two factsheet sections that have stood empty (#26).
     *
     * "Environmental occurrence (all data)" and "Potential risk of exceedance
     * of lowest PNEC" carried no configuration at all, so they rendered
     * nothing. Their figures are now precomputed by
     * `FactsheetStatisticsController` and read back by the two controller
     * methods pointed at here.
     *
     * `FactsheetEntitySeeder` carries the same configuration for fresh
     * installs; this migration exists so databases that already hold section
     * rows pick it up without a seeder run.
     *
     * Only sections whose configuration is still empty are touched, so a
     * section someone has since configured by hand is left alone and the
     * migration is safe to re-run.
     */
    private const SECTIONS = [
        4 => 'getSurfaceWaterOccurrenceData',
        10 => 'getRiskOfExceedanceData',
    ];

    public function up(): void
    {
        foreach (self::SECTIONS as $sortOrder => $method) {
            $row = DB::table('factsheet_entities')->where('sort_order', $sortOrder)->first();

            if ($row === null) {
                continue;
            }

            $data = json_decode((string) $row->data, true);

            // Anything already configured is somebody's decision, not ours.
            if (! empty($data['method_of_presentation'])) {
                continue;
            }

            DB::table('factsheet_entities')
                ->where('id', $row->id)
                ->update([
                    'data' => json_encode([
                        'method_of_presentation' => 'controller_method',
                        'method' => $method,
                    ]),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        foreach (self::SECTIONS as $sortOrder => $method) {
            $row = DB::table('factsheet_entities')->where('sort_order', $sortOrder)->first();

            if ($row === null) {
                continue;
            }

            $data = json_decode((string) $row->data, true);

            // Only revert the configuration this migration put there.
            if (($data['method'] ?? null) !== $method) {
                continue;
            }

            DB::table('factsheet_entities')
                ->where('id', $row->id)
                ->update(['data' => json_encode([]), 'updated_at' => now()]);
        }
    }
};
