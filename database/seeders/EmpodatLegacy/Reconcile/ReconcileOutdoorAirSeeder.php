<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use RuntimeException;

/**
 * Gives the 12 outdoor air records (empodat_main.id 12037475 to 12037486,
 * matrix_id 70) their details from legacy dct_analysis_air_outdoor in
 * empodat_matrix_air. In one transaction it
 *  - inserts list_air_proxy_pressures (legacy data_proxy, ids 1 to 5) and
 *    list_air_concentration_types (legacy data_cto, ids 1 and 2) with the
 *    legacy ids and sets their id sequences,
 *  - inserts one empodat_matrix_air row per record (dsmo_id, dscd_id, dproxy_id,
 *    dcto_id as in legacy, dloca_id translated from legacy data_loc to
 *    list_air_locations by exact name, flow_rate, wider_area, ground_level and
 *    sea_level copied, the other columns NULL),
 *  - sets list_matrices.empodat_matrix_link to empodat_matrix_air for id 70,
 *    only where it is NULL or empty.
 *
 * The run stops before anything is written unless legacy has exactly the 12
 * ids, all of them exist in empodat_main with matrix_id 70, the legacy lists
 * hold the expected names, the sampling mode and collection device names of the
 * used numbers equal the NDS2.0 lists, and every location name matches exactly
 * one list_air_locations row. Rows and lists already present count as done, a
 * rerun after a successful run plans 0 rows. Needs the migration that creates
 * the two lists and the dproxy_id and dcto_id columns.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileOutdoorAirSeeder' --force
 */
final class ReconcileOutdoorAirSeeder extends ReconcileSeeder
{
    private const TABLE = 'empodat_matrix_air';

    private const MATRIX_ID = 70;

    private const MATRIX_LINK = 'empodat_matrix_air';

    private const ID_FROM = 12037475;

    private const ID_TO = 12037486;

    private const MAX_LENGTH = 255;

    private const PROXY_TABLE = 'list_air_proxy_pressures';

    private const CTO_TABLE = 'list_air_concentration_types';

    private const EXPECTED_LOCATION = [3 => ['name' => 'Ambient air - Rural/agricultural area', 'id' => 9]];

    /**
     * @var array<string, array<int, string>>
     */
    private const EXPECTED_LISTS = [
        self::PROXY_TABLE => [1 => 'Industrial area', 2 => 'Long range transport', 3 => 'Traffic', 4 => 'Urban', 5 => 'Other'],
        self::CTO_TABLE => [1 => 'Quantitative', 2 => 'Semi-quantitative'],
    ];

    /**
     * @var array<string, string>
     */
    private const LEGACY_LISTS = [
        self::PROXY_TABLE => 'data_proxy',
        self::CTO_TABLE => 'data_cto',
    ];

    /** @var array<string, array<int, string>> list table => id => name still to insert */
    private array $listWork = [];

    /** @var array<int, array<string, mixed>> empodat_main id => row to insert */
    private array $airWork = [];

    private ?string $oldLink = null;

    private bool $linkWork = false;

    protected function plan(): int
    {
        $this->listWork = [];
        $this->airWork = [];
        $this->oldLink = null;
        $this->linkWork = false;

        $this->assertStructure();
        $this->planLists();

        $legacyRows = $this->legacyRows();
        $this->assertMainRecords(array_keys($legacyRows));
        $this->assertDeviceNames($legacyRows);

        $locations = $this->locationMap($legacyRows);

        $present = array_map(
            static fn (object $row): int => (int) $row->id,
            $this->pg->table(self::TABLE)->whereBetween('id', [self::ID_FROM, self::ID_TO])->get(['id'])->all(),
        );

        $done = 0;
        foreach ($legacyRows as $id => $row) {
            if (in_array($id, $present, true)) {
                $done++;

                continue;
            }

            $this->airWork[$id] = [
                'id' => $id,
                'dloca_id' => $row['dloc_id'] === null ? null : $locations[$row['dloc_id']],
                'dsmo_id' => $row['dsmo_id'],
                'dscd_id' => $row['dscd_id'],
                'dproxy_id' => $row['dproxy_id'],
                'dcto_id' => $row['dcto_id'],
                'flow_rate' => $row['flow_rate'],
                'wider_area' => $row['wider_area'],
                'ground_level' => $row['ground_level'],
                'sea_level' => $row['sea_level'],
            ];
        }

        $matrix = $this->pg->table('list_matrices')->where('id', self::MATRIX_ID)->first(['id', 'empodat_matrix_link']);
        if ($matrix === null) {
            throw new RuntimeException('list_matrices has no row '.self::MATRIX_ID.', nothing written.');
        }
        $this->oldLink = $matrix->empodat_matrix_link;
        $this->linkWork = $this->oldLink === null || $this->oldLink === '';

        $lists = array_sum(array_map('count', $this->listWork));
        $this->say('Legacy rows: '.count($legacyRows).', already in '.self::TABLE.': '.$done.', to insert: '.count($this->airWork));
        $this->say('List rows to insert: '.$lists);
        $this->say('list_matrices '.self::MATRIX_ID.' empodat_matrix_link: '.($this->linkWork ? 'to set' : 'already "'.$this->oldLink.'", left alone'));

        return $lists + count($this->airWork) + ($this->linkWork ? 1 : 0);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $this->pg->transaction(function () use ($audit): void {
            foreach ($this->listWork as $table => $rows) {
                $startedAt = hrtime(true);
                $now = now();

                $insert = [];
                foreach ($rows as $id => $name) {
                    $insert[] = ['id' => $id, 'name' => $name, 'created_at' => $now, 'updated_at' => $now];
                }
                $this->pg->table($table)->insert($insert);
                $this->pg->statement('SELECT setval(pg_get_serial_sequence(\''.$table.'\', \'id\'), (SELECT MAX(id) FROM '.$table.'))');

                $audit->logInsert('Outdoor air '.$table, $table, array_keys($rows), $this->elapsed($startedAt));
            }

            if ($this->airWork !== []) {
                $startedAt = hrtime(true);
                $this->pg->table(self::TABLE)->insert(array_values($this->airWork));
                $audit->logInsert('Outdoor air records', self::TABLE, array_keys($this->airWork), $this->elapsed($startedAt));
            }

            if ($this->linkWork) {
                $startedAt = hrtime(true);
                $updated = $this->pg->update(
                    'UPDATE list_matrices SET empodat_matrix_link = ? WHERE id = ? AND (empodat_matrix_link IS NULL OR empodat_matrix_link = \'\')',
                    [self::MATRIX_LINK, self::MATRIX_ID],
                );
                if ($updated !== 1) {
                    throw new RuntimeException('list_matrices '.self::MATRIX_ID.' changed meanwhile, everything rolled back.');
                }
                $audit->logFill(
                    'Outdoor air matrix link',
                    'list_matrices',
                    ['empodat_matrix_link'],
                    [self::MATRIX_ID],
                    [['id' => self::MATRIX_ID, 'empodat_matrix_link' => $this->oldLink]],
                    $this->elapsed($startedAt),
                );
            }
        });

        $this->say('Rows written: '.(array_sum(array_map('count', $this->listWork)) + count($this->airWork) + ($this->linkWork ? 1 : 0)).'.');
    }

    protected function notes(): string
    {
        return 'outdoor air records 12037475-12037486 from legacy dct_analysis_air_outdoor into empodat_matrix_air, lists data_proxy and data_cto';
    }

    private function assertStructure(): void
    {
        $schema = $this->pg->getSchemaBuilder();

        foreach ([self::PROXY_TABLE, self::CTO_TABLE] as $table) {
            if (! $schema->hasTable($table)) {
                throw new RuntimeException('Table '.$table.' does not exist, run the migration first. Nothing written.');
            }
        }

        foreach (['dproxy_id', 'dcto_id'] as $column) {
            if (! $schema->hasColumn(self::TABLE, $column)) {
                throw new RuntimeException('Column '.self::TABLE.'.'.$column.' does not exist, run the migration first. Nothing written.');
            }
        }
    }

    private function planLists(): void
    {
        foreach (self::EXPECTED_LISTS as $table => $expected) {
            $legacy = $this->legacyList(self::LEGACY_LISTS[$table]);
            if ($legacy !== $expected) {
                throw new RuntimeException('Legacy '.self::LEGACY_LISTS[$table].' differs from the expected '.json_encode($expected).', found '.json_encode($legacy).'. Nothing written.');
            }

            $existing = [];
            foreach ($this->pg->table($table)->get(['id', 'name']) as $row) {
                $existing[(int) $row->id] = (string) $row->name;
            }

            foreach ($expected as $id => $name) {
                if (! isset($existing[$id])) {
                    $this->listWork[$table][$id] = $name;
                } elseif ($existing[$id] !== $name) {
                    throw new RuntimeException($table.' id '.$id.' holds "'.$existing[$id].'", expected "'.$name.'". Nothing written.');
                }
            }
        }
    }

    /**
     * @return array<int, string> id => name, read by column position
     */
    private function legacyList(string $table): array
    {
        $list = [];
        foreach ($this->legacy->select('SELECT * FROM '.$table.' ORDER BY 1') as $row) {
            $values = array_values((array) $row);
            if (count($values) < 2) {
                throw new RuntimeException('Legacy '.$table.' has fewer than 2 columns. Nothing written.');
            }
            $list[(int) $values[0]] = trim((string) $values[1]);
        }

        return $list;
    }

    /**
     * @return array<int, array<string, mixed>> legacy id => row with ints and strings normalised
     */
    private function legacyRows(): array
    {
        $rows = $this->legacy->select(
            'SELECT id, flow_rate, dcto_id, dloc_id, wider_area, ground_level, sea_level, dproxy_id, dsmo_id, dscd_id FROM dct_analysis_air_outdoor WHERE id BETWEEN ? AND ? ORDER BY id',
            [self::ID_FROM, self::ID_TO],
        );

        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row->id;
            $result[$id] = [
                'dcto_id' => $this->nullableInt($row->dcto_id),
                'dloc_id' => $this->nullableInt($row->dloc_id),
                'dproxy_id' => $this->nullableInt($row->dproxy_id),
                'dsmo_id' => $this->nullableInt($row->dsmo_id),
                'dscd_id' => $this->nullableInt($row->dscd_id),
                'flow_rate' => $this->nullableText($row->flow_rate, $id, 'flow_rate'),
                'wider_area' => $this->nullableText($row->wider_area, $id, 'wider_area'),
                'ground_level' => $this->nullableText($row->ground_level, $id, 'ground_level'),
                'sea_level' => $this->nullableText($row->sea_level, $id, 'sea_level'),
            ];
        }

        $expectedIds = range(self::ID_FROM, self::ID_TO);
        if (array_keys($result) !== $expectedIds) {
            throw new RuntimeException('Legacy dct_analysis_air_outdoor does not hold exactly the ids '.self::ID_FROM.' to '.self::ID_TO.' (found '.count($result).' rows). Nothing written.');
        }

        foreach ($result as $id => $row) {
            foreach (['dcto_id' => self::CTO_TABLE, 'dproxy_id' => self::PROXY_TABLE] as $column => $table) {
                if ($row[$column] !== null && ! isset(self::EXPECTED_LISTS[$table][$row[$column]])) {
                    throw new RuntimeException('Legacy record '.$id.' has '.$column.' '.$row[$column].' which is not in '.$table.'. Nothing written.');
                }
            }
        }

        return $result;
    }

    /**
     * @param  list<int>  $ids
     */
    private function assertMainRecords(array $ids): void
    {
        $found = $this->pg->table('empodat_main')
            ->whereIn('id', $ids)
            ->where('matrix_id', self::MATRIX_ID)
            ->count();

        if ($found !== count($ids)) {
            throw new RuntimeException('Only '.$found.' of '.count($ids).' records exist in empodat_main with matrix_id '.self::MATRIX_ID.'. Nothing written.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $legacyRows
     */
    private function assertDeviceNames(array $legacyRows): void
    {
        $checks = [
            ['dsmo_id', 'data_smo', 'list_air_sampling_modes'],
            ['dscd_id', 'data_scd', 'list_sampling_collection_devices'],
        ];

        foreach ($checks as [$column, $legacyTable, $pgTable]) {
            $used = array_values(array_unique(array_filter(array_column($legacyRows, $column), static fn (mixed $value): bool => $value !== null)));
            if ($used === []) {
                continue;
            }

            $legacy = $this->legacyList($legacyTable);
            $pg = [];
            foreach ($this->pg->table($pgTable)->whereIn('id', $used)->get(['id', 'name']) as $row) {
                $pg[(int) $row->id] = trim((string) $row->name);
            }

            foreach ($used as $number) {
                if (! isset($legacy[$number], $pg[$number]) || $legacy[$number] !== $pg[$number]) {
                    throw new RuntimeException($column.' '.$number.': legacy '.$legacyTable.' "'.($legacy[$number] ?? 'missing').'" differs from '.$pgTable.' "'.($pg[$number] ?? 'missing').'". Nothing written.');
                }
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $legacyRows
     * @return array<int, int> legacy data_loc id => list_air_locations id
     */
    private function locationMap(array $legacyRows): array
    {
        $used = array_values(array_unique(array_filter(array_column($legacyRows, 'dloc_id'), static fn (mixed $value): bool => $value !== null)));
        if ($used === []) {
            return [];
        }

        $legacy = $this->legacyList('data_loc');
        $map = [];

        foreach ($used as $number) {
            if (! isset($legacy[$number])) {
                throw new RuntimeException('Legacy data_loc has no id '.$number.'. Nothing written.');
            }

            $matches = $this->pg->table('list_air_locations')->where('name', $legacy[$number])->get(['id']);
            if ($matches->count() !== 1) {
                throw new RuntimeException('Location "'.$legacy[$number].'" matches '.$matches->count().' rows in list_air_locations, expected exactly 1. Nothing written.');
            }

            $map[$number] = (int) $matches->first()->id;

            if (isset(self::EXPECTED_LOCATION[$number]) && (self::EXPECTED_LOCATION[$number]['name'] !== $legacy[$number] || self::EXPECTED_LOCATION[$number]['id'] !== $map[$number])) {
                throw new RuntimeException('Legacy data_loc '.$number.' "'.$legacy[$number].'" gives list_air_locations '.$map[$number].', expected '.self::EXPECTED_LOCATION[$number]['id'].'. Nothing written.');
            }
        }

        return $map;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableText(mixed $value, int $id, string $column): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = (string) $value;
        if (mb_strlen($text) > self::MAX_LENGTH) {
            throw new RuntimeException('Legacy record '.$id.' '.$column.' is longer than '.self::MAX_LENGTH.' characters. Nothing written.');
        }

        return $text;
    }

    private function elapsed(int|float $startedAt): int
    {
        return (int) ((hrtime(true) - $startedAt) / 1_000_000);
    }
}
