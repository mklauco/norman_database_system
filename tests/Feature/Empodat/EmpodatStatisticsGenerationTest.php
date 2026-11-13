<?php

declare(strict_types=1);

namespace Tests\Feature\Empodat;

use App\Actions\Empodat\GenerateEmpodatStatisticsAction;
use App\Models\DatabaseEntity;
use App\Models\Statistic;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

/**
 * One run of GenerateEmpodatStatisticsAction must count every EMPODAT record
 * once per scope: public = unprotected and not deleted files, all = not
 * deleted files. The country comes from the station.
 *
 * Runs against the dedicated `norman_test` database (per `phpunit.xml`) and
 * wraps its inserts in a transaction that tearDown rolls back.
 */
class EmpodatStatisticsGenerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->markTestSkipped('Cannot connect to PostgreSQL: '.$e->getMessage());
        }

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_one_run_counts_public_and_admin_records_consistently(): void
    {
        $entity = DatabaseEntity::factory()->create(['code' => 'empodat']);

        $countryId = DB::table('list_countries')->insertGetId(['code' => 'SK', 'name' => 'Slovakia']);
        $stationId = DB::table('empodat_stations')->insertGetId([
            'name' => 'Station', 'country' => 'SK', 'country_id' => $countryId, 'latitude' => 48.1, 'longitude' => 17.1,
        ]);
        $matrixId = DB::table('list_matrices')->insertGetId([
            'name' => 'Surface water - River water', 'title' => 'Water', 'subtitle' => 'Surface water', 'type' => 'River water',
            'unit' => 'µg/l', 'empodat_matrix_link' => 'water_surface',
        ]);
        $substanceId = DB::table('susdat_substances')->insertGetId(['code' => '00009701', 'name' => 'Test substance']);
        DB::table('list_quality_empodat_analytical_methods')->insert([
            ['name' => 'Adequate', 'min_rating' => 68, 'max_rating' => 100],
            ['name' => 'Not supported', 'min_rating' => 0, 'max_rating' => 22],
        ]);
        $goodMethodId = DB::table('empodat_analytical_methods')->insertGetId(['rating' => 80]);

        $publicFile = DB::table('files')->insertGetId(['name' => 'public', 'original_name' => 'p.xlsx']);
        $protectedFile = DB::table('files')->insertGetId(['name' => 'protected', 'original_name' => 'x.xlsx', 'is_protected' => true]);
        $deletedFile = DB::table('files')->insertGetId(['name' => 'deleted', 'original_name' => 'd.xlsx', 'is_deleted' => true]);

        $insert = function (?int $fileId, int $count, ?int $methodId = null) use ($stationId, $matrixId, $substanceId): void {
            for ($i = 0; $i < $count; $i++) {
                DB::table('empodat_main')->insert([
                    'station_id' => $stationId,
                    'substance_id' => $substanceId,
                    'matrix_id' => $matrixId,
                    'method_id' => $methodId,
                    'file_id' => $fileId,
                    'country_id' => null,
                    'sampling_date_year' => 2020,
                ]);
            }
        };

        $insert($publicFile, 3, $goodMethodId);
        $insert($protectedFile, 2);
        $insert($deletedFile, 4);
        $insert(null, 5);

        $summary = app(GenerateEmpodatStatisticsAction::class)->execute();

        $this->assertSame(3, $summary['public_records']);
        $this->assertSame(5, $summary['all_records']);

        $stats = Statistic::query()->where('database_entity_id', $entity->id)->pluck('meta_data', 'key');

        foreach (['country_year', 'matrix', 'substance', 'quality'] as $key) {
            $this->assertSame(3, $stats[$key]['total_records'], $key);
            $this->assertSame(5, $stats[$key.'_all']['total_records'], $key.'_all');
            $this->assertSame($summary['generated_at'], $stats[$key]['generated_at'], $key);
        }

        $this->assertSame([2020 => 3], $stats['country_year']['data']['Slovakia']);
        $this->assertSame(3, $stats['matrix']['data'][0]['record_count']);
        $this->assertSame(3, $stats['substance']['data'][0]['record_count']);
        $this->assertSame(3, $stats['quality']['data'][0]['record_count']);
        $this->assertSame(2, collect($stats['quality_all']['data'])->firstWhere('category_id', null)['record_count']);
    }
}
