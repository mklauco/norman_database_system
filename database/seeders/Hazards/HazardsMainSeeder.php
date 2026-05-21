<?php

namespace Database\Seeders\Hazards;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HazardsMainSeeder extends Seeder
{
    /**
     * Run the Hazards module seeds.
     */
    public function run(): void
    {
        $this->upsertDatabaseEntity();
        $this->upsertFactsheetEntities();

        $this->call([
            HazardsJanusSeeder::class,
            HazardsPikmeSeeder::class,
        ]);
    }

    private function upsertDatabaseEntity(): void
    {
        $now = now();
        $row = DB::table('database_entities')
            ->where('code', 'hazards')
            ->first();
        $values = [
            'name' => 'Hazards and Properties',
            'description' => 'CompTox hazards and properties placeholder entity',
            'image_path' => 'fas fa-triangle-exclamation',
            'code' => 'hazards',
            'dashboard_route_name' => 'hazardshome.index',
            'last_update' => null,
            'number_of_records' => 0,
            'parent_id' => null,
            'show_in_dashboard' => true,
            'has_templates' => false,
            'is_public' => true,
        ];

        if (! $row) {
            DB::table('database_entities')->insert($values + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        if ($this->rowMatches($row, $values)) {
            return;
        }

        DB::table('database_entities')
            ->where('id', $row->id)
            ->update($values + ['updated_at' => $now]);
    }

    private function upsertFactsheetEntities(): void
    {
        $now = now();
        $row = DB::table('factsheet_entities')
            ->where('name', 'PBT/vPvB & PMT/vPvM (NORMAN)')
            ->first();
        $values = [
            'name' => 'PBT/vPvB & PMT/vPvM (NORMAN)',
            'sort_order' => 8,
            'data' => json_encode([
                'method_of_presentation' => 'controller_method',
                'method' => 'getHazardsPbtPmtData',
            ]),
        ];

        if (! $row) {
            DB::table('factsheet_entities')->insert($values + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        if ($this->rowMatches($row, $values)) {
            return;
        }

        DB::table('factsheet_entities')
            ->where('id', $row->id)
            ->update($values + ['updated_at' => $now]);
    }

    private function rowMatches(object $row, array $values): bool
    {
        foreach ($values as $column => $value) {
            if ($this->normalizeValue($row->{$column} ?? null) !== $this->normalizeValue($value)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
