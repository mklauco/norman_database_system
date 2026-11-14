<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Models\User;
use App\Services\Hazards\HazardsClassificationService;
use Database\Seeders\Hazards\HazardsDataSeeder;
use Database\Seeders\Hazards\HazardsSetupSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class HazardsRecomputeTest extends HazardsTestCase
{
    private int $substanceId;

    private int $rowId;

    private User $voter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->useTempStorage();
        $this->seed(HazardsSetupSeeder::class);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->substanceId = $this->makeSubstance('33333333')->id;
        $this->writeJanus([$this->janusRow('NS33333333')]);
        $this->seed(HazardsDataSeeder::class);
        $this->rowId = (int) DB::table('hazards_comptox_substance_data')->where('source_key', 'janus:NS33333333:persistency')->value('id');

        $this->voter = User::factory()->create(['first_name' => 'Real', 'last_name' => 'Voter']);
        $this->voter->assignRole('hazards');
    }

    private function autoSelectionId(): int
    {
        return (int) DB::table('hazards_derivation_selections')
            ->where('kind', 'auto')
            ->where('is_current', true)
            ->where('bucket', 'P_pred')
            ->value('id');
    }

    public function test_auto_selections_create_no_metadata_rows(): void
    {
        $this->assertGreaterThan(0, DB::table('hazards_derivation_selections')->where('kind', 'auto')->count());
        $this->assertSame(0, $this->countRows('hazards_derivation_metadata'));
    }

    public function test_derivation_page_shows_the_live_row_values_of_an_auto_selection(): void
    {
        DB::table('hazards_comptox_substance_data')->where('id', $this->rowId)->update(['assessment_class' => 'LiveClassX']);

        $this->actingAs($this->voter)
            ->get(route('hazards.derivation.index', ['susdatSubstanceId' => $this->substanceId]))
            ->assertOk()
            ->assertSee('LiveClassX');
    }

    public function test_metadata_page_and_json_of_an_auto_selection_render_from_the_live_row(): void
    {
        DB::table('hazards_comptox_substance_data')->where('id', $this->rowId)->update(['assessment_class' => 'LiveClassY']);
        $selectionId = $this->autoSelectionId();

        $this->actingAs($this->voter)
            ->get(route('hazards.derivation.metadata.show', $selectionId))
            ->assertOk()
            ->assertSee('LiveClassY');

        $this->actingAs($this->voter)
            ->getJson(route('hazards.derivation.metadata.json', $selectionId))
            ->assertOk()
            ->assertJsonPath('norman_classification', 'LiveClassY')
            ->assertJsonPath('norman_vote', 1)
            ->assertJsonPath('editor', 'NDS EXPERT');

        $this->assertSame(0, $this->countRows('hazards_derivation_metadata'));
    }

    public function test_a_vote_still_creates_its_snapshot(): void
    {
        $this->actingAs($this->voter)->post(route('hazards.derivation.vote'), [
            'susdat_substance_id' => $this->substanceId,
            'bucket' => 'P_pred',
            'hazards_substance_data_id' => $this->rowId,
        ])->assertSessionHasNoErrors();

        $voteId = (int) DB::table('hazards_derivation_selections')->where('kind', 'vote')->value('id');
        $this->assertSame(1, DB::table('hazards_derivation_metadata')->where('selection_id', $voteId)->count());
        $this->assertSame(1, $this->countRows('hazards_derivation_metadata'));
        $this->assertSame(1, DB::table('hazards_substance_classifications')->where('kind', 'derivation')->where('is_current', true)->count());

        $this->actingAs($this->voter)->getJson(route('hazards.derivation.metadata.json', $voteId))->assertOk();
    }

    public function test_recomputing_twice_changes_nothing(): void
    {
        $this->actingAs($this->voter)->post(route('hazards.derivation.vote'), [
            'susdat_substance_id' => $this->substanceId,
            'bucket' => 'P_pred',
            'hazards_substance_data_id' => $this->rowId,
        ])->assertSessionHasNoErrors();

        $tables = ['hazards_derivation_selections', 'hazards_derivation_metadata', 'hazards_substance_classifications', 'hazards_classification_supports'];
        $service = app(HazardsClassificationService::class);

        $service->runMany([$this->substanceId]);
        $before = $this->snapshot($tables);
        $service->runMany([$this->substanceId]);
        $service->run($this->substanceId);

        $this->assertSame($before, $this->snapshot($tables));
    }
}
