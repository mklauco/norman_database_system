<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use RuntimeException;
use stdClass;

/**
 * Fills the position of 43 stations in empodat_stations that have none
 * (latitude and longitude both 0, or both NULL) because legacy stores them only
 * as degrees, minutes and seconds. Only `latitude` and `longitude` are written,
 * and only where both are still NULL or both are 0; a station already at the
 * approved position counts as done. A listed station that is missing or holds
 * any other value stops the run before anything is written.
 *
 * Excluded on purpose, waiting for Lubos: stations 72648, 139938 and 534700
 * (each has two positions) and legacy file 41.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileDegreeStationsSeeder' --force
 */
final class ReconcileDegreeStationsSeeder extends ReconcileSeeder
{
    private const TABLE = 'empodat_stations';

    private const TOLERANCE = 1e-9;

    /**
     * Station id => approved [latitude, longitude].
     *
     * @var array<int, array{0: float, 1: float}>
     */
    private const STATIONS = [
        72623 => [41.665556, 41.593333],
        72624 => [41.665556, 41.593333],
        72625 => [41.665556, 41.593333],
        72626 => [41.665556, 41.593333],
        72627 => [41.665556, 41.593333],
        72644 => [41.671667, 41.541667],
        72645 => [41.671667, 41.541667],
        72651 => [41.719000, 41.692333],
        72654 => [41.671667, 41.541667],
        72657 => [41.719000, 41.692333],
        72660 => [41.671667, 41.541667],
        89229 => [41.955667, 19.104722],
        89231 => [42.271778, 18.680000],
        89249 => [43.388517, 19.152306],
        89251 => [42.397381, 19.216922],
        89256 => [42.252775, 19.125744],
        89260 => [42.436361, 18.763972],
        116102 => [43.591382, 39.706759],
        133500 => [45.516111, 29.861944],
        133501 => [45.199722, 29.810000],
        133502 => [45.199722, 29.810000],
        133503 => [45.199722, 29.810000],
        133504 => [45.199722, 29.810000],
        133505 => [45.255000, 30.207778],
        133507 => [46.508889, 30.823611],
        133508 => [46.508889, 30.823611],
        133509 => [46.508889, 30.823611],
        133511 => [46.441111, 30.775556],
        139927 => [46.443400, 30.747700],
        139929 => [46.248439, 30.629730],
        139931 => [46.443400, 30.747700],
        139933 => [46.443400, 30.747700],
        139935 => [45.250000, 30.200000],
        139936 => [46.443400, 30.747700],
        139946 => [46.274116, 30.646601],
        534701 => [46.000000, 30.700000],
        534702 => [45.200000, 29.810000],
        534703 => [45.166667, 31.010000],
        534704 => [45.666667, 31.250000],
        534705 => [45.833333, 31.010000],
        534706 => [46.583333, 31.461667],
        534707 => [46.526667, 30.791667],
        534708 => [41.719444, 41.535833],
    ];

    /** @var list<int> */
    private array $toChange = [];

    protected function plan(): int
    {
        $this->toChange = [];
        $done = 0;
        $problems = [];

        $current = $this->pg->table(self::TABLE)
            ->whereIn('id', array_keys(self::STATIONS))
            ->get(['id', 'latitude', 'longitude'])
            ->keyBy('id');

        foreach (self::STATIONS as $id => $target) {
            $state = $this->state($current->get($id), $target);
            if ($state === 'change') {
                $this->toChange[] = $id;
            } elseif ($state === 'done') {
                $done++;
            } else {
                $problems[] = $id;
            }
        }

        $this->say('Listed stations: '.count(self::STATIONS));
        $this->say('Positions to fill: '.count($this->toChange));
        $this->say('Already done: '.$done);
        $this->say('Missing or other value: '.count($problems));
        if ($problems !== []) {
            $this->say('Problem ids: '.implode(', ', $problems));
        }

        return count($this->toChange);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $started = microtime(true);

        $this->pg->transaction(function () use ($audit, $started): void {
            $current = $this->pg->table(self::TABLE)
                ->whereIn('id', array_keys(self::STATIONS))
                ->lockForUpdate()
                ->get(['id', 'latitude', 'longitude'])
                ->keyBy('id');

            $ids = [];
            $before = [];
            $problems = [];
            foreach (self::STATIONS as $id => $target) {
                $row = $current->get($id);
                $state = $this->state($row, $target);
                if ($state === 'change') {
                    $ids[] = $id;
                    $before[] = [
                        'id' => $id,
                        'latitude' => $row->latitude === null ? null : (float) $row->latitude,
                        'longitude' => $row->longitude === null ? null : (float) $row->longitude,
                    ];
                } elseif ($state === 'other') {
                    $problems[] = $id;
                }
            }

            if ($problems !== []) {
                throw new RuntimeException('Stations missing or with another position than approved, nothing written: '.implode(', ', $problems));
            }

            foreach ($ids as $id) {
                $this->pg->table(self::TABLE)->where('id', $id)->update([
                    'latitude' => self::STATIONS[$id][0],
                    'longitude' => self::STATIONS[$id][1],
                ]);
            }

            $audit->logFill(
                'Stations from degrees and minutes',
                self::TABLE,
                ['latitude', 'longitude'],
                $ids,
                $before,
                (int) ((microtime(true) - $started) * 1000),
            );
            $this->say('Rows changed: '.count($ids).' of '.count($this->toChange).' planned.');
        });
    }

    protected function notes(): string
    {
        return 'Fill the position of stations that legacy stores only as degrees and minutes in empodat_stations';
    }

    /**
     * @param  array{0: float, 1: float}  $target
     * @return 'change'|'done'|'other'
     */
    private function state(?stdClass $row, array $target): string
    {
        if ($row === null) {
            return 'other';
        }

        if ($row->latitude === null && $row->longitude === null) {
            return 'change';
        }

        if ($row->latitude === null || $row->longitude === null) {
            return 'other';
        }

        $latitude = (float) $row->latitude;
        $longitude = (float) $row->longitude;
        if ($latitude === 0.0 && $longitude === 0.0) {
            return 'change';
        }

        $matches = abs($latitude - $target[0]) <= self::TOLERANCE && abs($longitude - $target[1]) <= self::TOLERANCE;

        return $matches ? 'done' : 'other';
    }
}
