<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Models\Susdat\Substance;
use Database\Seeders\Hazards\HazardsDataSeeder;
use Illuminate\Support\Facades\DB;

class HazardsDataSeederTest extends HazardsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useTempStorage();
    }

    public function test_janus_and_pikme_fixtures_are_loaded_and_resolved(): void
    {
        $a = $this->makeSubstance('11111111', 'DTXSID1');
        $b = $this->makeSubstance('22222222', 'DTXSID2');
        $this->writeJanus([$this->janusRow('NS11111111'), $this->janusRow('NS22222222')]);
        $this->writePikme([$this->pikmeRow('DTXSID1')]);

        $this->seed(HazardsDataSeeder::class);

        $this->assertSame(2, $this->countRows('hazards_janus'));
        $this->assertSame($a->id, (int) DB::table('hazards_janus')->where('norman_id', 'NS11111111')->value('susdat_substance_id'));
        $this->assertSame($b->id, (int) DB::table('hazards_janus')->where('norman_id', 'NS22222222')->value('susdat_substance_id'));
        $this->assertSame(1, $this->countRows('hazards_pikme'));
        $this->assertSame($a->id, (int) DB::table('hazards_pikme')->where('dtxid', 'DTXSID1')->value('susdat_substance_id'));
        $this->assertEquals(0.9, DB::table('hazards_janus')->where('norman_id', 'NS11111111')->value('p_assessment_index'));
    }

    public function test_susdat_substance_id_column_in_the_files_is_ignored(): void
    {
        $a = $this->makeSubstance('11111111', 'DTXSID1');
        $other = $this->makeSubstance('33333333', 'DTXSID3');
        $this->writeJanus([$this->janusRow('NS11111111', ['susdat_substance_id' => $other->id])]);
        $this->writePikme([$this->pikmeRow('DTXSID1', ['susdat_substance_id' => $other->id])]);

        $this->seed(HazardsDataSeeder::class);

        $this->assertSame($a->id, (int) DB::table('hazards_janus')->value('susdat_substance_id'));
        $this->assertSame($a->id, (int) DB::table('hazards_pikme')->value('susdat_substance_id'));
    }

    public function test_unknown_and_ambiguous_codes_resolve_to_null(): void
    {
        $this->makeSubstance('11111111', 'DTXSID1');
        Substance::factory()->create(['code' => '44444444', 'dtxid' => 'DTXSIDDUP']);
        Substance::factory()->create(['code' => '44444444', 'dtxid' => 'DTXSIDDUP']);
        $this->writeJanus([
            $this->janusRow('NS99999999'),
            $this->janusRow('NS44444444'),
            $this->janusRow('NS11111111'),
        ]);
        $this->writePikme([$this->pikmeRow('DTXSIDNONE'), $this->pikmeRow('DTXSIDDUP')]);

        $this->seed(HazardsDataSeeder::class);

        $this->assertNull(DB::table('hazards_janus')->where('norman_id', 'NS99999999')->value('susdat_substance_id'));
        $this->assertNull(DB::table('hazards_janus')->where('norman_id', 'NS44444444')->value('susdat_substance_id'));
        $this->assertNotNull(DB::table('hazards_janus')->where('norman_id', 'NS11111111')->value('susdat_substance_id'));
        $this->assertSame(3, $this->countRows('hazards_janus'));
        $this->assertNull(DB::table('hazards_pikme')->where('dtxid', 'DTXSIDNONE')->value('susdat_substance_id'));
        $this->assertNull(DB::table('hazards_pikme')->where('dtxid', 'DTXSIDDUP')->value('susdat_substance_id'));
        $this->assertSame(0, DB::table('hazards_comptox_substance_data')->whereNull('susdat_substance_id')->count());
    }

    public function test_rows_without_key_and_duplicate_keys_are_skipped(): void
    {
        $this->makeSubstance('11111111', 'DTXSID1');
        $this->writeJanus([
            $this->janusRow('NS11111111', ['SMILES' => 'FIRST']),
            $this->janusRow('', ['SMILES' => 'NOKEY']),
            $this->janusRow('NS11111111', ['SMILES' => 'DUPLICATE']),
        ]);
        $this->writePikme([
            $this->pikmeRow('DTXSID1'),
            $this->pikmeRow(''),
            $this->pikmeRow('DTXSID1', ['logkoc_pred_opera' => 9.9]),
        ]);

        $this->seed(HazardsDataSeeder::class);

        $this->assertSame(1, $this->countRows('hazards_janus'));
        $this->assertSame('FIRST', DB::table('hazards_janus')->value('smiles'));
        $this->assertSame(1, $this->countRows('hazards_pikme'));
        $this->assertEquals(2.5, DB::table('hazards_pikme')->value('logkoc_pred_opera'));
    }

    public function test_missing_file_leaves_its_table_untouched(): void
    {
        $this->makeSubstance('11111111', 'DTXSID1');
        $this->writeJanus([$this->janusRow('NS11111111')]);
        DB::table('hazards_pikme')->insert([
            'dtxid' => 'DTXSIDKEEP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(HazardsDataSeeder::class);

        $this->assertSame(1, $this->countRows('hazards_janus'));
        $this->assertSame(['DTXSIDKEEP'], DB::table('hazards_pikme')->pluck('dtxid')->all());
    }

    public function test_both_files_missing_leave_both_tables_untouched(): void
    {
        DB::table('hazards_janus')->insert(['norman_id' => 'NS5', 'created_at' => now(), 'updated_at' => now()]);

        $this->seed(HazardsDataSeeder::class);

        $this->assertSame(['NS5'], DB::table('hazards_janus')->pluck('norman_id')->all());
    }

    public function test_derived_rows_carry_stable_source_keys(): void
    {
        $this->makeSubstance('11111111', 'DTXSID1');
        $this->writeJanus([$this->janusRow('NS11111111')]);
        $this->writePikme([$this->pikmeRow('DTXSID1')]);

        $this->seed(HazardsDataSeeder::class);

        $keys = DB::table('hazards_comptox_substance_data')->pluck('source_key')->all();
        $this->assertContains('janus:NS11111111:persistency', $keys);
        $this->assertContains('janus:NS11111111:endocrine_disruption', $keys);
        $this->assertContains('pikme:DTXSID1:koc', $keys);
        $this->assertSame(
            DB::table('hazards_comptox_substance_data')->count(),
            count(array_unique(DB::table('hazards_comptox_substance_data')->selectRaw("source_key || '|' || data_domain as k")->pluck('k')->all()))
        );
    }

    public function test_reloading_replaces_the_table_content(): void
    {
        $this->makeSubstance('11111111', 'DTXSID1');
        $this->writeJanus([$this->janusRow('NS11111111'), $this->janusRow('NS22222222')]);
        $this->seed(HazardsDataSeeder::class);
        $this->assertSame(2, $this->countRows('hazards_janus'));

        $this->writeJanus([$this->janusRow('NS11111111')]);
        $this->seed(HazardsDataSeeder::class);

        $this->assertSame(1, $this->countRows('hazards_janus'));
    }
}
