<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class SarsCov2Part1Seeder extends Seeder
{
    public function run(): void
    {
        // Disable query logging for large inserts
        DB::disableQueryLog();

        $filePath = base_path() . '/database/seeders/seeds_sars/sars1.xlsx';
        $targetTable = 'sars_cov_main';

        // Stream rows from the CSV in chunks of 1,000
        SimpleExcelReader::create($filePath)
            ->getRows()
            ->chunk(1000)
            ->each(function ($rowsChunk) use ($targetTable) {
                // Transform each row in the chunk into the structure you need
                $formattedRows = $rowsChunk->map(function ($r) {
                    return [
                        'id' => $r['sars_id'],
                        'type_of_data' => $r['type_of_data'] ?: null,
                        'data_provider' => $r['data_provider'] ?: null,
                        'contact_person' => $r['contact_person'] ?: null,
                        'address_of_contact' => $r['address_of_contact'] ?: null,
                        'email' => $r['email'] ?: null,
                        'laboratory' => $r['laboratory'] ?: null,
                        'name_of_country' => $r['name_of_country'] ?: null,
                        'name_of_city' => $r['name_of_city'] ?: null,
                        'station_name' => $r['station_name'] ?: null,
                        'national_code' => $r['national_code'] ?: null,
                        'relevant_ec_code_wise' => $r['relevant_ec_code_wise'] ?: null,
                        'relevant_ec_code_other' => $r['relevant_ec_code_other'] ?: null,
                        'other_code' => $r['other_code'] ?: null,
                        'latitude' => $r['latitude'] ?: null,
                        'latitude_d' => $r['latitude_d'] ?: null,
                        'latitude_m' => $r['latitude_m'] ?: null,
                        'latitude_s' => $r['latitude_s'] ?: null,
                        'latitude_decimal' => $r['latitude_decimal'] ?: null,
                        'longitude' => $r['longitude'] ?: null,
                        'longitude_d' => $r['longitude_d'] ?: null,
                        'longitude_m' => $r['longitude_m'] ?: null,
                        'longitude_s' => $r['longitude_s'] ?: null,
                        'longitude_decimal' => $r['longitude_decimal'] ?: null,
                        'altitude' => $r['altitude'] ?: null,
                        'design_capacity' => $r['design_capacity'] ?: null,
                        'population_served' => $r['population_served'] ?: null,
                        'catchment_size' => $r['catchment_size'] ?: null,
                        'gdp' => $r['gdp'] ?: null,
                        'people_positive' => $r['people_positive'] ?: null,
                        'people_recovered' => $r['people_recovered'] ?: null,
                        'people_positive_past' => $r['people_positive_past'] ?: null,
                        'people_recovered_past' => $r['people_recovered_past'] ?: null,
                        'sample_matrix' => $r['sample_matrix'] ?: null,
                        'sample_from_hour' => $r['sample_from_hour'] ?: null,
                        'sample_from_day' => $r['sample_from_day'] ?: null,
                        'sample_from_month' => $r['sample_from_month'] ?: null,
                        'sample_from_year' => $r['sample_from_year'] ?: null,
                        'sample_to_hour' => $r['sample_to_hour'] ?: null,
                        'sample_to_day' => $r['sample_to_day'] ?: null,
                        'sample_to_month' => $r['sample_to_month'] ?: null,
                        'sample_to_year' => $r['sample_to_year'] ?: null,
                        'type_of_sample' => $r['type_of_sample'] ?: null,
                        'type_of_composite_sample' => $r['type_of_composite_sample'] ?: null,
                        'sample_interval' => $r['sample_interval'] ?: null,
                        'flow_total' => $r['flow_total'] ?: null,
                        'flow_minimum' => $r['flow_minimum'] ?: null,
                        'flow_maximum' => $r['flow_maximum'] ?: null,
                        'temperature' => $r['temperature'] ?: null,
                        'cod' => $r['cod'] ?: null,
                        'total_n_nh4_n' => $r['total_n_nh4_n'] ?: null,
                        'tss' => $r['tss'] ?: null,
                        'dry_weather_conditions' => $r['dry_weather_conditions'] ?: null,
                        'last_rain_event' => $r['last_rain_event'] ?: null,
                        'associated_phenotype' => $r['associated_phenotype'] ?: null,
                        'genetic_marker' => $r['genetic_marker'] ?: null,
                        'date_of_sample_preparation' => $r['date_of_sample_preparation'] ?: null,
                        'storage_of_sample' => $r['storage_of_sample'] ?: null,
                        'volume_of_sample' => $r['volume_of_sample'] ?: null,
                        'internal_standard_used1' => $r['internal_standard_used1'] ?: null,
                        'method_used_for_sample_preparation' => $r['method_used_for_sample_preparation'] ?: null,
                        'date_of_rna_extraction' => $r['date_of_rna_extraction'] ?: null,
                        'method_used_for_rna_extraction' => $r['method_used_for_rna_extraction'] ?: null,
                        'internal_standard_used2' => $r['internal_standard_used2'] ?: null,
                        'rna1' => $r['rna1'] ?: null,
                        'rna2' => $r['rna2'] ?: null,
                        'replicates1' => $r['replicates1'] ?: null,
                        'analytical_method_type' => $r['analytical_method_type'] ?: null,
                        'analytical_method_type_other' => $r['analytical_method_type_other'] ?: null,
                        'date_of_analysis' => $r['date_of_analysis'] ?: null,
                        'lod1' => $r['lod1'] ?: null,
                        'lod2' => $r['lod2'] ?: null,
                        'loq1' => $r['loq1'] ?: null,
                        'loq2' => $r['loq2'] ?: null,
                        'uncertainty_of_the_quantification' => $r['uncertainty_of_the_quantification'] ?: null,
                        'efficiency' => $r['efficiency'] ?: null,
                        'rna3' => $r['rna3'] ?: null,
                        'pos_control_used' => $r['pos_control_used'] ?: null,
                        'replicates2' => $r['replicates2'] ?: null,
                        'ct' => $r['ct'] ?: null,
                        'gene1' => $r['gene1'] ?: null,
                        'gene2' => $r['gene2'] ?: null,
                        'comment' => $r['comment'] ?: null,
                        'latitude_decimal_show' => $r['longitude_decimal_show'] ?: null,
                        'longitude_decimal_show' => $r['latitude_decimal_show'] ?: null,
                        'sars_cov_file_upload_id' => $r['source_id'] ?: null,
                    ];
                });

                // Bulk-insert the chunk
                DB::table($targetTable)->insert($formattedRows->toArray());
            });
    }
}

// php artisan db:seed --class=Database\Seeders\SarsCov2Part1Seeder