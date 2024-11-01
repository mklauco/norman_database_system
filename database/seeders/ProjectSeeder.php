<?php

namespace Database\Seeders;

use App\Models\Backend\Project;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProjectSeeder extends Seeder
{
    /**
    * Run the database seeds.
    */
    public function run(): void
    {
        //
        $project_abbreviations = [
            'jds4',
            'ufz',
            'lptc',
            'nl',
            'connect',
            'preempt',
            'dnieper',
            '6rbmp',
            'eu4emblas',
            'parc_pfas',
            'promisces',
        ];
        $now = now();
        foreach ($project_abbreviations as $abbreviation) {
            Project::insert([
                'name' => 'Project ' . strtoupper($abbreviation),
                'abbreviation' => strtoupper($abbreviation),
                'description' => 'This is project ' . ($abbreviation),
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }
    }
}
