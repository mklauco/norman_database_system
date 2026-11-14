<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Models\User;
use App\Services\Hazards\HazardsReseedService;
use Database\Seeders\Hazards\HazardsDataSeeder;
use Database\Seeders\Hazards\HazardsSetupSeeder;
use Illuminate\Support\Facades\DB;

class HazardsReseedKeepsVotesTest extends HazardsTestCase
{
    private User $expert;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useTempStorage();
        $this->seed(HazardsSetupSeeder::class);
        $this->expert = User::factory()->create();
        $this->expert->assignRole('hazards');
    }

    private function rowId(string $sourceKey): int
    {
        return (int) DB::table('hazards_comptox_substance_data')->where('source_key', $sourceKey)->value('id');
    }

    private function vote(int $substanceId, string $sourceKey, string $bucket = 'P_pred'): int
    {
        $this->actingAs($this->expert)->post(route('hazards.derivation.vote'), [
            'susdat_substance_id' => $substanceId,
            'bucket' => $bucket,
            'hazards_substance_data_id' => $this->rowId($sourceKey),
        ])->assertSessionHasNoErrors();

        return (int) DB::table('hazards_derivation_selections')
            ->where('kind', 'vote')
            ->where('susdat_substance_id', $substanceId)
            ->where('is_current', true)
            ->value('id');
    }

    public function test_reseed_with_reordered_added_and_removed_substances_keeps_votes(): void
    {
        $x = $this->makeSubstance('11111111');
        $y = $this->makeSubstance('22222222');
        $z = $this->makeSubstance('33333333');
        $this->writeJanus([$this->janusRow('NS11111111'), $this->janusRow('NS22222222')]);
        $this->seed(HazardsDataSeeder::class);

        $voteX = $this->vote($x->id, 'janus:NS11111111:persistency');
        $voteY = $this->vote($y->id, 'janus:NS22222222:persistency');
        $oldRowIdX = $this->rowId('janus:NS11111111:persistency');
        $this->assertGreaterThan(0, $voteX);
        $this->assertTrue(DB::table('hazards_comptox_substance_data')->where('source_key', 'janus:NS22222222:carcinogenicity')->exists());

        $this->writeJanus([$this->janusRow('NS33333333'), $this->janusRow('NS11111111')]);
        $this->seed(HazardsDataSeeder::class);

        $selection = DB::table('hazards_derivation_selections')->where('id', $voteX)->first();
        $this->assertTrue((bool) $selection->is_current);
        $this->assertSame('vote', $selection->kind);
        $row = DB::table('hazards_comptox_substance_data')->where('id', $selection->hazards_substance_data_id)->first();
        $this->assertNotNull($row);
        $this->assertSame($x->id, (int) $row->susdat_substance_id);
        $this->assertSame('janus:NS11111111:persistency', $row->source_key);
        $this->assertNull($row->withdrawn_at);
        $this->assertSame($oldRowIdX, (int) $row->id);

        $selectionY = DB::table('hazards_derivation_selections')->where('id', $voteY)->first();
        $rowY = DB::table('hazards_comptox_substance_data')->where('id', $selectionY->hazards_substance_data_id)->first();
        $this->assertNotNull($rowY);
        $this->assertNotNull($rowY->withdrawn_at);
        $this->assertSame('janus:NS22222222:persistency', $rowY->source_key);

        $this->assertFalse(DB::table('hazards_comptox_substance_data')->where('source_key', 'janus:NS22222222:carcinogenicity')->exists());
        $this->assertTrue(DB::table('hazards_comptox_substance_data')->where('source_key', 'janus:NS33333333:persistency')->whereNull('withdrawn_at')->exists());
        $this->assertSame($z->id, (int) DB::table('hazards_comptox_substance_data')->where('source_key', 'janus:NS33333333:persistency')->value('susdat_substance_id'));
    }

    public function test_second_rebuild_with_unchanged_files_changes_nothing(): void
    {
        $this->makeSubstance('11111111', 'DTXSID1');
        $this->writeJanus([$this->janusRow('NS11111111')]);
        $this->writePikme([$this->pikmeRow('DTXSID1')]);
        $this->seed(HazardsDataSeeder::class);

        $summary = app(HazardsReseedService::class)->rebuild();

        $this->assertSame(0, $summary['inserted']);
        $this->assertSame(0, $summary['updated']);
        $this->assertSame(0, $summary['withdrawn']);
        $this->assertSame(0, $summary['deleted']);
        $this->assertGreaterThan(0, $summary['unchanged']);
    }

    public function test_reseeding_unchanged_files_keeps_votes_and_row_ids(): void
    {
        $x = $this->makeSubstance('11111111');
        $this->writeJanus([$this->janusRow('NS11111111')]);
        $this->seed(HazardsDataSeeder::class);
        $voteId = $this->vote($x->id, 'janus:NS11111111:persistency');
        $rowIds = DB::table('hazards_comptox_substance_data')->orderBy('id')->pluck('source_key', 'id')->all();

        $this->seed(HazardsDataSeeder::class);

        $this->assertSame($rowIds, DB::table('hazards_comptox_substance_data')->orderBy('id')->pluck('source_key', 'id')->all());
        $this->assertTrue((bool) DB::table('hazards_derivation_selections')->where('id', $voteId)->value('is_current'));
    }

    public function test_voting_for_a_withdrawn_row_is_rejected(): void
    {
        $y = $this->makeSubstance('22222222');
        $this->writeJanus([$this->janusRow('NS22222222')]);
        $this->seed(HazardsDataSeeder::class);
        $rowId = $this->rowId('janus:NS22222222:persistency');
        DB::table('hazards_comptox_substance_data')->where('id', $rowId)->update(['withdrawn_at' => now()]);

        $this->actingAs($this->expert)->post(route('hazards.derivation.vote'), [
            'susdat_substance_id' => $y->id,
            'bucket' => 'P_pred',
            'hazards_substance_data_id' => $rowId,
        ])->assertSessionHasErrors('hazards_substance_data_id');

        $this->assertSame(0, DB::table('hazards_derivation_selections')->where('kind', 'vote')->count());
    }
}
