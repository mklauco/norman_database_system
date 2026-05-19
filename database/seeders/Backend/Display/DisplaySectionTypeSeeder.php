<?php

declare(strict_types=1);

namespace Database\Seeders\Backend\Display;

use App\Models\Backend\DisplaySectionType;
use Illuminate\Database\Seeder;

class DisplaySectionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectionTypes = [
            [
                'code' => 'main_record',
                'default_name' => 'Record Details',
                'description' => 'Main record data from the primary table',
                'default_header_bg_class' => 'bg-gray-300',
                'default_header_text_class' => 'text-gray-900',
                'default_row_even_class' => 'bg-slate-100',
                'default_row_odd_class' => 'bg-slate-200',
                'default_row_text_class' => 'text-gray-900',
            ],
            [
                'code' => 'location',
                'default_name' => 'Location Information',
                'description' => 'Geographic coordinates and station information',
                'default_header_bg_class' => 'bg-gray-300',
                'default_header_text_class' => 'text-gray-900',
                'default_row_even_class' => 'bg-emerald-50',
                'default_row_odd_class' => 'bg-emerald-100',
                'default_row_text_class' => 'text-emerald-900',
            ],
            [
                'code' => 'substance',
                'default_name' => 'Substance',
                'description' => 'Chemical substance/compound information',
                'default_header_bg_class' => 'bg-teal-600',
                'default_header_text_class' => 'text-white',
                'default_row_even_class' => 'bg-teal-50',
                'default_row_odd_class' => 'bg-teal-100',
                'default_row_text_class' => 'text-teal-900',
            ],
            [
                'code' => 'matrix',
                'default_name' => 'Sample Matrix',
                'description' => 'Sample matrix/medium information',
                'default_header_bg_class' => 'bg-teal-600',
                'default_header_text_class' => 'text-white',
                'default_row_even_class' => 'bg-teal-50',
                'default_row_odd_class' => 'bg-teal-100',
                'default_row_text_class' => 'text-teal-900',
            ],
            [
                'code' => 'country',
                'default_name' => 'Country',
                'description' => 'Country information',
                'default_header_bg_class' => 'bg-gray-300',
                'default_header_text_class' => 'text-gray-900',
                'default_row_even_class' => 'bg-slate-100',
                'default_row_odd_class' => 'bg-slate-200',
                'default_row_text_class' => 'text-gray-900',
            ],
            [
                'code' => 'data_source',
                'default_name' => 'Data Source',
                'description' => 'Organisation, laboratory, and contact information',
                'default_header_bg_class' => 'bg-gray-300',
                'default_header_text_class' => 'text-gray-900',
                'default_row_even_class' => 'bg-slate-100',
                'default_row_odd_class' => 'bg-slate-200',
                'default_row_text_class' => 'text-gray-900',
            ],
            [
                'code' => 'analytical_method',
                'default_name' => 'Analytical Method',
                'description' => 'Analytical method and quality assurance information',
                'default_header_bg_class' => 'bg-amber-600',
                'default_header_text_class' => 'text-white',
                'default_row_even_class' => 'bg-amber-50',
                'default_row_odd_class' => 'bg-amber-100',
                'default_row_text_class' => 'text-amber-900',
            ],
            [
                'code' => 'water_quality',
                'default_name' => 'Water Quality Parameters',
                'description' => 'Physical and chemical water quality parameters',
                'default_header_bg_class' => 'bg-cyan-600',
                'default_header_text_class' => 'text-white',
                'default_row_even_class' => 'bg-cyan-50',
                'default_row_odd_class' => 'bg-cyan-100',
                'default_row_text_class' => 'text-cyan-900',
            ],
            [
                'code' => 'concentration',
                'default_name' => 'Concentration Data',
                'description' => 'Measured concentration values and units',
                'default_header_bg_class' => 'bg-rose-600',
                'default_header_text_class' => 'text-white',
                'default_row_even_class' => 'bg-rose-50',
                'default_row_odd_class' => 'bg-rose-100',
                'default_row_text_class' => 'text-rose-900',
            ],
            [
                'code' => 'sampling',
                'default_name' => 'Sampling Information',
                'description' => 'Sampling dates, methods, and equipment',
                'default_header_bg_class' => 'bg-violet-600',
                'default_header_text_class' => 'text-white',
                'default_row_even_class' => 'bg-violet-50',
                'default_row_odd_class' => 'bg-violet-100',
                'default_row_text_class' => 'text-violet-900',
            ],
            [
                'code' => 'soil',
                'default_name' => 'Soil Information',
                'description' => 'Soil type, texture, and related information',
                'default_header_bg_class' => 'bg-amber-600',
                'default_header_text_class' => 'text-white',
                'default_row_even_class' => 'bg-amber-50',
                'default_row_odd_class' => 'bg-amber-100',
                'default_row_text_class' => 'text-amber-900',
            ],
            [
                'code' => 'remarks',
                'default_name' => 'Remarks',
                'description' => 'Additional remarks and notes',
                'default_header_bg_class' => 'bg-gray-400',
                'default_header_text_class' => 'text-gray-900',
                'default_row_even_class' => 'bg-gray-50',
                'default_row_odd_class' => 'bg-gray-100',
                'default_row_text_class' => 'text-gray-700',
            ],
        ];

        foreach ($sectionTypes as $type) {
            DisplaySectionType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
//   php artisan db:seed --class="Database\\Seeders\\Backend\\Display\\DisplaySectionTypeSeeder"
