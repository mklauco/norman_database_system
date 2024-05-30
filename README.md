## Main notes
* sample seeder for seeding small tables: `SupportingTableSeeder.php`


## Exporting partial data
* Export without header:
```
SELECT id, sus_id
INTO OUTFILE '/path/to/csv'
    FIELDS TERMINATED BY ','
    ENCLOSED BY '"'
    LINES TERMINATED BY '\n'
FROM _full_dct_analysis
WHERE id < 20;
```
* Export with header:
```
(SELECT 'country', 'country_other', 'station_name', 'national_name', 'short_sample_code',
        'sample_code', 'provider_code', 'code_ec_wise', 'code_ec_other', 'code_other', 'longitude_decimal',
        'latitude_decimal', 'specific_locations')
UNION ALL
(SELECT DISTINCT country, country_other, station_name, national_name, short_sample_code, sample_code,
                 provider_code, code_ec_wise, code_ec_other, code_other, longitude_decimal, latitude_decimal,
                 specific_locations
 FROM _full_dct_analysis
  WHERE id < 20000000
)
INTO OUTFILE 'd:/db_testing/norman_single_column_files/dct_analysis_stations.csv'
    FIELDS TERMINATED BY ','
    ENCLOSED BY '"'
    LINES TERMINATED BY '\n';
```