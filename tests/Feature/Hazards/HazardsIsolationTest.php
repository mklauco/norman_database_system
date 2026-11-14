<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Models\DatabaseEntity;
use App\Models\User;
use Database\Seeders\Hazards\HazardsDataRestoreSeeder;
use Database\Seeders\Hazards\HazardsDataSeeder;
use Database\Seeders\Hazards\HazardsSetupSeeder;
use Illuminate\Support\Facades\DB;

class HazardsIsolationTest extends HazardsTestCase
{
    private const CONFIRMATION = 'Empty ALL 14 hazards tables, including expert votes? This cannot be undone.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->useTempStorage();

        $this->makeSubstance('11111111', 'DTXSID1');
        $this->makeSubstance('22222222', 'DTXSID2');
        DatabaseEntity::factory()->private()->create(['code' => 'other_module']);
        $this->seed(HazardsSetupSeeder::class);
        User::factory()->create()->assignRole('hazards');
        $this->writeJanus([$this->janusRow('NS11111111'), $this->janusRow('NS22222222')]);
        $this->writePikme([$this->pikmeRow('DTXSID1')]);
    }

    private function seedData(): void
    {
        $this->seed(HazardsDataSeeder::class);
    }

    private function restore(): \Illuminate\Testing\PendingCommand
    {
        return $this->artisan('db:seed', ['--class' => HazardsDataRestoreSeeder::class, '--no-interaction' => false]);
    }

    public function test_data_seeder_leaves_non_hazards_tables_byte_identical(): void
    {
        $before = $this->nonHazardsSnapshot();

        $this->seedData();

        $this->assertGreaterThan(0, $this->countRows('hazards_comptox_substance_data'));
        $this->assertSame($before, $this->nonHazardsSnapshot());
    }

    public function test_data_restore_empties_all_hazards_tables_and_leaves_the_rest_identical(): void
    {
        $this->seedData();
        $before = $this->nonHazardsSnapshot();
        $this->assertGreaterThan(0, $this->countRows('hazards_derivation_selections'));

        $this->restore()->expectsConfirmation(self::CONFIRMATION, 'yes')->assertSuccessful();

        foreach (self::HAZARDS_TABLES as $table) {
            $this->assertSame(0, $this->countRows($table), "{$table} should be empty");
        }
        $this->assertSame($before, $this->nonHazardsSnapshot());
        $this->assertDatabaseHas('roles', ['name' => 'hazards']);
        $this->assertDatabaseHas('database_entities', ['code' => 'hazards']);
    }

    public function test_data_restore_does_nothing_when_declined(): void
    {
        $this->seedData();
        $hazardsBefore = $this->snapshot(self::HAZARDS_TABLES);
        $before = $this->nonHazardsSnapshot();

        $this->restore()->expectsConfirmation(self::CONFIRMATION, 'no')->assertSuccessful();

        $this->assertSame($hazardsBefore, $this->snapshot(self::HAZARDS_TABLES));
        $this->assertSame($before, $this->nonHazardsSnapshot());
    }

    public function test_data_restore_restarts_identity_so_reseeding_starts_at_one(): void
    {
        $this->seedData();

        $this->restore()->expectsConfirmation(self::CONFIRMATION, 'yes')->assertSuccessful();
        $this->seedData();

        $this->assertSame(1, (int) DB::table('hazards_janus')->min('id'));
    }
}
