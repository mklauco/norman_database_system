## Main notes
* connection to previous MariaDB is done via additional `.env` parameters
```
DB_DATABASE_MARIADB=norman
DB_USERNAME_MARIADB=root
DB_PASSWORD_MARIADB=root
DB_PORT_MARIADB=3306
DB_HOST_MARIADB=127.0.0.1
```
and by specifying the connection name `$connection = norman-mariadb` see line 62 in `config/database.php` and model `Models/MariaDB/*`
* 
* Databases to migrate:

| MariaDB - db name | prefix in Postgre |
| ----------------- | ----------------- |
| `bacteria   `     | ``                |
| `bioactivity`     | ``                |
| `bioassay   `     | ``                |
| `ecotox     `     | ``                |
| `empodat    `     | `empodat_`        |
| `factsheets `     | ``                |
| `indoor     `     | ``                |
| `passive    `     | ``                |
| `sars       `     | ``                |
| `sle        `     | ``                |
| `susdat     `     | `susdat_`         |
| `suspect    `     | ``                |
| `nds - users`     | ``                |

* sample seeder for seeding small tables: `SupportingTableSeeder.php`

## Naming convention
* database prefixes are specified in previous section, and in the Postgre setup will be in one database
* Naming of migrators is: `MariadbnameTablenameMigrator`, e.g. `SusdatSusdatMigrator` or `EmpodatDCTAnalysisMigrator`

## Migrators from database (not CSV)
* update `composer.json`, key `autoload` with entry `"Database\\Seeders\\Migrators\\": "database/seeders/migrators/"`
* manually run `php artisan db:seed --class=Database\Seeders\migrators\YYY`


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

## Issues during migrations:
* `susdat` is missing `id=8`, but `susdat_category_join` has this entry