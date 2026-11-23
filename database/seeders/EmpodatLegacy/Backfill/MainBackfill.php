<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use RuntimeException;
use Throwable;

/**
 * empodat_main (and the stations it needs). For every legacy dct_analysis id
 * missing in PG:
 *   - station_id: an existing PG station with the same 13-column signature is
 *     REUSED; only signatures nobody has are inserted (same transaction, one
 *     INSERT per new station so every id is tied to its own signature);
 *   - substance_id: susdat_substances.code = LPAD(sus_id, 8, '0'), NULL when
 *     no such code exists (reported, never invented);
 *   - country_id: list_countries.code, NULL when unknown;
 *   - file_id: computed up front from the legacy dct_list ranges (highest
 *     list_id wins, as PopulateFileIdStep does), so there is no UPDATE;
 *   - method_id / data_source_id: raw legacy ids (the lookups step has just
 *     copied the missing rows; ids legacy itself lacks stay as they are).
 * NULLs in legacy stay NULL.
 *
 * Dry run and execute share one pipeline. In a dry run a new station gets a
 * negative placeholder id (so later rows with the same signature reuse it, as
 * they would for real) and the COPY is replaced by a count.
 */
final class MainBackfill
{
    private const STEP = 'Backfill empodat_main (dct_analysis -> empodat_main)';

    private const STEP_STATIONS = 'Backfill empodat_stations';

    /** Columns written to empodat_main, in COPY order. */
    public const MAIN_COLUMNS = [
        'id', 'station_id', 'matrix_id', 'substance_id', 'sampling_date_year',
        'concentration_indicator_id', 'concentration_value', 'method_id',
        'data_source_id', 'country_id', 'file_id',
    ];

    /** @var list<string> */
    private const LEGACY_SELECT = [
        'id', 'sus_id', 'country', 'country_other', 'station_name', 'national_name',
        'short_sample_code', 'sample_code', 'provider_code', 'code_ec_wise',
        'code_ec_other', 'code_other', 'longitude_decimal', 'latitude_decimal',
        'specific_locations', 'matrix', 'sampling_date_y', 'dic_id',
        'concentration_value', 'id_method', 'id_data',
    ];

    private int $placeholderStationId = 0;

    /** @var array<string, true> signatures of the stations this run created (or would create) */
    private array $createdSignatures = [];

    public function __construct(private readonly BackfillContext $ctx) {}

    /**
     * Builds the signature index of every station PG already has.
     */
    public function stationIndex(): StationIndex
    {
        $index = new StationIndex;
        $this->ctx->pg->table('empodat_stations')
            ->select([
                'id', 'name', 'country', 'country_other', 'national_name',
                'short_sample_code', 'sample_code', 'provider_code',
                'code_ec_wise', 'code_ec_other', 'code_other',
                'specific_locations', 'longitude', 'latitude', 'is_deprecated',
            ])
            ->orderBy('id')
            ->chunk(20000, function ($rows) use ($index): void {
                foreach ($rows as $r) {
                    $index->add(StationSignature::fromStation($r), (int) $r->id, (bool) $r->is_deprecated);
                }
            });

        return $index;
    }

    /**
     * @param  list<int>  $availableFileIds  empodat files.id that exist (or will exist) in PG
     */
    public function fileIndex(array $availableFileIds): FileRangeIndex
    {
        $available = array_flip($availableFileIds);
        $ranges = [];
        foreach ($this->ctx->legacy->table('dct_list')
            ->whereNotNull('list_analysis_from')
            ->whereNotNull('list_analysis_to')
            ->orderBy('list_id')
            ->get(['list_id', 'list_analysis_from', 'list_analysis_to']) as $r) {
            if (isset($available[(int) $r->list_id])) {
                $ranges[] = [(int) $r->list_analysis_from, (int) $r->list_analysis_to, (int) $r->list_id];
            }
        }

        return new FileRangeIndex($ranges);
    }

    /**
     * Reads every legacy row of $runs and turns it into an empodat_main row.
     * Writes only when $write is true (execute, with an audit run).
     *
     * @param  list<array{0: int, 1: int, 2: int}>  $runs
     * @param  list<int>  $availableFileIds
     * @return array{
     *     rowsRead: int,
     *     inserted: int,
     *     wouldInsert: int,
     *     alreadyInPg: int,
     *     stationsInPg: int,
     *     stationsAmbiguousInPg: int,
     *     rowsWithExistingStation: int,
     *     newStations: int,
     *     noSubstance: array<string, int>,
     *     noCountry: array<string, int>,
     *     noFile: int,
     *     files: array<int, int>,
     *     samples: list<array<string, mixed>>
     * }
     */
    public function run(array $runs, array $availableFileIds, bool $write): array
    {
        $audit = $write ? ($this->ctx->audit ?? throw new RuntimeException('MainBackfill write without an audit run.')) : null;

        $index = $this->stationIndex();
        $stats = [
            'rowsRead' => 0,
            'inserted' => 0,
            'wouldInsert' => 0,
            'alreadyInPg' => 0,
            'stationsInPg' => $index->count(),
            'stationsAmbiguousInPg' => $index->ambiguousCount(),
            'rowsWithExistingStation' => 0,
            'newStations' => 0,
            'noSubstance' => [],
            'noCountry' => [],
            'noFile' => 0,
            'files' => [],
            'samples' => [],
        ];
        $this->ctx->emit(sprintf('  station index: %d signatures in PG (%d shared by more than one station)', $index->count(), $index->ambiguousCount()));

        $substances = array_map('intval', $this->ctx->pg->table('susdat_substances')->pluck('id', 'code')->all());
        $countries = array_map('intval', $this->ctx->pg->table('list_countries')->pluck('id', 'code')->all());
        $fileIndex = $this->fileIndex($availableFileIds);
        $select = implode(', ', array_map(fn (string $c): string => $this->ctx->quoteLegacy($c), self::LEGACY_SELECT));
        $chunk = $this->ctx->options->chunkRows;
        $total = IdRanges::countIds($runs);

        try {
            foreach ($runs as [$from, $to]) {
                $cursor = $from;
                while ($cursor <= $to) {
                    $rows = $this->ctx->legacy->select(
                        'SELECT '.$select.' FROM dct_analysis WHERE id >= ? AND id <= ? ORDER BY id LIMIT '.$chunk,
                        [$cursor, $to],
                    );
                    if ($rows === []) {
                        break;
                    }
                    $this->ctx->pauseLegacy();

                    $this->processChunk($rows, $index, $substances, $countries, $fileIndex, $audit, $stats);

                    $cursor = (int) $rows[array_key_last($rows)]->id + 1;
                    $this->ctx->progress(sprintf(
                        'main: %d / %d legacy rows read, %d %s, %d new stations (id %d)',
                        $stats['rowsRead'],
                        $total,
                        $write ? $stats['inserted'] : $stats['wouldInsert'],
                        $write ? 'inserted' : 'would be inserted',
                        $stats['newStations'],
                        $cursor - 1,
                    ));
                }
            }
        } catch (Throwable $e) {
            $audit?->failedStep(self::STEP, 'empodat_main', $e);
            throw $e;
        }

        ksort($stats['files']);

        return $stats;
    }

    /**
     * @param  list<object>  $rows
     * @param  array<string, int>  $substances
     * @param  array<string, int>  $countries
     * @param  array<string, mixed>  $stats
     */
    private function processChunk(
        array $rows,
        StationIndex $index,
        array $substances,
        array $countries,
        FileRangeIndex $fileIndex,
        ?Audit $audit,
        array &$stats,
    ): void {
        $started = microtime(true);
        $stats['rowsRead'] += count($rows);

        $signatures = [];
        $pending = [];
        foreach ($rows as $i => $r) {
            $signature = StationSignature::fromLegacy($r);
            $signatures[$i] = $signature;
            if ($index->find($signature) === null && ! isset($pending[$signature])) {
                $pending[$signature] = $r;
            }
        }

        $build = function () use ($rows, $signatures, $pending, $index, $substances, $countries, $fileIndex, $audit, &$stats, $started): array {
            $newStationIds = [];
            if ($pending !== []) {
                $now = now()->toDateTimeString();
                foreach ($pending as $signature => $r) {
                    $id = $audit !== null
                        ? (int) $this->ctx->pg->table('empodat_stations')->insertGetId(StationSignature::insertRow($r, $countries, $now))
                        : --$this->placeholderStationId;
                    $index->add($signature, $id);
                    $newStationIds[$signature] = $id;
                    $this->createdSignatures[$signature] = true;
                }
                if ($audit !== null) {
                    $audit->chunk(self::STEP_STATIONS, 'empodat_stations', array_values($newStationIds), (int) ((microtime(true) - $started) * 1000));
                }
                $stats['newStations'] += count($newStationIds);
            }

            $lines = [];
            foreach ($rows as $i => $r) {
                $stationId = $index->find($signatures[$i]);
                $createdByRun = isset($this->createdSignatures[$signatures[$i]]);
                if (! $createdByRun) {
                    $stats['rowsWithExistingStation']++;
                }
                $code = str_pad((string) $r->sus_id, 8, '0', STR_PAD_LEFT);
                $substanceId = $substances[$code] ?? null;
                if ($substanceId === null) {
                    $stats['noSubstance'][$code] = ($stats['noSubstance'][$code] ?? 0) + 1;
                }
                $country = trim((string) $r->country);
                $countryId = $countries[$country] ?? null;
                if ($countryId === null) {
                    $stats['noCountry'][$country] = ($stats['noCountry'][$country] ?? 0) + 1;
                }
                $id = (int) $r->id;
                $fileId = $fileIndex->fileIdFor($id);
                if ($fileId === null) {
                    $stats['noFile']++;
                } else {
                    $stats['files'][$fileId] = ($stats['files'][$fileId] ?? 0) + 1;
                }

                $values = [
                    'id' => (string) $id,
                    'station_id' => $stationId !== null ? (string) $stationId : null,
                    'matrix_id' => ValueCoercer::intText($r->matrix),
                    'substance_id' => $substanceId !== null ? (string) $substanceId : null,
                    'sampling_date_year' => ValueCoercer::intText($r->sampling_date_y),
                    'concentration_indicator_id' => ValueCoercer::intText($r->dic_id),
                    'concentration_value' => ValueCoercer::numericText($r->concentration_value),
                    'method_id' => ValueCoercer::intText($r->id_method),
                    'data_source_id' => ValueCoercer::intText($r->id_data),
                    'country_id' => $countryId !== null ? (string) $countryId : null,
                    'file_id' => $fileId !== null ? (string) $fileId : null,
                ];
                if (count($stats['samples']) < $this->ctx->options->sampleRows) {
                    $stats['samples'][] = [
                        'legacy' => sprintf('id=%d sus_id=%s country=%s station=%s', $id, $r->sus_id, $r->country, trim((string) $r->station_name)),
                        'station' => $createdByRun ? 'NEW (created by this run)' : 'existing PG station reused',
                        'values' => $values,
                    ];
                }
                $lines[] = ValueCoercer::copyLine(array_values($values));
            }

            return $lines;
        };

        if ($audit !== null) {
            $this->ctx->pg->transaction(function () use ($build, $audit, &$stats, $started): void {
                $lines = $build();
                $insertedIds = CopyWriter::insertMissing($this->ctx->pg, 'empodat_main', self::MAIN_COLUMNS, $lines);
                $audit->chunk(self::STEP, 'empodat_main', $insertedIds, (int) ((microtime(true) - $started) * 1000));
                $stats['inserted'] += count($insertedIds);
            });

            return;
        }

        $lines = $build();
        $ids = array_map(static fn (object $r): int => (int) $r->id, $rows);
        $present = $this->ctx->pg->table('empodat_main')->whereIn('id', $ids)->count();
        $stats['alreadyInPg'] += $present;
        $stats['wouldInsert'] += count($lines) - $present;
    }
}
