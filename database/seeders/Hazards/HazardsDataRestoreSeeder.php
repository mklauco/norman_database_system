<?php

declare(strict_types=1);

namespace Database\Seeders\Hazards;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Empties all 14 hazards tables (including the experts' votes) with one
 * TRUNCATE ... RESTART IDENTITY statement, without CASCADE, after a confirmation (default no).
 * Never touches the role 'hazards', the database_entities row or any non-hazards table.
 * Irreversible: reload with HazardsDataSeeder (votes are not recoverable).
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class="Database\Seeders\Hazards\HazardsDataRestoreSeeder" --force
 */
class HazardsDataRestoreSeeder extends Seeder
{
    /** @var list<string> */
    private const TABLES = [
        'hazards_api_runs',
        'hazards_comptox_payloads',
        'hazards_parse_runs',
        'hazards_comptox_detail_records',
        'hazards_comptox_property_records',
        'hazards_comptox_fate_records',
        'hazards_janus',
        'hazards_pikme',
        'hazards_comptox_substance_data',
        'hazards_derivation_selections',
        'hazards_derivation_metadata',
        'hazards_classification_votes',
        'hazards_substance_classifications',
        'hazards_classification_supports',
    ];

    public function run(): void
    {
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        if (! $this->command->confirm('Empty ALL '.count(self::TABLES).' hazards tables, including expert votes? This cannot be undone.', false)) {
            $this->command->info('Cancelled, nothing changed.');

            return;
        }

        DB::statement('TRUNCATE TABLE '.implode(', ', self::TABLES).' RESTART IDENTITY');

        $this->command->info('Emptied: '.implode(', ', self::TABLES).'.');
    }
}
