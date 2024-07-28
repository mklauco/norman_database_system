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
* getting concatenated ids from joins:
  ```
        $substances = DB::select("SELECT
      t.id,
        STRING_AGG(
            susdat_category_substance.category_id :: text,
            '|'
            ORDER BY
              category_id
          ) AS category_ids
        FROM
        (select
          susdat_substances.id as id
        from
          susdat_substances
          inner join susdat_category_substance on susdat_substances.id = susdat_category_substance.substance_id
        where
          susdat_category_substance.category_id in (5)
        group by
          susdat_substances.id
        order by
          id asc) t
        JOIN susdat_category_substance on t.id = susdat_category_substance.substance_id
        group by
          t.id");
  ```

## Issues during migrations:
* `susdat` is missing `id=8`, but `susdat_category_join` has this entry

## some other notes:
* running `pg_export` and `pg_restore` greatly increases seeding DB with known structure/data
* use path `C:/Program Files/PostgreSQL/16/bin/pg_dump.exe` or add it in PATH in environment variables
* to ease up direct CSV seeding by `psql` command, store config file here `c:\Users\mk\AppData\Roaming\postgresql\pgpass.conf` with `localhost:5432:norman:postgres:root`
* ```
pg_dump -h 127.0.0.1 -U postgres -d norman -F c -b -v -f "d:\norman_database_current_export\norman_db_2024-07-28.backup"

pg_restore -h 138.197.182.100 -U app -d app --clean --no-owner -v "d:\norman_database_current_export\norman_db_2024-07-28.backup"

pg_restore -h target_host -U target_user -d target_db --clean  -v /path/to/backup/source_db.backup

```