<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use Throwable;

/**
 * Checks that run before anything else, in every mode. Any FAIL aborts. A
 * failure to connect, a schema drift on either side, a read-only or
 * under-privileged PG user, or another import run in progress all stop the
 * seeder before it reads a single data row. Passwords are never printed.
 */
final class Preflight
{
    private const PG_EXPECTED = [
        'empodat_main' => 'id:i,station_id:i,matrix_id:i,substance_id:i,sampling_date_year:i,'
            .'concentration_indicator_id:i,concentration_value:n,method_id:i,data_source_id:i,country_id:i,file_id:i',
        'empodat_stations' => 'id:i,name:s,country_id:i,country_other_id:i,country:s,country_other:s,national_name:s,'
            .'short_sample_code:s,sample_code:s,provider_code:s,code_ec_wise:s,code_ec_other:s,code_other:s,'
            .'specific_locations:s,latitude:n,longitude:n,created_at:t,updated_at:t,is_deprecated:b',
        'files' => 'id:i,database_entity_id:i,name:s,original_name:s,description:s,doi:s,note:s,project_id:i,list_type:s,'
            .'matrice_dct:i,main_id_from:i,main_id_to:i,analysis_number:i,source_id_from:i,source_id_to:i,source_number:i,'
            .'method_id_from:i,method_id_to:i,method_number:i,is_deleted:b,is_protected:b,created_at:t,updated_at:t',
        'empodat_data_sources' => 'id:i,type_data_source_id:i,type_data_source_other:s,type_monitoring_id:i,'
            .'type_monitoring_other:s,data_accessibility_id:i,data_accessibility_other:s,project_title:s,organisation_id:i,'
            .'laboratory1_id:i,laboratory2_id:i,author:s,email:s,reference1:s,reference2:s,data_source_url:s,'
            .'bibliographic_source:s,identifier_url:s,orcid_no:s,data_provider:s,data_source_remark:s,created_at:t,updated_at:t',
        'empodat_analytical_methods' => 'id:i,lod:n,loq:n,uncertainty_loq:n,coverage_factor_id:i,sample_preparation_method_id:i,'
            .'sample_preparation_method_other:s,analytical_method_id:i,analytical_method_other:s,standardised_method_id:i,'
            .'standardised_method_other:s,standardised_method_number:s,validated_method_id:i,corrected_recovery_id:i,'
            .'field_blank_id:i,iso_id:i,given_analyte_id:i,legacy_given_analyte_id:i,laboratory_participate_id:i,'
            .'legacy_laboratory_participate_id:i,summary_performance_id:i,control_charts_id:i,internal_standards_id:i,'
            .'authority_id:i,rating:i,remark:s,foa:n,created_at:t,updated_at:t',
        'list_matrices' => 'id:i,title:s,subtitle:s,type:s,name:s,dct_name:s,unit:s,empodat_matrix_link:s,created_at:t,updated_at:t',
        'list_data_source_organisations' => 'id:i,name:s,acronym:s,city:s,country_id:i,legacy_data_organisation_id:i,created_at:t,updated_at:t',
        'list_data_source_laboratories' => 'id:i,name:s,city:s,country_id:i,created_at:t,updated_at:t',
        'list_countries' => 'id:i,code:s',
        'susdat_substances' => 'id:i,code:s',
        'database_entities' => 'id:i,code:s',
        'empodat_legacy_import_runs' => 'id:i,since_id:i,started_at:t,finished_at:t,status:s,notes:s',
        'empodat_legacy_import_log' => 'id:i,run_id:i,step_name:s,table_name:s,inserted_ids:j,id_min:i,id_max:i,'
            .'inserted_count:i,duration_ms:i,status:s,error:s,started_at:t,finished_at:t',
    ];

    /** Tables the run INSERTs into besides the TableSpec tables. */
    private const PG_WRITE_TABLES = [
        'files', 'list_matrices', 'empodat_data_sources', 'empodat_analytical_methods',
        'list_data_source_organisations', 'list_data_source_laboratories', 'empodat_stations', 'empodat_main',
        'empodat_legacy_import_runs', 'empodat_legacy_import_log',
    ];

    /** Sequences that must be usable (auto-id inserts). */
    private const PG_SEQUENCE_TABLES = [
        'empodat_stations', 'list_data_source_organisations', 'list_data_source_laboratories',
        'empodat_legacy_import_runs', 'empodat_legacy_import_log',
    ];

    /** Columns the audit writer fills. */
    private const AUDIT_RUN_COLUMNS = ['since_id', 'status', 'started_at', 'notes'];

    private const AUDIT_LOG_COLUMNS = [
        'run_id', 'step_name', 'table_name', 'inserted_ids', 'id_min', 'id_max', 'inserted_count',
        'duration_ms', 'status', 'error', 'started_at', 'finished_at',
    ];

    private const LEGACY_EXPECTED = [
        'dct_analysis' => [
            'id', 'sus_id', 'country', 'country_other', 'station_name', 'national_name', 'short_sample_code',
            'sample_code', 'provider_code', 'code_ec_wise', 'code_ec_other', 'code_other', 'longitude_decimal',
            'latitude_decimal', 'specific_locations', 'matrix', 'sampling_date_y', 'dic_id', 'concentration_value',
            'id_method', 'id_data',
        ],
        'dct_list' => [
            'list_id', 'list_title', 'list_file', 'list_description', 'list_doi', 'list_note', 'list_project_id',
            'list_type', 'matrice_dct', 'list_analysis_from', 'list_analysis_to', 'list_analysis_number',
            'list_source_from', 'list_source_to', 'list_source_number', 'list_method_from', 'list_method_to',
            'list_method_number', 'list_deleted', 'list_protected',
        ],
        'dct_data_source' => [
            'id_data', 'dts_id', 'dts_other', 'dtm_id', 'dtm_other', 'dda_id', 'dda_other', 'title_project',
            'organisation', 'laboratory_name', 'laboratory_city', 'laboratory_country', 'laboratory_name_2',
            'laboratory_city_2', 'laboratory_country_2', 'author', 'email', 'literature1', 'literature2',
            'data_source_link', 'bibliographic_source', 'identifier', 'orcid_no', 'data_provider', 'data_source_remark',
        ],
        'dct_analytical_method' => [
            'id_method', 'am_lod', 'am_loq', 'am_uncertainty_loq', 'dcf_id', 'dpm_id', 'dpm_other', 'dam_id',
            'dam_other', 'dsm_id', 'dsm_other', 'am_number', 'dp_id', 'am_corrected_recovery', 'am_field_blank',
            'am_iso', 'am_given_analyte', 'am_laboratory_participate', 'dsp_id', 'am_control_charts',
            'am_internal_standards', 'am_authority', 'rating', 'am_remark', 'am_foa',
        ],
        'data_matrice' => [
            'matrice_id', 'matrice_title1', 'matrice_title2', 'matrice_title3', 'matrice_title', 'matrice_dct_name', 'matrice_unit',
        ],
        'data_organisation' => ['id', 'organisation_name_en', 'acronym', 'city', 'country'],
    ];

    private const KIND_TYPES = [
        'i' => ['smallint', 'integer', 'bigint'],
        'n' => ['numeric', 'double precision', 'real'],
        's' => ['character varying', 'text', 'character'],
        't' => ['timestamp without time zone', 'timestamp with time zone'],
        'b' => ['boolean'],
        'j' => ['jsonb', 'json'],
    ];

    /** @var list<array{status: string, name: string, detail: string}> */
    private array $results = [];

    /** @var array<string, array{bytes: int, rows: int}> */
    private array $sizes = [];

    /**
     * @param  list<TableSpec>  $specs
     */
    public function __construct(
        private readonly BackfillContext $ctx,
        private readonly array $specs,
    ) {}

    /**
     * Cheap identity query used by the execution guard before the full preflight.
     *
     * @return array{database: ?string, host: ?string, addr: ?string, port: ?string, user: ?string, version: ?string}
     */
    public function pgIdentity(): array
    {
        $row = $this->ctx->pg->selectOne(
            'SELECT current_database() AS db, current_user AS usr, host(inet_server_addr()) AS addr, '
            .'inet_server_port()::text AS port, version() AS version'
        );

        return [
            'database' => $row->db ?? null,
            'host' => (string) ($this->ctx->pg->getConfig('host') ?? ''),
            'addr' => $row->addr ?? null,
            'port' => $row->port ?? null,
            'user' => $row->usr ?? null,
            'version' => $row->version ?? null,
        ];
    }

    /**
     * @return array<string, array{bytes: int, rows: int}>
     */
    public function sizes(): array
    {
        return $this->sizes;
    }

    /**
     * Runs every check, prints them, and throws PreflightFailed on any FAIL.
     */
    public function run(bool $needsWriteAccess): void
    {
        $relaxWrites = ! $needsWriteAccess && $this->ctx->options->dryRunReadOnlyOk;

        $this->checkConnections();
        $this->checkTables();
        $this->checkPgColumns();
        $this->checkRequiredColumnsWritten();
        $this->checkLegacyColumns();
        $this->checkWriteCapability($relaxWrites);
        $this->checkRunningImports();
        $this->checkRuntime();
        $this->collectSizes();

        foreach ($this->results as $result) {
            $this->ctx->emit(sprintf('[%s] %s%s', $result['status'], $result['name'], $result['detail'] !== '' ? ': '.$result['detail'] : ''));
        }

        $failures = array_values(array_map(
            static fn (array $r): string => $r['name'].($r['detail'] !== '' ? ' ('.$r['detail'].')' : ''),
            array_filter($this->results, static fn (array $r): bool => $r['status'] === 'FAIL'),
        ));
        if ($failures !== []) {
            throw new PreflightFailed($failures);
        }
    }

    private function record(string $status, string $name, string $detail = ''): void
    {
        $this->results[] = ['status' => $status, 'name' => $name, 'detail' => $detail];
    }

    private function outcome(bool $ok, string $name, string $okDetail, string $failDetail, string $failStatus = 'FAIL'): void
    {
        $this->record($ok ? 'OK' : $failStatus, $name, $ok ? $okDetail : $failDetail);
    }

    private function attempt(string $name, callable $check): void
    {
        try {
            $check();
        } catch (Throwable $e) {
            $this->record('FAIL', $name, $e->getMessage());
        }
    }

    private function checkConnections(): void
    {
        $this->attempt('PostgreSQL target reachable', function (): void {
            $id = $this->pgIdentity();
            $this->record('OK', 'PostgreSQL target reachable', sprintf(
                'database=%s user=%s host=%s port=%s server_addr=%s',
                $id['database'],
                $id['user'],
                $id['host'] ?: '-',
                $id['port'] ?? '-',
                $id['addr'] ?? 'local socket',
            ));
            $this->record('INFO', 'PostgreSQL version', (string) $id['version']);
        });

        $this->attempt('Legacy MariaDB reachable', function (): void {
            $row = $this->ctx->legacy->selectOne('SELECT VERSION() AS version, DATABASE() AS db, CURRENT_USER() AS usr, @@port AS port, @@hostname AS host');
            $this->record('OK', 'Legacy MariaDB reachable', sprintf(
                'database=%s user=%s host=%s port=%s configured_host=%s',
                $row->db,
                $row->usr,
                $row->host,
                $row->port,
                (string) ($this->ctx->legacy->getConfig('host') ?? '-'),
            ));
            $this->record('INFO', 'MariaDB version', (string) $row->version);
            if (! str_contains(strtolower((string) $row->db), 'empodat')) {
                $this->record('FAIL', 'Legacy looks like legacy EMPODAT', 'DATABASE() is "'.$row->db.'", expected a name containing "empodat"');
            }
        });

        $this->attempt('Legacy MariaDB session is read-only', function (): void {
            $value = $this->ctx->legacy->selectOne('SELECT @@session.tx_read_only AS ro')->ro ?? null;
            $this->outcome((string) $value === '1', 'Legacy MariaDB session is read-only', 'tx_read_only=1, the server refuses any write on this session', 'tx_read_only='.var_export($value, true));
        });

        $this->attempt('Legacy MariaDB account privileges', function (): void {
            $grantee = "CONCAT('''', REPLACE(CURRENT_USER(), '@', '''@'''), '''')";
            $privileges = array_map(static fn (object $r): string => strtoupper((string) $r->privilege_type), $this->ctx->legacy->select(
                'SELECT DISTINCT privilege_type FROM information_schema.user_privileges WHERE grantee = '.$grantee
                .' UNION SELECT DISTINCT privilege_type FROM information_schema.schema_privileges WHERE grantee = '.$grantee.' AND table_schema = DATABASE()'
            ));
            $writeCapable = array_values(array_diff($privileges, ['SELECT', 'SHOW VIEW', 'USAGE', 'PROCESS', 'SHOW DATABASES', 'REPLICATION CLIENT']));
            $this->record(
                $writeCapable === [] ? 'OK' : 'WARN',
                'Legacy MariaDB account privileges',
                $writeCapable === []
                    ? 'read-only account ('.implode(', ', $privileges).')'
                    : 'the account could write ('.implode(', ', $writeCapable).'); writes are still blocked by the read-only session and the statement guard, but a SELECT-only account is the safer choice',
            );
        });

        if ($this->ctx->options->mode !== BackfillOptions::MODE_EXECUTE) {
            $this->attempt('PostgreSQL session is read-only', function (): void {
                $row = $this->ctx->pg->selectOne("SELECT current_setting('transaction_read_only') AS ro, current_setting('default_transaction_read_only') AS dro");
                $this->outcome(
                    $row->ro === 'on' && $row->dro === 'on',
                    'PostgreSQL session is read-only',
                    'transaction_read_only=on, the server refuses any write on this session',
                    sprintf('transaction_read_only=%s default_transaction_read_only=%s', $row->ro, $row->dro),
                );
            });
        }
    }

    /**
     * @return list<string>
     */
    private function legacyTableNames(): array
    {
        return array_values(array_unique([
            ...array_keys(self::LEGACY_EXPECTED),
            ...array_map(static fn (TableSpec $s): string => $s->legacyTable, $this->specs),
        ]));
    }

    /**
     * @return list<string>
     */
    private function pgTableNames(): array
    {
        return array_values(array_unique([
            ...array_keys(self::PG_EXPECTED),
            ...array_map(static fn (TableSpec $s): string => $s->pgTable, $this->specs),
        ]));
    }

    private function checkTables(): void
    {
        $this->attempt('Legacy tables exist', function (): void {
            $names = $this->legacyTableNames();
            $found = $this->ctx->legacy->table('information_schema.tables')
                ->selectRaw('table_name AS table_name')
                ->whereRaw('table_schema = DATABASE()')
                ->whereIn('table_name', $names)
                ->pluck('table_name')
                ->all();
            $missing = array_diff($names, array_map('strval', $found));
            $this->outcome(
                $missing === [],
                'Legacy tables exist',
                count($names).' tables incl. dct_analysis, dct_list, dct_data_source, dct_analytical_method and the dct_analysis_* matrix tables',
                'missing: '.implode(', ', $missing),
            );
        });

        $this->attempt('PostgreSQL tables exist', function (): void {
            $names = $this->pgTableNames();
            $found = $this->ctx->pg->table('information_schema.tables')
                ->where('table_schema', 'public')
                ->whereIn('table_name', $names)
                ->pluck('table_name')
                ->all();
            $missing = array_diff($names, array_map('strval', $found));
            $this->outcome($missing === [], 'PostgreSQL tables exist', count($names).' tables', 'missing: '.implode(', ', $missing));
        });
    }

    /**
     * @return array<string, array<string, array{type: string, length: ?int}>>
     */
    private function columnMap(bool $legacy, array $tables): array
    {
        $map = [];
        $query = $legacy
            ? $this->ctx->legacy->table('information_schema.columns')->whereRaw('table_schema = DATABASE()')
            : $this->ctx->pg->table('information_schema.columns')->where('table_schema', 'public');

        $rows = $query->whereIn('table_name', $tables)->selectRaw(
            'table_name AS tbl, column_name AS col, data_type AS typ, character_maximum_length AS len'
        )->get();
        foreach ($rows as $row) {
            $map[(string) $row->tbl][(string) $row->col] = [
                'type' => strtolower((string) $row->typ),
                'length' => $row->len !== null ? (int) $row->len : null,
            ];
        }

        return $map;
    }

    private function checkPgColumns(): void
    {
        $this->attempt('PostgreSQL columns and types', function (): void {
            $expected = [];
            foreach (self::PG_EXPECTED as $table => $spec) {
                foreach (explode(',', $spec) as $item) {
                    [$column, $kind] = explode(':', $item);
                    $expected[$table][$column] = $kind;
                }
            }
            foreach ($this->specs as $spec) {
                $expected[$spec->pgTable]['id'] = 'i';
                foreach ($spec->columns() as $column => $kind) {
                    $expected[$spec->pgTable][$column] = $kind;
                }
            }

            $actual = $this->columnMap(false, array_keys($expected));
            $problems = [];
            foreach ($expected as $table => $columns) {
                foreach ($columns as $column => $kind) {
                    $found = $actual[$table][$column] ?? null;
                    if ($found === null) {
                        $problems[] = "$table.$column is missing";
                    } elseif (! in_array($found['type'], self::KIND_TYPES[$kind], true)) {
                        $problems[] = "$table.$column is {$found['type']}, expected kind $kind (".implode('/', self::KIND_TYPES[$kind]).')';
                    }
                }
            }

            $this->outcome(
                $problems === [],
                'PostgreSQL columns and types',
                array_sum(array_map('count', $expected)).' columns checked in '.count($expected).' tables',
                implode('; ', array_slice($problems, 0, 25)),
            );
        });
    }

    /**
     * Every NOT NULL column without a default (and not generated) must be one
     * the run writes, or every INSERT/COPY into that table would fail.
     */
    private function checkRequiredColumnsWritten(): void
    {
        $this->attempt('NOT NULL columns are written', function (): void {
            $written = [
                'empodat_main' => MainBackfill::MAIN_COLUMNS,
                'empodat_stations' => StationSignature::INSERT_COLUMNS,
                'files' => FilesBackfill::COLUMNS,
                'empodat_analytical_methods' => LookupBackfill::METHOD_COLUMNS,
                'empodat_data_sources' => LookupBackfill::SOURCE_COLUMNS,
                'list_matrices' => LookupBackfill::MATRIX_COLUMNS,
                'list_data_source_organisations' => LookupBackfill::ORG_COLUMNS,
                'list_data_source_laboratories' => LookupBackfill::LAB_COLUMNS,
                'empodat_legacy_import_runs' => self::AUDIT_RUN_COLUMNS,
                'empodat_legacy_import_log' => self::AUDIT_LOG_COLUMNS,
            ];
            foreach ($this->specs as $spec) {
                $written[$spec->pgTable] = $spec->pgColumns();
            }

            $required = $this->ctx->pg->table('information_schema.columns')
                ->where('table_schema', 'public')
                ->whereIn('table_name', array_keys($written))
                ->where('is_nullable', 'NO')
                ->whereNull('column_default')
                ->where('is_identity', 'NO')
                ->where('is_generated', 'NEVER')
                ->get(['table_name', 'column_name']);

            $problems = [];
            foreach ($required as $column) {
                if (! in_array((string) $column->column_name, $written[(string) $column->table_name], true)) {
                    $problems[] = $column->table_name.'.'.$column->column_name;
                }
            }

            $this->outcome(
                $problems === [],
                'NOT NULL columns are written',
                count($written).' tables: every NOT NULL column without a default gets a value',
                'NOT NULL without default and not written by the backfill: '.implode(', ', $problems),
            );
        });
    }

    private function checkLegacyColumns(): void
    {
        $this->attempt('Legacy columns and text lengths', function (): void {
            $expected = [];
            foreach (self::LEGACY_EXPECTED as $table => $columns) {
                foreach ($columns as $column) {
                    $expected[$table][$column] = null;
                }
            }
            foreach ($this->specs as $spec) {
                foreach ($spec->legacySelect() as $column) {
                    $expected[$spec->legacyTable][$column] = null;
                }
            }

            $legacy = $this->columnMap(true, array_keys($expected));
            $pg = $this->columnMap(false, array_map(static fn (TableSpec $s): string => $s->pgTable, $this->specs));
            $problems = [];

            foreach ($expected as $table => $columns) {
                foreach (array_keys($columns) as $column) {
                    if (! isset($legacy[$table][$column])) {
                        $problems[] = "legacy $table.$column is missing";
                    }
                }
            }

            foreach ($this->specs as $spec) {
                foreach ($spec->columns() as $pgColumn => $kind) {
                    $legacyColumn = $spec->legacyColumn($pgColumn);
                    if ($legacyColumn === null) {
                        $this->record('WARN', 'NULL-filled column', "{$spec->pgTable}.$pgColumn has no legacy source and is written as NULL");

                        continue;
                    }
                    $legacyInfo = $legacy[$spec->legacyTable][$legacyColumn] ?? null;
                    $pgInfo = $pg[$spec->pgTable][$pgColumn] ?? null;
                    if ($legacyInfo !== null && $pgInfo !== null && $kind === 's'
                        && $pgInfo['length'] !== null && $legacyInfo['length'] !== null && $pgInfo['length'] < $legacyInfo['length']) {
                        $problems[] = "{$spec->pgTable}.$pgColumn holds {$pgInfo['length']} chars but legacy {$spec->legacyTable}.$legacyColumn allows {$legacyInfo['length']}";
                    }
                }
            }

            $this->outcome(
                $problems === [],
                'Legacy columns and text lengths',
                array_sum(array_map('count', $expected)).' legacy columns present',
                implode('; ', array_slice($problems, 0, 25)),
            );
        });
    }

    private function checkWriteCapability(bool $relaxed): void
    {
        $fail = $relaxed ? 'WARN' : 'FAIL';
        $suffix = $relaxed ? ' (relaxed: LEGACY_BACKFILL_DRY_RUN_READ_ONLY_OK=true, dry run only)' : '';

        $this->attempt('PostgreSQL is writable', function () use ($fail, $suffix): void {
            $row = $this->ctx->pgSessionBeforeLock ?? $this->ctx->pg->selectOne(
                "SELECT current_setting('transaction_read_only') AS ro, current_setting('default_transaction_read_only') AS dro, pg_is_in_recovery() AS recovery"
            );
            $readOnly = $row->ro === 'on' || $row->dro === 'on' || (bool) $row->recovery;
            $this->outcome(
                ! $readOnly,
                'PostgreSQL is writable',
                'not read-only, not a standby',
                sprintf('transaction_read_only=%s default_transaction_read_only=%s in_recovery=%s%s', $row->ro, $row->dro, var_export((bool) $row->recovery, true), $suffix),
                $fail,
            );
        });

        $this->attempt('PostgreSQL privileges', function () use ($fail, $suffix): void {
            $problems = [];
            $writeTables = array_values(array_unique([
                ...self::PG_WRITE_TABLES,
                ...array_map(static fn (TableSpec $s): string => $s->pgTable, $this->specs),
            ]));
            foreach ($writeTables as $table) {
                $row = $this->ctx->pg->selectOne(
                    "SELECT has_table_privilege(current_user, ?, 'INSERT') AS can_insert, has_table_privilege(current_user, ?, 'SELECT') AS can_select",
                    ['public.'.$table, 'public.'.$table],
                );
                if (! $row->can_insert) {
                    $problems[] = "no INSERT on $table";
                }
                if (! $row->can_select) {
                    $problems[] = "no SELECT on $table";
                }
            }
            foreach (['empodat_legacy_import_runs'] as $table) {
                if (! $this->ctx->pg->selectOne("SELECT has_table_privilege(current_user, ?, 'UPDATE') AS ok", ['public.'.$table])->ok) {
                    $problems[] = "no UPDATE on $table";
                }
            }
            foreach (self::PG_SEQUENCE_TABLES as $table) {
                $row = $this->ctx->pg->selectOne(
                    "SELECT pg_get_serial_sequence(?, 'id') AS seq",
                    ['public.'.$table],
                );
                if ($row->seq === null) {
                    $problems[] = "$table.id has no sequence";

                    continue;
                }
                $usable = $this->ctx->pg->selectOne("SELECT has_sequence_privilege(current_user, ?, 'USAGE') AS ok", [$row->seq])->ok;
                if (! $usable) {
                    $problems[] = "no USAGE on sequence {$row->seq}";
                }
            }
            if (! $this->ctx->pg->selectOne("SELECT has_database_privilege(current_database(), 'TEMP') AS ok")->ok) {
                $problems[] = 'no TEMP privilege (COPY staging tables)';
            }

            $this->outcome(
                $problems === [],
                'PostgreSQL privileges',
                'INSERT/SELECT on '.count($writeTables).' tables, UPDATE on runs, sequences, TEMP',
                implode('; ', $problems).$suffix,
                $fail,
            );
        });
    }

    private function checkRunningImports(): void
    {
        $this->attempt('No other legacy import run is running', function (): void {
            $running = $this->ctx->pg->table('empodat_legacy_import_runs')
                ->where('status', 'running')
                ->get(['id', 'started_at', 'notes']);
            if ($running->isEmpty()) {
                $this->record('OK', 'No other legacy import run is running');

                return;
            }
            $this->record('FAIL', 'No other legacy import run is running', $running
                ->map(static fn (object $r): string => sprintf('run #%d since %s (%s)', $r->id, $r->started_at, (string) $r->notes))
                ->implode('; ').'. If it is a dead BACKFILL run, set LEGACY_BACKFILL_CLOSE_STALE_RUN_ID=<id> with execute.');
        });
    }

    private function checkRuntime(): void
    {
        $this->attempt('PHP runtime', function (): void {
            if (! extension_loaded('pdo_pgsql')) {
                $this->record('FAIL', 'PHP runtime', 'pdo_pgsql is not loaded');

                return;
            }
            $this->outcome(
                is_callable([$this->ctx->pg->getPdo(), 'pgsqlCopyFromArray']),
                'PHP runtime',
                'PDO::pgsqlCopyFromArray available, memory_limit='.ini_get('memory_limit'),
                'PDO::pgsqlCopyFromArray is not callable',
            );
        });
    }

    private function collectSizes(): void
    {
        $this->attempt('Table sizes', function (): void {
            $tables = array_values(array_unique([
                'empodat_main', 'empodat_minor', 'empodat_stations', 'files', 'empodat_data_sources', 'empodat_analytical_methods',
                ...array_map(static fn (TableSpec $s): string => $s->pgTable, $this->specs),
            ]));
            $lines = [];
            foreach ($tables as $table) {
                $row = $this->ctx->pg->selectOne(
                    'SELECT pg_total_relation_size(c.oid) AS bytes, c.reltuples::bigint AS rows FROM pg_class c WHERE c.oid = ?::regclass',
                    ['public.'.$table],
                );
                $this->sizes[$table] = ['bytes' => (int) $row->bytes, 'rows' => max(0, (int) $row->rows)];
                $lines[] = sprintf('%s %s (~%s rows)', $table, $this->humanBytes((int) $row->bytes), number_format(max(0, (int) $row->rows), 0, '.', ' '));
            }
            $db = $this->ctx->pg->selectOne('SELECT pg_database_size(current_database()) AS bytes');
            $this->record('INFO', 'Table sizes', implode('; ', $lines).'; database total '.$this->humanBytes((int) $db->bytes));
            $this->record('INFO', 'Free space', 'not visible from SQL: check "df -h" on the PostgreSQL data volume against the estimate in the report');
        });
    }

    private function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $i = 0;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return sprintf('%.1f %s', $value, $units[$i]);
    }
}
