<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use RuntimeException;
use stdClass;

/**
 * Copies the numbered organisation / laboratory / contact groups (N = 1..3) of
 * legacy dct_data_source into empodat_data_source_groups, empodat_contacts and,
 * where needed, new rows of list_data_source_organisations and
 * list_data_source_laboratories. A source in legacy that is absent from
 * empodat_data_sources stops the run.
 *
 * An organisation or laboratory is reused only when the trimmed name, the city
 * and the country_id all match exactly (NULL equals empty); with several
 * matches the lowest id wins. Anything else is inserted as a new row with the
 * legacy name, city and country (acronym NULL). There is no fuzzy mapping:
 * duplicates are left for Lubos. A country code that list_countries does not
 * know gives NULL and a printed warning. A contact with identical first name,
 * family name and e-mail is reused. A group with no organisation name,
 * laboratory name or contact value is skipped, its position N is kept for the
 * others. A (data_source_id, position) that already exists counts as done.
 *
 * Contact names and e-mails are never printed.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileDataSourceGroupsSeeder' --force
 */
final class ReconcileDataSourceGroupsSeeder extends ReconcileSeeder
{
    private const STEP = 'Data source groups';

    private const SOURCES = 'empodat_data_sources';

    private const GROUPS = 'empodat_data_source_groups';

    private const CONTACTS = 'empodat_contacts';

    private const ORGANISATIONS = 'list_data_source_organisations';

    private const LABORATORIES = 'list_data_source_laboratories';

    private const POSITIONS = [1, 2, 3];

    private const EXPECTED_SOURCES = 5;

    private const EXPECTED_GROUPS = 11;

    private const SEPARATOR = "\x1f";

    /**
     * @var array{
     *     groups: list<array<string, mixed>>,
     *     organisations: array<string, array<string, mixed>>,
     *     laboratories: array<string, array<string, mixed>>,
     *     contacts: array<string, array<string, mixed>>
     * }
     */
    private array $work = [];

    /** @var array<string, int>|null */
    private ?array $countryIds = null;

    /** @var array<string, true> */
    private array $warnedCountries = [];

    protected function plan(): int
    {
        $this->work = $this->build();

        return count($this->work['groups']);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $started = microtime(true);

        $this->pg->transaction(function () use ($audit, $started): void {
            $this->work = $this->build(false);
            $now = now()->toDateTimeString();

            $organisationIds = [];
            foreach ($this->work['organisations'] as $key => $row) {
                $organisationIds[$key] = (int) $this->pg->table(self::ORGANISATIONS)->insertGetId($row + ['created_at' => $now, 'updated_at' => $now]);
            }

            $laboratoryIds = [];
            foreach ($this->work['laboratories'] as $key => $row) {
                $laboratoryIds[$key] = (int) $this->pg->table(self::LABORATORIES)->insertGetId($row + ['created_at' => $now, 'updated_at' => $now]);
            }

            $contactIds = [];
            foreach ($this->work['contacts'] as $key => $row) {
                $contactIds[$key] = (int) $this->pg->table(self::CONTACTS)->insertGetId($row + ['user_id' => null, 'created_at' => $now, 'updated_at' => $now]);
            }

            $groupIds = [];
            foreach ($this->work['groups'] as $group) {
                $groupIds[] = (int) $this->pg->table(self::GROUPS)->insertGetId([
                    'data_source_id' => $group['data_source_id'],
                    'position' => $group['position'],
                    'organisation_id' => $this->resolved($group['organisation'], $organisationIds),
                    'laboratory1_id' => $this->resolved($group['laboratory1'], $laboratoryIds),
                    'laboratory2_id' => $this->resolved($group['laboratory2'], $laboratoryIds),
                    'contact_id' => $this->resolved($group['contact'], $contactIds),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $duration = (int) ((microtime(true) - $started) * 1000);
            $audit->logInsert(self::STEP, self::ORGANISATIONS, array_values($organisationIds), $duration);
            $audit->logInsert(self::STEP, self::LABORATORIES, array_values($laboratoryIds), $duration);
            $audit->logInsert(self::STEP, self::CONTACTS, array_values($contactIds), $duration);
            $audit->logInsert(self::STEP, self::GROUPS, $groupIds, $duration);

            $this->say(sprintf(
                'Inserted: %d organisations, %d laboratories, %d contacts, %d groups.',
                count($organisationIds),
                count($laboratoryIds),
                count($contactIds),
                count($groupIds),
            ));
        });
    }

    protected function notes(): string
    {
        return 'Copy the numbered organisation, laboratory and contact groups of legacy data sources into empodat_data_source_groups';
    }

    /**
     * @param  array{key: string, id?: int}|null  $reference
     * @param  array<string, int>  $ids
     */
    private function resolved(?array $reference, array $ids): ?int
    {
        if ($reference === null) {
            return null;
        }

        return $reference['id'] ?? $ids[$reference['key']];
    }

    /**
     * @return array{
     *     groups: list<array<string, mixed>>,
     *     organisations: array<string, array<string, mixed>>,
     *     laboratories: array<string, array<string, mixed>>,
     *     contacts: array<string, array<string, mixed>>
     * }
     */
    private function build(bool $report = true): array
    {
        $this->assertLegacyColumns();

        $rows = $this->legacySources();
        $sourceIds = array_map(static fn (stdClass $row): int => (int) $row->id_data, $rows);

        $existingSources = $this->pg->table(self::SOURCES)->whereIn('id', $sourceIds)->pluck('id')
            ->map(static fn ($id): int => (int) $id)->all();
        $absent = array_values(array_diff($sourceIds, $existingSources));
        if ($absent !== []) {
            throw new RuntimeException('Legacy data sources absent from '.self::SOURCES.': '.implode(', ', $absent).'. Nothing written.');
        }

        $done = [];
        foreach ($this->pg->table(self::GROUPS)->whereIn('data_source_id', $sourceIds)->get(['data_source_id', 'position']) as $row) {
            $done[(int) $row->data_source_id.':'.(int) $row->position] = true;
        }

        $organisations = $this->existingByKey(self::ORGANISATIONS, ['name', 'city', 'country_id']);
        $laboratories = $this->existingByKey(self::LABORATORIES, ['name', 'city', 'country_id']);
        $contacts = $this->existingByKey(self::CONTACTS, ['first_name', 'family_name', 'email']);

        $work = ['groups' => [], 'organisations' => [], 'laboratories' => [], 'contacts' => []];
        $found = 0;
        $alreadyDone = 0;
        $perSource = [];

        foreach ($rows as $row) {
            $sourceId = (int) $row->id_data;
            foreach (self::POSITIONS as $n) {
                $organisation = $this->place($row, 'organisation_%s'.$n, $n, 'organisation');
                $laboratory1 = $this->place($row, 'laboratory_1_%s'.$n, $n, 'laboratory 1');
                $laboratory2 = $this->place($row, 'laboratory_2_%s'.$n, $n, 'laboratory 2');
                $contact = [
                    'first_name' => $this->text($row->{'contact_person_first_name'.$n} ?? null),
                    'family_name' => $this->text($row->{'contact_person_family_name'.$n} ?? null),
                    'email' => $this->text($row->{'contact_person_email'.$n} ?? null),
                ];
                $hasContact = $contact['first_name'] !== null || $contact['family_name'] !== null || $contact['email'] !== null;

                if ($organisation === null && $laboratory1 === null && $laboratory2 === null && ! $hasContact) {
                    continue;
                }

                $found++;
                $perSource[$sourceId] = ($perSource[$sourceId] ?? 0) + 1;
                if (isset($done[$sourceId.':'.$n])) {
                    $alreadyDone++;

                    continue;
                }

                $group = [
                    'data_source_id' => $sourceId,
                    'position' => $n,
                    'organisation' => $this->reference($organisation, $organisations, $work['organisations']),
                    'laboratory1' => $this->reference($laboratory1, $laboratories, $work['laboratories']),
                    'laboratory2' => $this->reference($laboratory2, $laboratories, $work['laboratories']),
                    'contact' => $hasContact ? $this->reference($contact, $contacts, $work['contacts']) : null,
                ];
                $work['groups'][] = $group;
            }
        }

        if ($report) {
            $this->report($sourceIds, $perSource, $found, $alreadyDone, $work);
        }

        return $work;
    }

    /**
     * @param  list<int>  $sourceIds
     * @param  array<int, int>  $perSource
     * @param  array{groups: list<array<string, mixed>>, organisations: array<string, array<string, mixed>>, laboratories: array<string, array<string, mixed>>, contacts: array<string, array<string, mixed>>}  $work
     */
    private function report(array $sourceIds, array $perSource, int $found, int $alreadyDone, array $work): void
    {
        $this->say('Legacy sources with groups: '.count($sourceIds).' (expected '.self::EXPECTED_SOURCES.')');
        foreach ($perSource as $sourceId => $count) {
            $this->say('  source '.$sourceId.': '.$count.' group(s)');
        }
        $this->say('Groups found: '.$found.' (expected '.self::EXPECTED_GROUPS.')');
        $this->say('Already done: '.$alreadyDone);
        $this->say('Groups to insert: '.count($work['groups']));
        $this->say('New contacts: '.count($work['contacts']));
        $this->say('New organisations: '.count($work['organisations']));
        foreach ($work['organisations'] as $row) {
            $this->say('  '.$row['name'].' | '.($row['city'] ?? '-').' | country_id '.($row['country_id'] ?? 'NULL'));
        }
        $this->say('New laboratories: '.count($work['laboratories']));
        foreach ($work['laboratories'] as $row) {
            $this->say('  '.$row['name'].' | '.($row['city'] ?? '-').' | country_id '.($row['country_id'] ?? 'NULL'));
        }
    }

    /**
     * @param  array<string, int>  $existing  key => lowest existing id
     * @param  array<string, array<string, mixed>>  $new
     * @param  array<string, mixed>|null  $values
     * @return array{key: string, id?: int}|null
     */
    private function reference(?array $values, array $existing, array &$new): ?array
    {
        if ($values === null) {
            return null;
        }

        $key = implode(self::SEPARATOR, array_map(static fn (mixed $value): string => (string) ($value ?? ''), $values));
        if (isset($existing[$key])) {
            return ['key' => $key, 'id' => $existing[$key]];
        }

        $new[$key] ??= $values;

        return ['key' => $key];
    }

    /**
     * @return array{name: string, city: ?string, country_id: ?int}|null
     */
    private function place(stdClass $row, string $pattern, int $n, string $kind): ?array
    {
        $name = $this->text($row->{sprintf($pattern, 'name')} ?? null);
        if ($name === null) {
            return null;
        }

        return [
            'name' => $name,
            'city' => $this->text($row->{sprintf($pattern, 'city')} ?? null),
            'country_id' => $this->countryId($this->text($row->{sprintf($pattern, 'country')} ?? null), (int) $row->id_data, $n, $kind),
        ];
    }

    private function countryId(?string $code, int $sourceId, int $n, string $kind): ?int
    {
        if ($code === null) {
            return null;
        }

        $this->countryIds ??= $this->pg->table('list_countries')->orderByDesc('id')->pluck('id', 'code')
            ->map(static fn ($id): int => (int) $id)->all();

        $id = $this->countryIds[$code] ?? null;
        if ($id === null) {
            $warning = 'source '.$sourceId.' group '.$n.' '.$kind.': country code "'.$code.'" not in list_countries, stored as NULL';
            if (! isset($this->warnedCountries[$warning])) {
                $this->warnedCountries[$warning] = true;
                $this->say('WARNING: '.$warning);
            }
        }

        return $id;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Signature => lowest id of the rows that carry it.
     *
     * @param  list<string>  $columns
     * @return array<string, int>
     */
    private function existingByKey(string $table, array $columns): array
    {
        $map = [];
        foreach ($this->pg->table($table)->orderBy('id')->get(['id', ...$columns]) as $row) {
            $key = implode(self::SEPARATOR, array_map(
                fn (string $column): string => (string) $this->text($row->{$column}),
                $columns,
            ));
            $map[$key] ??= (int) $row->id;
        }

        return $map;
    }

    /**
     * @return list<stdClass>
     */
    private function legacySources(): array
    {
        $query = $this->legacy->table('dct_data_source')->orderBy('id_data');
        $query->where(function ($where): void {
            foreach (self::POSITIONS as $n) {
                foreach (['organisation_name', 'laboratory_1_name', 'laboratory_2_name', 'contact_person_family_name', 'contact_person_first_name', 'contact_person_email'] as $column) {
                    $where->orWhereRaw("TRIM(`{$column}{$n}`) <> ''");
                }
            }
        });

        return $query->get()->all();
    }

    private function assertLegacyColumns(): void
    {
        $schema = $this->legacy->getSchemaBuilder();
        foreach (self::POSITIONS as $n) {
            foreach (['organisation', 'laboratory_1', 'laboratory_2'] as $prefix) {
                foreach (['name', 'city', 'country'] as $part) {
                    $this->requireColumn($schema->hasColumn('dct_data_source', $prefix.'_'.$part.$n), $prefix.'_'.$part.$n);
                }
            }
            foreach (['contact_person_family_name', 'contact_person_first_name', 'contact_person_email'] as $column) {
                $this->requireColumn($schema->hasColumn('dct_data_source', $column.$n), $column.$n);
            }
        }
    }

    private function requireColumn(bool $exists, string $column): void
    {
        if (! $exists) {
            throw new RuntimeException('Legacy dct_data_source has no column '.$column.'.');
        }
    }
}
