<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| EMPODAT record field labels
|--------------------------------------------------------------------------
|
| Display labels for the columns rendered in the EMPODAT search-result
| record modal (issue #22). Without them the modal prints Title-Cased
| column names — "Sample Preparation Method Name", "Dpc Id" — instead of
| the wording the users know.
|
| The wording is taken verbatim from the legacy record view
| (POST https://www.norman-network.com/nds/empodat/ajaxChemicalDataDetail.php
| with `id`, which uses the same record ids as this system), so the same
| record reads the same way in both systems. Where legacy nests a field
| under a sub-heading — "Code" and "Number" under "Has standardised
| analytical method been used?" — the heading is folded into the label,
| because this modal renders a flat list.
|
| Keys are the physical column names, grouped by the modal section that
| renders them. Columns with no entry here fall back to the Title-Cased
| column name, so this file can be completed incrementally.
|
| Codelist columns are keyed by their `*_id` column even though the value
| shown is resolved from the `list_*` table: the resolution and the
| "Other" collapsing rule both key off the id column. See
| App\Services\Empodat\EmpodatRecordDisplay.
|
*/

return [

    'station' => [
        'name' => 'Station name',
        'country' => 'Country code',
        'national_name' => 'National code',
        'short_sample_code' => 'Short sample code',
        'sample_code' => 'Sample code',
        'provider_code' => 'Provider code',
        'code_ec_wise' => 'EC WISE code',
        'code_ec_other' => 'Other EC code',
        'code_other' => 'Other code',
        'specific_locations' => 'Specific location',
        'latitude' => 'Latitude coordinates',
        'longitude' => 'Longitude coordinates',
    ],

    'analytical_method' => [
        'lod' => 'Limit of Detection (LoD)',
        'loq' => 'Limit of Quantification (LoQ)',
        'uncertainty_loq' => 'Uncertainty at LoQ',
        'uncertainty_loq_range_min' => 'Uncertainty at LoQ - range min',
        'uncertainty_loq_range_max' => 'Uncertainty at LoQ - range max',
        'coverage_factor_id' => 'Coverage factor',
        'sample_preparation_method_id' => 'Sample preparation method',
        'analytical_method_id' => 'Analytical method',
        'standardised_method_id' => 'Has standardised analytical method been used? - Code',
        'standardised_method_number' => 'Has standardised analytical method been used? - Number',
        'validated_method_id' => 'Has the used method been validated according to one of the below protocols?',
        'corrected_recovery_id' => 'Have the results been corrected for extraction recovery?',
        'field_blank_id' => 'Was a field blank checked?',
        'iso_id' => 'Is the laboratory accredited according to ISO 17025?',
        'given_analyte_id' => 'Is the laboratory accredited for the given analyte?',
        'laboratory_participate_id' => 'Does the laboratory participate in interlaboratory studies for the given determinand?',
        'summary_performance_id' => 'Summary of performance of the laboratory in interlaboratory study for the given determinand',
        'control_charts_id' => 'Are control charts recorded for the given determinand?',
        'internal_standards_id' => 'Were there used any internal standards or isotopically labelled compounds?',
        'authority_id' => 'Are the data controlled by competent authority (apart from accreditation body)?',
        'sampling_method_id' => 'Sampling method',
        'sampling_collection_device_id' => 'Sampling collection device',
        'rating' => 'Data Quality Evaluation',
        'remark' => 'Remark',
        'foa' => 'Frequency of analysis',
    ],

    'data_source' => [
        'type_data_source_id' => 'Type of data source',
        'type_monitoring_id' => 'Type of monitoring',
        'data_accessibility_id' => 'Data accessibility',
        'project_title' => 'Title of project',
        'organisation_id' => 'Organisation',
        'laboratory1_id' => 'Laboratory',
        'laboratory2_id' => 'Laboratory 2',
        'author' => 'Author',
        'email' => 'E-mail',
        'reference1' => 'References / literature 1',
        'reference2' => 'References / literature 2',
        'data_source_url' => 'Website',
        'bibliographic_source' => 'Bibliographic source',
        'identifier_url' => 'Identifier URL',
        'orcid_no' => 'ORCID',
        'data_provider' => 'Data provider',
        'data_source_remark' => 'Remark',
    ],

    'minor' => [
        'dpc_id' => 'Precision of coordinates',
        'dcod_id' => 'Concentration data',
        'dst_id' => 'Sampling technique',
        'dplu_id' => 'Prevalent land use',
        'dtl_id' => 'Treatment less',
        'dtod_id' => 'Type of data',
        'dtos_id' => 'Type of sampling',
        'dmm_id' => 'Aggregation type',
        'altitude' => 'Altitude [m]',
        'matrix_other' => 'Matrix - further specification',
        'compound' => 'Compound as reported',
        'unit_extra' => 'Additional unit',
        'tier' => 'Tier',
        'sampling_technique' => 'Sampling technique as reported',
        'sampling_date' => 'Sampling date',
        'sampling_date_t' => 'Sampling time',
        'sampling_date_m' => 'Sampling month',
        'sampling_date_d' => 'Sampling day',
        'sampling_date1' => 'Sampling date (end)',
        'sampling_date1_t' => 'Sampling time (end)',
        'sampling_date1_y' => 'Sampling year (end)',
        'sampling_date1_m' => 'Sampling month (end)',
        'sampling_date1_d' => 'Sampling day (end)',
        'analysis_date_y' => 'Year of analysis',
        'analysis_date_m' => 'Month of analysis',
        'analysis_date_d' => 'Day of analysis',
        'sampling_duration_day' => 'Sampling duration [days]',
        'sampling_duration_hour' => 'Sampling duration [hours]',
        'description' => 'Description',
        'remark' => 'Remark',
        'remark_add' => 'Additional remark',
        'agg_uncertainty' => 'Aggregated data - uncertainty',
        'agg_max' => 'Aggregated data - maximum',
        'agg_min' => 'Aggregated data - minimum',
        'agg_number' => 'Aggregated data - number of samples',
        'agg_deviation' => 'Aggregated data - standard deviation',
    ],

    /*
    | Matrix metadata columns. The same physical column names are reused
    | across the `empodat_matrix_*` tables, so a single flat map covers all
    | matrix types.
    */
    'matrix' => [
        'dki_id' => 'Kingdom',
        'dph_id' => 'Phylum',
        'dcla_id' => 'Class',
        'dord_id' => 'Order',
        'dfam_id' => 'Family',
        'dspc_id' => 'Species',
        'diop_id' => 'Individual or pooled',
        'dcat_id' => 'Category',
        'dht_id' => 'Habitat type',
        'dmeas_id' => 'Basis of measurement',
        'dtiel_id' => 'Tissue element of species monitored',
        'dpr_id' => 'Proxy pressures',
        'dsgr_id' => 'Species group',
        'de_id' => 'Depth sampling type',
        'dps_id' => 'Particle size',
        'dgra_id' => 'Grain size distribution',
        'dsot_id' => 'Soil texture',
        'dcnps_id' => 'Conc. normalised (particle size)',
        'dtbu_id' => 'Treatment before use',
        'df_id' => 'Fraction',
        'effluent_influent_id' => 'Effluent/Influent',
        'dloca_id' => 'Location',
        'dsmo_id' => 'Sampling mode',
        'dscd_id' => 'Sampling collection device',
        'dsa_id' => 'Sampling method',
        'dtw_id' => 'Type of waste water',
        'dtp_id' => 'Type of treatment plant',
        'dtt_id' => 'Advanced treatment steps',
        'dss_id' => 'Type of sewage sludge',
        'name' => 'Name of river / estuary / lake / reservoir / sea',
        'basin_name' => 'River Basin / Sea Region Name',
        'km' => 'River km',
        'depth_m' => 'Depth [m]',
        'ph' => 'pH',
        'temperature' => 'Temperature [°C]',
        'conductivity' => 'Conductivity [µS/cm]',
        'carbon' => 'Organic carbon [%]',
        'total_carbon' => 'Total carbon [%]',
        'organic_carbon_content' => 'Organic carbon content [%]',
        'doc' => 'Dissolved organic carbon [mg/l]',
        'toc' => 'Total organic carbon [mg/l]',
        'spm' => 'Suspended particulate matter [mg/l]',
        'hardness' => 'Hardness [mg CaCO3/l]',
        'salinity_min' => 'Salinity - minimum',
        'salinity_mean' => 'Salinity - mean',
        'salinity_max' => 'Salinity - maximum',
        'dry_wet' => 'Dry Wet Ratio [%]',
        'water_content' => 'Water content of tissue [%]',
        'fat_content' => 'Fat content [%]',
        'wider_area' => 'Wider area',
        'type_industry' => 'Type of industry',
        'capacity' => 'Capacity [p.e.]',
        'flow' => 'Flow',
        'species' => 'Species',
        'species_name' => 'Species name (in Latin)',
        'biota_size' => 'Size [cm]',
        'biota_length' => 'Length [cm]',
        'biota_weight' => 'Weight [g]',
        'biota_weight_dry' => 'Dry weight [g]',
        'biota_sex' => 'Sex',
        'biota_age' => 'Age',
        'number_organisms' => 'Number of organisms used',
        'no_pooled_individuals' => 'Number of pooled individuals',
        'eunis_habitat_type' => 'EUNIS habitat type',
        'height_level' => 'Height level [m]',
        'ground_level' => 'Ground level [m]',
        'sea_level' => 'Sea level [m]',
        'barometric_pressure' => 'Barometric pressure',
        'humidity' => 'Humidity [%]',
        'wind_speed' => 'Wind speed',
        'wind_direction' => 'Wind direction',
        'flow_rate' => 'Flow rate',

        /*
        | Remaining matrix columns (issue #22). Without an entry these render
        | as Title-Cased column names — "Doc Mg Cl", "Tarsus Width". A unit is
        | given only where the stored values confirm it; the rest are left
        | unitless rather than guessed.
        |
        | The flat map is safe because no two columns sharing a label ever
        | appear on the same `empodat_matrix_*` table: `nh4` / `ammonium_nh4`
        | and `n_no3` / `nitrate_no3` are the surface-water and waste-water
        | spellings of the same determinand and never co-occur.
        */

        // Biota — specimen handling and biometry
        'species_alive' => 'Species alive',
        'was_species_alive' => 'Was the species alive',
        'was_species_euthanised' => 'Was the species euthanised',
        'receive_medical_treatment' => 'Did the specimen receive medical treatment',
        'cause_death' => 'Cause of death',
        'year_death' => 'Year of death',
        'agegroup' => 'Age group',
        'nutrition_condition' => 'Nutrition condition',
        'geographic_range' => 'Geographic range',
        'standardised_protocols' => 'Standardised protocols used',
        'time_freezing' => 'Time to freezing',
        'storage_temperature' => 'Storage temperature',
        'packing_material' => 'Packing material',
        'head_length' => 'Head length',
        'bill_length' => 'Bill length',
        'wing_length' => 'Wing length',
        'tarsus_length' => 'Tarsus length',
        'tarsus_width' => 'Tarsus width',

        // Soil
        'soil_type' => 'Soil type',
        'soil_texture' => 'Soil texture (as reported)',
        'dilution_factor' => 'Dilution factor',
        'bulk_density' => 'Bulk density',
        'ph_cacl2' => 'pH (CaCl2)',
        'ph_h2o' => 'pH (H2O)',
        'no_pooled_sub_samples' => 'Number of pooled sub-samples',
        'sample_wet_weight' => 'Sample wet weight',
        'sample_dry_weight' => 'Sample dry weight',

        // Suspended matter — as-reported values and transect end coordinates
        'spm_orig' => 'Suspended particulate matter (as reported)',
        'carbon_orig' => 'Organic carbon (as reported)',
        'distance' => 'Distance',
        'end_north_south' => 'End latitude - N/S',
        'end_latitude_d' => 'End latitude - degrees',
        'end_latitude_m' => 'End latitude - minutes',
        'end_latitude_s' => 'End latitude - seconds',
        'end_latitude_decimal' => 'End latitude (decimal)',
        'end_east_west' => 'End longitude - E/W',
        'end_longitude_d' => 'End longitude - degrees',
        'end_longitude_m' => 'End longitude - minutes',
        'end_longitude_s' => 'End longitude - seconds',
        'end_longitude_decimal' => 'End longitude (decimal)',

        // Waste water and sewage sludge — treatment plant operation
        'srt' => 'Sludge retention time (SRT)',
        'sludge_retention_time' => 'Sludge retention time',
        'reactor' => 'Reactor',
        'dry_matter' => 'Dry matter',
        'description_sampling' => 'Description of sampling',

        // Water body description
        'surface' => 'Surface',
        'horizon' => 'Horizon [m]',
        'ocean_sea_region_name' => 'Ocean / sea region name',
        'sampled_volume' => 'Sampled volume',

        // Supporting water chemistry
        'cod' => 'Chemical oxygen demand (COD)',
        'bod5' => 'Biochemical oxygen demand (BOD5)',
        'doc_mg_cl' => 'Dissolved organic carbon [mg C/l]',
        'o2_m' => 'Dissolved oxygen [mg/l]',
        'o2_p' => 'Oxygen saturation [%]',
        'dissolved_o2' => 'Dissolved oxygen',
        'alkalinity' => 'Alkalinity',
        'spm_conc' => 'Suspended particulate matter concentration',
        'tss' => 'Total suspended solids (TSS)',
        'n_total' => 'Total nitrogen',
        'p_total' => 'Total phosphorus',
        'n_no2' => 'Nitrite nitrogen (N-NO2)',
        'n_no3' => 'Nitrate nitrogen (N-NO3)',
        'nitrate_no3' => 'Nitrate nitrogen (N-NO3)',
        'p_po4' => 'Phosphate phosphorus (P-PO4)',
        'nh4' => 'Ammonium (NH4)',
        'ammonium_nh4' => 'Ammonium (NH4)',
        'po43' => 'Phosphate (PO4)',
        'orthophosphate_po43' => 'Orthophosphate (PO4)',
        'so42' => 'Sulfate (SO4)',
        'sulfates' => 'Sulfates',
        'hco3' => 'Bicarbonate (HCO3)',
        'cl' => 'Chloride (Cl)',
        'chlorides' => 'Chlorides',
        'h2s' => 'Hydrogen sulfide (H2S)',
        'calcium' => 'Calcium',
        'iron' => 'Iron',
        'magnesium' => 'Magnesium',
        'manganese' => 'Manganese',
    ],

];
