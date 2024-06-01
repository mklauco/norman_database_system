<?php

namespace Database\Seeders;

use App\Models\DatabaseEntity;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseEntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DatabaseEntity::truncate();
        DatabaseEntity::insert([
        [
            'name'          => 'Substance Database',
            'description'   => 'A database of substances',
            'image_path'    => 'substance_database.webp',
            'code'          => 'susdat',
            'dashboard_route_name'    => 'substancies',
        ], [
            'name'          => 'Chemical Occurrence Data',
            'description'   => 'A database of chemical occurrences',
            'image_path'    => 'chemical_occurrence_data.webp',
            'code'          => 'empodat',
            'dashboard_route_name'    => 'route1',
        ], [
            'name'          => 'Ecotoxicology',
            'description'   => 'A database of ecotoxicology',
            'image_path'    => 'ecotoxicology.webp',
            'code'          => 'ecotox',
            'dashboard_route_name'    => 'route2',
        ]]);
    }
}
