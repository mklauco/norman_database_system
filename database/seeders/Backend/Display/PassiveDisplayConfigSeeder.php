<?php

declare(strict_types=1);

namespace Database\Seeders\Backend\Display;

use App\Models\Backend\DisplayColumn;
use App\Models\Backend\DisplaySection;
use App\Models\Backend\DisplaySectionType;
use App\Models\DatabaseEntity;
use Illuminate\Database\Seeder;

class PassiveDisplayConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $passiveEntity = DatabaseEntity::where('code', 'passive')->first();

        if (! $passiveEntity) {
            $this->command->error('Passive database entity not found. Please run DatabaseEntitySeeder first.');

            return;
        }

        $sectionTypes = DisplaySectionType::pluck('id', 'code')->toArray();

        $sections = $this->getSections($passiveEntity->id, $sectionTypes);

        foreach ($sections as $sectionData) {
            $columns = $sectionData['columns'] ?? [];
            unset($sectionData['columns']);

            $section = DisplaySection::updateOrCreate(
                [
                    'database_entity_id' => $passiveEntity->id,
                    'code' => $sectionData['code'],
                ],
                $sectionData
            );

            foreach ($columns as $order => $columnData) {
                $columnData['display_section_id'] = $section->id;
                $columnData['display_order'] = $columnData['display_order'] ?? ($order + 1) * 10;

                DisplayColumn::updateOrCreate(
                    [
                        'display_section_id' => $section->id,
                        'column_name' => $columnData['column_name'],
                    ],
                    $columnData
                );
            }
        }

        $this->command->info('Passive display configuration seeded successfully.');
    }

    /**
     * Get section definitions with their columns.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getSections(int $entityId, array $sectionTypes): array
    {
        return [
            // Section 1: Sample Basic Data (main record fields)
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['main_record'] ?? null,
                'code' => 'sample_basic',
                'name' => 'SAMPLE - BASIC DATA',
                'relationship' => null,
                'display_order' => 10,
                'columns' => [
                    ['column_name' => 'id', 'display_label' => 'Record ID', 'is_glance' => true, 'css_class' => 'font-mono'],
                    ['column_name' => 'station_name', 'display_label' => 'Station Name', 'is_glance' => true],
                    ['column_name' => 'name', 'display_label' => 'Name'],
                    ['column_name' => 'short_sample_code', 'display_label' => 'Short Sample Code', 'css_class' => 'font-mono'],
                    ['column_name' => 'sample_code', 'display_label' => 'Sample Code', 'css_class' => 'font-mono'],
                    ['column_name' => 'provider_code', 'display_label' => 'Provider Code', 'css_class' => 'font-mono'],
                    ['column_name' => 'national_code', 'display_label' => 'National Code', 'css_class' => 'font-mono'],
                    ['column_name' => 'code_ec_wise', 'display_label' => 'EC WISE Code', 'css_class' => 'font-mono'],
                    ['column_name' => 'code_ec_other', 'display_label' => 'EC Other Code', 'css_class' => 'font-mono'],
                    ['column_name' => 'code_other', 'display_label' => 'Other Code', 'css_class' => 'font-mono'],
                    ['column_name' => 'specific_locations', 'display_label' => 'Specific Locations'],
                    ['column_name' => 'title_of_project', 'display_label' => 'Title of Project'],
                    ['column_name' => 'basin_name_other', 'display_label' => 'Basin Name'],
                    ['column_name' => 'dts_other', 'display_label' => 'Type of Data Source'],
                    ['column_name' => 'dtm_other', 'display_label' => 'Type of Monitoring'],
                ],
            ],

            // Section 2: Location Information
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['location'] ?? null,
                'code' => 'location',
                'name' => 'LOCATION INFORMATION',
                'relationship' => null,
                'display_order' => 20,
                'columns' => [
                    ['column_name' => 'latitude_decimal', 'display_label' => 'Latitude', 'format_type' => 'coordinates'],
                    ['column_name' => 'longitude_decimal', 'display_label' => 'Longitude', 'format_type' => 'coordinates'],
                    ['column_name' => 'altitude', 'display_label' => 'Altitude [m]'],
                    ['column_name' => 'dpr_other', 'display_label' => 'Proxy for Pressures'],
                    ['column_name' => 'ds_passive_sampling_stretch', 'display_label' => 'Passive Sampling Stretch', 'is_glance' => true],
                    ['column_name' => 'ds_stretch_start_and_end', 'display_label' => 'Stretch Start and End', 'is_glance' => true],
                    ['column_name' => 'ds_latitude_start_point_decimal', 'display_label' => 'Start Point Latitude', 'format_type' => 'coordinates'],
                    ['column_name' => 'ds_longitude_start_point_decimal', 'display_label' => 'Start Point Longitude', 'format_type' => 'coordinates'],
                    ['column_name' => 'ds_latitude_end_point_decimal', 'display_label' => 'End Point Latitude', 'format_type' => 'coordinates'],
                    ['column_name' => 'ds_longitude_end_point_decimal', 'display_label' => 'End Point Longitude', 'format_type' => 'coordinates'],
                    ['column_name' => 'ds_altitude', 'display_label' => 'Stretch Altitude [m]'],
                    ['column_name' => 'ds_dpr_other', 'display_label' => 'Stretch Proxy for Pressures'],
                ],
            ],

            // Section 3: Sampling Information
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['sampling'] ?? null,
                'code' => 'sampling',
                'name' => 'SAMPLING INFORMATION',
                'relationship' => null,
                'display_order' => 30,
                'columns' => [
                    ['column_name' => 'matrix_other', 'display_label' => 'Sample Matrix', 'is_glance' => true],
                    ['column_name' => 'type_sampling_other', 'display_label' => 'Type of Sampling'],
                    ['column_name' => 'passive_sampler_other', 'display_label' => 'Passive Sampler', 'is_glance' => true],
                    ['column_name' => 'sampler_type_other', 'display_label' => 'Sampler Type', 'is_glance' => true],
                    ['column_name' => 'sampler_mass', 'display_label' => 'Sampler Mass [g]', 'format_type' => 'number', 'format_options' => ['decimals' => 4]],
                    ['column_name' => 'sampler_surface_area', 'display_label' => 'Sampler Surface Area [cm²]', 'format_type' => 'number'],
                    ['column_name' => 'date_sampling_start_day', 'display_label' => 'Sampling Start Day'],
                    ['column_name' => 'date_sampling_start_month', 'display_label' => 'Sampling Start Month'],
                    ['column_name' => 'date_sampling_start_year', 'display_label' => 'Sampling Start Year', 'is_glance' => true],
                    ['column_name' => 'exposure_time_days', 'display_label' => 'Exposure Time [days]', 'is_glance' => true],
                    ['column_name' => 'exposure_time_hours', 'display_label' => 'Exposure Time [hours]'],
                    ['column_name' => 'date_of_analysis', 'display_label' => 'Date of Analysis', 'format_type' => 'date', 'is_glance' => true],
                    ['column_name' => 'time_of_analysis', 'display_label' => 'Time of Analysis', 'format_type' => 'datetime'],
                    ['column_name' => 'p_a_exposure_time', 'display_label' => 'Additional Exposure Time'],
                    ['column_name' => 'p_a_cruise_dates', 'display_label' => 'Cruise Dates'],
                    ['column_name' => 'p_a_river_km', 'display_label' => 'River Km'],
                    ['column_name' => 'p_a_sampler_sheets_disks_nr', 'display_label' => 'Sampler Sheets/Disks Nr'],
                    ['column_name' => 'p_a_sample_code', 'display_label' => 'Additional Sample Code', 'css_class' => 'font-mono'],
                ],
            ],

            // Section 4: Concentration Data
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['concentration'] ?? null,
                'code' => 'concentration',
                'name' => 'ANALYTICAL RESULTS',
                'relationship' => null,
                'display_order' => 40,
                'columns' => [
                    ['column_name' => 'concentration_value', 'display_label' => 'Concentration', 'is_glance' => true, 'format_type' => 'number', 'format_options' => ['decimals' => 4]],
                    ['column_name' => 'unit', 'display_label' => 'Unit', 'is_glance' => true],
                    ['column_name' => 'orig_compound', 'display_label' => 'Original Compound Name'],
                    ['column_name' => 'orig_cas_no', 'display_label' => 'Original CAS No.', 'css_class' => 'font-mono'],
                    ['column_name' => 'p_determinand_id', 'display_label' => 'Determinand ID', 'css_class' => 'font-mono'],
                ],
            ],

            // Section 5: Water Quality Parameters
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['water_quality'] ?? null,
                'code' => 'water_quality',
                'name' => 'WATER QUALITY PARAMETERS',
                'relationship' => null,
                'display_order' => 50,
                'is_collapsible' => true,
                'is_collapsed_default' => true,
                'columns' => [
                    ['column_name' => 'ph', 'display_label' => 'pH'],
                    ['column_name' => 'temperature', 'display_label' => 'Temperature [°C]'],
                    ['column_name' => 'spm_conc', 'display_label' => 'SPM Concentration'],
                    ['column_name' => 'salinity', 'display_label' => 'Salinity'],
                    ['column_name' => 'doc', 'display_label' => 'DOC'],
                    ['column_name' => 'hardness', 'display_label' => 'Hardness'],
                    ['column_name' => 'o2_1', 'display_label' => 'O₂ (1)'],
                    ['column_name' => 'o2_2', 'display_label' => 'O₂ (2)'],
                    ['column_name' => 'bod5', 'display_label' => 'BOD5'],
                    ['column_name' => 'h2s', 'display_label' => 'H₂S'],
                    ['column_name' => 'p_po4', 'display_label' => 'P-PO₄'],
                    ['column_name' => 'n_no2', 'display_label' => 'N-NO₂'],
                    ['column_name' => 'tss', 'display_label' => 'TSS'],
                    ['column_name' => 'p_total', 'display_label' => 'P Total'],
                    ['column_name' => 'n_no3', 'display_label' => 'N-NO₃'],
                    ['column_name' => 'n_total', 'display_label' => 'N Total'],
                ],
            ],

            // Section 6: Substance (relationship)
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['substance'] ?? null,
                'code' => 'substance',
                'name' => 'COMPOUND',
                'relationship' => 'substance',
                'display_order' => 60,
                'columns' => [
                    ['column_name' => 'name', 'display_label' => 'NORMAN Substance Name', 'is_glance' => true],
                    ['column_name' => 'code', 'display_label' => 'NORMAN Substance ID', 'css_class' => 'font-mono', 'link_route' => 'substances.show', 'link_param' => 'substance'],
                    ['column_name' => 'stdinchikey', 'display_label' => 'StdInChIKey', 'css_class' => 'font-mono'],
                    ['column_name' => 'cas_number', 'display_label' => 'CAS No.', 'css_class' => 'font-mono'],
                ],
            ],

            // Section 7: Matrix (relationship)
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['matrix'] ?? null,
                'code' => 'matrix',
                'name' => 'SAMPLE MATRIX',
                'relationship' => 'matrix',
                'display_order' => 70,
                'columns' => [
                    ['column_name' => 'name', 'display_label' => 'Matrix Name', 'is_glance' => true],
                    ['column_name' => 'description', 'display_label' => 'Description'],
                ],
            ],

            // Section 8: Country (relationship)
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['country'] ?? null,
                'code' => 'country',
                'name' => 'COUNTRY',
                'relationship' => 'country',
                'display_order' => 80,
                'columns' => [
                    ['column_name' => 'name', 'display_label' => 'Country Name', 'is_glance' => true],
                    ['column_name' => 'abbreviation', 'display_label' => 'Country Code', 'css_class' => 'font-mono'],
                ],
            ],

            // Section 9: Organisation (relationship)
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['data_source'] ?? null,
                'code' => 'organisation',
                'name' => 'DATA SOURCE INFORMATION',
                'relationship' => 'organisation',
                'display_order' => 90,
                'columns' => [
                    ['column_name' => 'org_name', 'display_label' => 'Organisation Name', 'is_glance' => true],
                    ['column_name' => 'org_city', 'display_label' => 'Organisation City'],
                    ['column_name' => 'org_country', 'display_label' => 'Organisation Country'],
                    ['column_name' => 'org_lab1_name', 'display_label' => 'Laboratory Name'],
                    ['column_name' => 'org_lab1_city', 'display_label' => 'Laboratory City'],
                    ['column_name' => 'org_lab1_country', 'display_label' => 'Laboratory Country'],
                    ['column_name' => 'org_lab2_name', 'display_label' => 'Laboratory 2 Name'],
                    ['column_name' => 'org_lab2_city', 'display_label' => 'Laboratory 2 City'],
                    ['column_name' => 'org_lab2_country', 'display_label' => 'Laboratory 2 Country'],
                    ['column_name' => 'org_family_name', 'display_label' => 'Contact Family Name'],
                    ['column_name' => 'org_first_name', 'display_label' => 'Contact First Name'],
                    ['column_name' => 'org_email', 'display_label' => 'Contact E-mail'],
                ],
            ],

            // Section 10: Analytical Method (relationship)
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['analytical_method'] ?? null,
                'code' => 'analytical_method',
                'name' => 'ANALYTICAL METHODS',
                'relationship' => 'analyticalMethod',
                'display_order' => 100,
                'columns' => [
                    ['column_name' => 'am_detection_limit', 'display_label' => 'Limit of Detection (LoD) [µg/sampler]', 'format_type' => 'number', 'format_options' => ['decimals' => 4]],
                    ['column_name' => 'am_quantification_limit', 'display_label' => 'Limit of Quantification (LoQ) [µg/sampler]', 'format_type' => 'number', 'format_options' => ['decimals' => 4]],
                    ['column_name' => 'am_unit', 'display_label' => 'Unit'],
                    ['column_name' => 'dpm_other', 'display_label' => 'Analytical Method Name'],
                    ['column_name' => 'dam_other', 'display_label' => 'Sample Preparation Method'],
                    ['column_name' => 'dsm_other', 'display_label' => 'Standardised Analytical Method'],
                    ['column_name' => 'dsm_number', 'display_label' => 'Method Number', 'css_class' => 'font-mono'],
                    ['column_name' => 'am_extraction_recovery_correction', 'display_label' => 'Extraction Recovery Correction'],
                    ['column_name' => 'am_field_blank_check', 'display_label' => 'Field Blank Checked'],
                    ['column_name' => 'am_lab_iso17025', 'display_label' => 'Laboratory ISO 17025 Accredited'],
                    ['column_name' => 'am_lab_accredited', 'display_label' => 'Laboratory Accredited for Analyte'],
                    ['column_name' => 'am_interlab_studies', 'display_label' => 'Interlaboratory Studies Participation'],
                    ['column_name' => 'am_interlab_summary', 'display_label' => 'Interlaboratory Studies Summary'],
                    ['column_name' => 'am_control_charts', 'display_label' => 'Control Charts Recorded'],
                    ['column_name' => 'am_authority_control', 'display_label' => 'Authority Control'],
                    ['column_name' => 'am_remark', 'display_label' => 'Method Remarks'],
                ],
            ],

            // Section 11: Remarks
            [
                'database_entity_id' => $entityId,
                'section_type_id' => $sectionTypes['remarks'] ?? null,
                'code' => 'remarks',
                'name' => 'REMARKS',
                'relationship' => null,
                'display_order' => 110,
                'is_collapsible' => true,
                'is_collapsed_default' => true,
                'columns' => [
                    ['column_name' => 'remark_1', 'display_label' => 'Remark 1'],
                    ['column_name' => 'remark_2', 'display_label' => 'Remark 2'],
                ],
            ],
        ];
    }
}
//  php artisan db:seed --class="Database\\Seeders\\Backend\\Display\\PassiveDisplayConfigSeeder"
