<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

/**
 * Registry of the 1:1-by-id tables the backfill copies after empodat_main.
 *
 * Deliberately NOT covered: dct_analysis_air_outdoor / _indoor / _emissions /
 * _workplace (PG has no empodat_matrix_* table for them; empodat_matrix_air was
 * loaded from dct_analysis_air_ambient only, see empodat_matrices_migration.md).
 */
final class BackfillTables
{
    /**
     * PG matrix table => [legacy source table, column spec]. Spec is `column:kind`,
     * kind i = integer, n = numeric/double, s = text; `id` is implicit.
     * Generated from information_schema of the PG target (2026-11-22).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const MATRIX_TABLES = [
        'empodat_matrix_water_surface' => [
            'dct_analysis_water_surface',
            'df_id:i,df_other:s,name:s,basin_name:s,km:s,dpr_id:i,dpr_other:s,de_id:i,'.
            'de_other:s,depth_m:s,surface:s,salinity_min:s,salinity_mean:s,salinity_max:s,'.
            'spm:s,ph:s,temperature:s,conductivity:s,doc:s,carbon:s,hardness:s,horizon:s,'.
            'doc_mg_cl:s,o2_m:s,o2_p:s,dcat_id:i,dcat_other:s,dtbu_id:i,p_total:s,'.
            'ocean_sea_region_name:s,flow:s,sampled_volume:s,p_po4:s,n_no2:s,n_no3:s,'.
            'n_total:s,alkalinity:s,nh4:s,dissolved_o2:s,cod:s,so42:s,hco3:s,toc:s,cl:s,'.
            'po43:s,calcium:s,iron:s,magnesium:s,manganese:s,chlorides:s,sulfates:s',
        ],
        'empodat_matrix_water_ground' => [
            'dct_analysis_water_ground',
            'df_id:i,df_other:s,name:s,basin_name:s,dpr_id:i,de_id:i,de_other:s,depth_m:s,'.
            'carbon:s,ph:s,temperature:s,spm_conc:s,conductivity:s,doc:s,hardness:s,o2_m:s,'.
            'o2_p:s,bod5:s,dcat_id:i,dcat_other:s,h2s:s,p_po4:s,n_no2:s,tss:s,p_total:s,'.
            'n_no3:s,n_total:s,alkalinity:n,nh4:n,dissolved_o2:n,cod:n,so42:n,hco3:n,toc:n,'.
            'cl:n,po43:n,calcium:n,iron:n,magnesium:n,manganese:n',
        ],
        'empodat_matrix_water_waste' => [
            'dct_analysis_water_waste',
            'df_id:i,basin_name:s,name:s,dpr_id:i,ph:s,temperature:s,carbon:s,hardness:s,'.
            'type_industry:s,capacity:s,flow:s,effluent_influent_id:i,'.
            'effluent_influent_other:s,dtw_id:i,dtp_id:i,dtp_other:s,dtt_id:i,dtt_other:s,'.
            'dry_matter:s,srt:s,reactor:s,dsa_id:i,dsa_other:s,de_id:i,de_other:s,depth_m:s,'.
            'sludge_retention_time:s,dcat_id:i,dcat_other:s,cod:n,toc:n,conductivity:n,'.
            'orthophosphate_po43:n,p_total:n,n_no2:n,nitrate_no3:n,ammonium_nh4:n,n_total:n,'.
            'bod5:n',
        ],
        'empodat_matrix_biota' => [
            'dct_analysis_biota',
            'name:s,basin_name:s,km:s,dpr_id:i,dsgr_id:i,dsgr_other:s,species:s,'.
            'species_name:s,species_alive:s,dmeas_id:i,dmeas_other:s,dtiel_id:i,dtiel_other:s,'.
            'biota_size:s,biota_length:s,biota_weight:s,biota_sex:s,biota_age:s,agegroup:s,'.
            'number_organisms:s,water_content:s,dry_wet:s,fat_content:s,nutrition_condition:i,'.
            'no_pooled_individuals:s,standardised_protocols:s,time_freezing:s,'.
            'storage_temperature:s,packing_material:s,geographic_range:s,was_species_alive:s,'.
            'receive_medical_treatment:s,was_species_euthanised:s,cause_death:s,year_death:s,'.
            'biota_weight_dry:s,dki_id:i,dph_id:i,dcla_id:i,dord_id:i,dfam_id:i,dspc_id:i,'.
            'diop_id:i,dcat_id:i,dcat_other:s,dht_id:i,eunis_habitat_type:s,head_length:s,'.
            'bill_length:s,wing_length:s,tarsus_length:s,tarsus_width:s',
        ],
        'empodat_matrix_sediments' => [
            'dct_analysis_sediments',
            'name:s,basin_name:s,km:s,dpr_id:i,de_id:i,depth_m:s,carbon:s,df_id:i,df_other:s,'.
            'dcat_id:i,dcat_other:s,dtbu_id:i,dtbu_other:s,total_carbon:s',
        ],
        'empodat_matrix_soil' => [
            'dct_analysis_soil',
            'basin_name:s,de_id:i,de_other:s,depth_m:s,ph:s,carbon:s,wider_area:s,dry_wet:s,'.
            'soil_type:s,soil_texture:s,dps_id:i,dgra_id:i,dgra_other:s,dilution_factor:s,'.
            'km:s,dsot_id:i,dsot_other:s,dcnps_id:i,dcat_id:i,dcat_other:s,dtbu_id:i,'.
            'dtbu_other:s,bulk_density:n,organic_carbon_content:n,ph_cacl2:n,ph_h2o:n,'.
            'dpr_id:i,dpr_other:s,eunis_habitat_type:s,no_pooled_sub_samples:s,'.
            'sample_wet_weight:s,sample_dry_weight:s,water_content:s,fat_content:s,'.
            'storage_temperature:s',
        ],
        'empodat_matrix_sewage_sludge' => [
            'dct_analysis_sewage_sludge',
            'basin_name:s,dpr_id:i,de_id:i,depth_m:s,ph:s,temperature:s,carbon:s,'.
            'type_industry:s,capacity:s,dtp_id:i,dtp_other:s,dtt_id:i,dtt_other:s,srt:s,'.
            'reactor:s,description_sampling:s,df_id:i,df_other:s,dss_id:i,dss_other:s,name:s,'.
            'cod:n,toc:n,conductivity:n,bod5:n,orthophosphate_po43:n,p_total:n,nitrate_no3:n,'.
            'ammonium_nh4:n,n_total:n,sludge_retention_time:n,dtbu_id:i,flow:n',
        ],
        'empodat_matrix_suspended_matter' => [
            'dct_analysis_spm',
            'name:s,basin_name:s,km:s,dpr_id:i,de_id:i,depth_m:s,spm:s,spm_orig:s,carbon:s,'.
            'carbon_orig:s,distance:s,end_east_west:i,end_longitude_d:s,end_longitude_m:s,'.
            'end_longitude_s:s,end_longitude_decimal:s,end_north_south:i,end_latitude_d:s,'.
            'end_latitude_m:s,end_latitude_s:s,end_latitude_decimal:s,df_id:i,df_other:s,'.
            'dsa_id:i,dsa_other:s,dtt_id:i,dtt_other:s,capacity:s,flow:n',
        ],
        'empodat_matrix_air' => [
            'dct_analysis_air_ambient',
            'temperature:s,height_level:s,barometric_pressure:s,humidity:s,wider_area:s,'.
            'sea_level:s,wind_speed:s,wind_direction:s,dloca_id:i,flow_rate:s,dsmo_id:i,'.
            'dscd_id:i,ground_level:s',
        ],
    ];

    /**
     * empodat_minor column spec (sampling_date_m / sampling_date_d are left NULL on
     * purpose, see EmpodatLegacyBackfillSeeder docblock).
     */
    public const MINOR_SPEC =
        'dpc_id:i,altitude:s,matrix_other:s,compound:i,dcod_id:i,unit_extra:s,tier:i,'.
        'sampling_technique:i,sampling_date:s,sampling_date_t:s,sampling_date1_y:s,'.
        'sampling_date1_m:s,sampling_date1_d:s,sampling_date1_t:s,sampling_date1:s,'.
        'analysis_date_y:s,analysis_date_m:s,analysis_date_d:s,sampling_duration_day:s,'.
        'sampling_duration_hour:s,description:s,remark:s,remark_add:s,show_date:s,'.
        'dtod_id:i,dtod_other:s,agg_uncertainty:s,dmm_id:i,agg_max:s,agg_min:s,'.
        'agg_number:s,agg_deviation:s,dtl_id:i,dtl_other:s,dst_id:i,dst_other:s,dtos_id:i,'.
        'dplu_id:i,noexport:i,list_id:i';

    /**
     * Every side table in execution order (minor first, then the matrices).
     *
     * @param  array<string, string|null>  $columnAliases  "pg_table.pg_column" => legacy column or null
     * @return list<TableSpec>
     */
    public static function all(array $columnAliases = []): array
    {
        $specs = [
            new TableSpec('empodat_minor', 'dct_analysis', self::MINOR_SPEC, self::aliasesFor('empodat_minor', $columnAliases)),
        ];

        foreach (self::MATRIX_TABLES as $pgTable => [$legacyTable, $spec]) {
            $specs[] = new TableSpec($pgTable, $legacyTable, $spec, self::aliasesFor($pgTable, $columnAliases));
        }

        return $specs;
    }

    /**
     * @param  array<string, string|null>  $columnAliases
     * @return array<string, string|null>
     */
    private static function aliasesFor(string $pgTable, array $columnAliases): array
    {
        $prefix = $pgTable.'.';
        $out = [];
        foreach ($columnAliases as $key => $legacyColumn) {
            if (str_starts_with($key, $prefix)) {
                $out[substr($key, strlen($prefix))] = $legacyColumn;
            }
        }

        return $out;
    }
}
