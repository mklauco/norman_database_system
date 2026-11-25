<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use RuntimeException;

/**
 * Restores the minus sign of the longitude of 159 western stations (112 FR,
 * 47 GB) in empodat_stations, which was lost at import. Only `longitude` is
 * written, and only where it still equals the approved positive value; a
 * station already at the negative value counts as done. A listed station that
 * is missing or holds any other value stops the run before anything is written.
 *
 * Seven Spanish stations (41470, 41477, 41540, 41701, 41718, 41846, 41849) are
 * deliberately excluded: legacy's West flag on file 164 is wrong (waiting for
 * Lubos).
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileWesternStationsSeeder' --force
 */
final class ReconcileWesternStationsSeeder extends ReconcileSeeder
{
    private const TABLE = 'empodat_stations';

    private const TOLERANCE = 1e-9;

    /**
     * Station id => current (wrong) positive longitude; French stations first, then British.
     *
     * @var array<int, float>
     */
    private const STATIONS = [
        44401 => 0.7441,
        44894 => 0.65063391888889,
        44933 => 1.2894498258333,
        46584 => 1.0922123952778,
        46681 => 0.48308232777778,
        46835 => 0.064799431388889,
        47277 => 0.39832594111111,
        47278 => 0.39892543222222,
        47310 => 0.40663363305556,
        47350 => 1.4040824622222,
        47824 => 1.2427977530556,
        47826 => 0.88473250611111,
        47827 => 0.88895911472222,
        47925 => 0.50312068638889,
        48558 => 1.0191834097222,
        48669 => 1.51895066,
        48824 => 0.072055432777778,
        49030 => 1.0627003402778,
        49471 => 0.12799942527778,
        50343 => 0.30688988194444,
        50420 => 0.59474023861111,
        51005 => 0.0994108825,
        51279 => 0.14288,
        51677 => 1.1706456472222,
        51739 => 0.01021,
        52767 => 1.2960998247222,
        53000 => 1.0117585477778,
        53639 => 0.111,
        53800 => 1.0622492661111,
        53893 => 1.0972674844444,
        54366 => 0.5256,
        54860 => 0.88090618833333,
        55318 => 0.37914981388889,
        55547 => 0.0233,
        55551 => 0.65741,
        56432 => 0.52308,
        58683 => 0.36596,
        58687 => 0.62252,
        58692 => 1.0781323158333,
        58734 => 1.4745322672222,
        58837 => 0.245,
        58844 => 0.16938,
        58848 => 1.4787033897222,
        58913 => 1.0803498977778,
        59148 => 1.0737673694444,
        59149 => 0.38250201333333,
        59249 => 0.74922211694444,
        59256 => 0.9648506525,
        59303 => 1.3986486230556,
        59679 => 0.69473356388889,
        59865 => 0.082148281388889,
        59930 => 0.07411,
        60006 => 0.3378586925,
        60009 => 0.061340768333333,
        60013 => 0.15286417805556,
        60116 => 1.5569164605556,
        60123 => 1.5281129925,
        60161 => 1.4055006980556,
        60348 => 0.016945446944444,
        60703 => 0.35665,
        60761 => 1.3932325397222,
        60969 => 1.3641001861111,
        60970 => 1.3694174477778,
        61054 => 0.83902288583333,
        61110 => 0.08005,
        61111 => 0.23216,
        61127 => 0.42942,
        61128 => 0.14619,
        61158 => 0.58603,
        61159 => 0.62923,
        61170 => 0.50224,
        61173 => 0.4718,
        61506 => 0.31864,
        61816 => 0.19154,
        61835 => 0.0967,
        61839 => 0.19896,
        61843 => 0.24393,
        61850 => 0.30219,
        61851 => 0.49136,
        61902 => 0.19288,
        61918 => 1.1273248313889,
        61919 => 1.1155599552778,
        63000 => 1.2466674327778,
        63119 => 0.46517990305556,
        63469 => 0.94123258527778,
        63568 => 0.20279,
        63631 => 0.88888328111111,
        64113 => 1.2927824061111,
        64123 => 0.51822132361111,
        64125 => 1.3750027777778,
        64150 => 1.6002352977778,
        64151 => 0.67159927666667,
        64153 => 1.0736386769444,
        64186 => 1.4624355680556,
        64195 => 0.16421883194444,
        64196 => 1.3929494625,
        64340 => 0.86273373083333,
        64679 => 0.17381628305556,
        64729 => 0.161200055,
        64947 => 1.7550377783333,
        65013 => 0.90161751666667,
        65024 => 1.2917990163889,
        66068 => 0.5304108075,
        66101 => 1.2617836805556,
        66102 => 1.2495262319444,
        66171 => 1.5722686313889,
        66278 => 0.82855006388889,
        66493 => 0.15464481777778,
        66636 => 1.3877251580556,
        66637 => 1.4072970986111,
        66988 => 1.4098973322222,
        67008 => 0.88650062361111,
        67342 => 3.2692225,
        67344 => 3.1620286111111,
        67346 => 3.6656658333333,
        67348 => 3.3926580555556,
        67350 => 3.6389547222222,
        67352 => 3.6445447222222,
        67354 => 4.4505169444444,
        67355 => 3.0284883333333,
        67767 => 3.96701,
        67856 => 2.3771594444444,
        67889 => 1.3374333333333,
        67932 => 1.3363391666667,
        68054 => 0.28556583333333,
        68073 => 3.7608388888889,
        68166 => 3.1241608333333,
        68468 => 3.1931936111111,
        68472 => 2.4103666666667,
        68621 => 3.4755380555556,
        68781 => 3.2964938888889,
        68801 => 3.4009008333333,
        68803 => 2.6740602777778,
        68805 => 2.7121791666667,
        68945 => 3.5690205555556,
        68957 => 0.4871375,
        68960 => 0.22185777777778,
        68961 => 0.94149361111111,
        69067 => 1.6367544444444,
        69085 => 1.8546547222222,
        69104 => 3.7626788888889,
        69108 => 3.7581191666667,
        69128 => 1.8757213888889,
        69136 => 3.7608427777778,
        69138 => 3.8803647222222,
        69140 => 3.7477369444444,
        69199 => 1.3347391666667,
        69246 => 4.0957983333333,
        69305 => 3.4320158333333,
        69319 => 3.9736058333333,
        71967 => 3.2878425,
        71968 => 3.4642027777778,
        71969 => 3.4958041666667,
        71987 => 2.2060711111111,
        72047 => 1.5135472222222,
        72148 => 3.3646605555556,
        72310 => 3.7807019444444,
        72312 => 3.7072683333333,
        72314 => 3.7067016666667,
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
            ->pluck('longitude', 'id');

        foreach (self::STATIONS as $id => $expected) {
            $state = $this->state($current->get($id), $expected);
            if ($state === 'change') {
                $this->toChange[] = $id;
            } elseif ($state === 'done') {
                $done++;
            } else {
                $problems[] = $id;
            }
        }

        $this->say('Listed stations: '.count(self::STATIONS));
        $this->say('Longitude sign to restore: '.count($this->toChange));
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
                ->pluck('longitude', 'id');

            $ids = [];
            $before = [];
            $problems = [];
            foreach (self::STATIONS as $id => $expected) {
                $state = $this->state($current->get($id), $expected);
                if ($state === 'change') {
                    $ids[] = $id;
                    $before[] = ['id' => $id, 'longitude' => (float) $current->get($id)];
                } elseif ($state === 'other') {
                    $problems[] = $id;
                }
            }

            if ($problems !== []) {
                throw new RuntimeException('Stations missing or with another longitude than approved, nothing written: '.implode(', ', $problems));
            }

            foreach ($ids as $id) {
                $this->pg->table(self::TABLE)->where('id', $id)->update(['longitude' => -self::STATIONS[$id]]);
            }

            $audit->logFill(
                'Western stations longitude sign',
                self::TABLE,
                ['longitude'],
                $ids,
                $before,
                (int) ((microtime(true) - $started) * 1000),
            );
            $this->say('Rows changed: '.count($ids).' of '.count($this->toChange).' planned.');
        });
    }

    protected function notes(): string
    {
        return 'Restore the lost minus sign of the longitude of western stations in empodat_stations';
    }

    /** @return 'change'|'done'|'other' */
    private function state(mixed $longitude, float $expected): string
    {
        if ($longitude === null) {
            return 'other';
        }

        $value = (float) $longitude;
        if (abs($value - $expected) <= self::TOLERANCE) {
            return 'change';
        }

        return abs($value + $expected) <= self::TOLERANCE ? 'done' : 'other';
    }
}
