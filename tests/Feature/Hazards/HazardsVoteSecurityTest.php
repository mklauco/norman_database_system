<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Models\User;
use Database\Seeders\Hazards\HazardsDataSeeder;
use Database\Seeders\Hazards\HazardsSetupSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class HazardsVoteSecurityTest extends HazardsTestCase
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

        $this->substanceId = $this->makeSubstance('22222222')->id;
        $this->writeJanus([$this->janusRow('NS22222222')]);
        $this->seed(HazardsDataSeeder::class);
        $this->rowId = (int) DB::table('hazards_comptox_substance_data')->where('source_key', 'janus:NS22222222:persistency')->value('id');

        $this->voter = User::factory()->create(['first_name' => 'Real', 'last_name' => 'Voter']);
        $this->voter->assignRole('hazards');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(array $extra = []): array
    {
        return $extra + [
            'susdat_substance_id' => $this->substanceId,
            'bucket' => 'P_pred',
            'hazards_substance_data_id' => $this->rowId,
        ];
    }

    public function test_forged_read_only_fields_are_ignored_and_derived_by_the_server(): void
    {
        $this->actingAs($this->voter)->post(route('hazards.derivation.vote'), $this->payload([
            'meta_editor' => 'Forged Editor',
            'meta_date' => '1999-01-01',
            'meta_substance_name' => 'Forged Name',
            'meta_cas' => '000-00-0',
            'meta_hazard_criterion' => 'Z',
            'meta_automated_expert_vote' => 'Forged Vote',
            'meta_title' => 'Allowed title',
            'meta_norman_vote' => '2',
        ]))->assertSessionHasNoErrors();

        $meta = DB::table('hazards_derivation_metadata')
            ->join('hazards_derivation_selections as s', 's.id', '=', 'hazards_derivation_metadata.selection_id')
            ->where('s.kind', 'vote')
            ->select('hazards_derivation_metadata.*')
            ->first();

        $this->assertNotNull($meta);
        $this->assertSame('Real Voter', trim((string) $meta->editor));
        $this->assertSame('Real Voter', trim((string) $meta->automated_expert_vote));
        $this->assertSame('P', $meta->hazard_criterion);
        $this->assertNotSame('Forged Name', $meta->substance_name);
        $this->assertNotSame('000-00-0', $meta->cas_number);
        $this->assertStringStartsNotWith('1999', (string) $meta->record_date);
        $this->assertSame('Allowed title', $meta->title);
        $this->assertSame(2, (int) $meta->norman_vote);
    }

    public function test_out_of_range_norman_vote_is_rejected(): void
    {
        $this->actingAs($this->voter)->post(route('hazards.derivation.vote'), $this->payload(['meta_norman_vote' => 30000]))
            ->assertSessionHasErrors('meta_norman_vote');

        $this->assertSame(0, $this->countRows('hazards_derivation_metadata') - $this->autoMetadataCount());
    }

    public function test_oversized_input_is_rejected(): void
    {
        $this->actingAs($this->voter)->post(route('hazards.derivation.vote'), $this->payload([
            'meta_title' => str_repeat('x', 10001),
            'meta_reference_type' => str_repeat('y', 1001),
            'meta_year' => 99999,
            'meta_ph' => 'abc',
        ]))->assertSessionHasErrors(['meta_title', 'meta_reference_type', 'meta_year', 'meta_ph']);

        $this->assertSame(0, DB::table('hazards_derivation_selections')->where('kind', 'vote')->count());
    }

    public function test_array_valued_meta_field_is_rejected(): void
    {
        $this->actingAs($this->voter)->post(route('hazards.derivation.vote'), $this->payload(['meta_title' => ['a' => 'b']]))
            ->assertSessionHasErrors('meta_title');
    }

    public function test_wrong_bucket_is_rejected(): void
    {
        $this->actingAs($this->voter)->post(route('hazards.derivation.vote'), $this->payload(['bucket' => 'T_exp']))
            ->assertSessionHasErrors('bucket');

        $this->actingAs($this->voter)->post(route('hazards.derivation.vote'), $this->payload(['bucket' => 'nonsense']))
            ->assertSessionHasErrors('bucket');

        $this->assertSame(0, DB::table('hazards_derivation_selections')->where('kind', 'vote')->count());
    }

    public function test_derivation_vote_is_forbidden_without_the_role_even_when_the_module_is_public(): void
    {
        DB::table('database_entities')->where('code', 'hazards')->update(['is_public' => true]);
        $plain = User::factory()->create();

        $this->actingAs($plain)->post(route('hazards.derivation.vote'), $this->payload())->assertForbidden();
        $this->actingAs($plain)->post(route('hazards.derivation.vote.remove'), ['selection_id' => 1])->assertForbidden();
        $this->actingAs($plain)->post(route('hazards.classification.vote'), [
            'susdat_substance_id' => $this->substanceId,
            'votes' => [],
        ])->assertForbidden();

        $this->assertSame(0, DB::table('hazards_derivation_selections')->where('kind', 'vote')->count());
    }

    public function test_classification_vote_rejects_oversized_and_unknown_input(): void
    {
        $votes = [];
        for ($i = 0; $i < 41; $i++) {
            $votes[] = ['classification_type' => 'JANUS', 'criterion' => 'P', 'classification_code' => 'vP', 'vote_value' => 1];
        }

        $this->actingAs($this->voter)->post(route('hazards.classification.vote'), [
            'susdat_substance_id' => $this->substanceId,
            'votes' => $votes,
        ])->assertSessionHasErrors('votes');

        $this->actingAs($this->voter)->post(route('hazards.classification.vote'), [
            'susdat_substance_id' => $this->substanceId,
            'votes' => [['classification_type' => 'JANUS', 'criterion' => 'X', 'classification_code' => 'vP', 'vote_value' => 9]],
        ])->assertSessionHasErrors(['votes.0.criterion', 'votes.0.vote_value']);

        $this->actingAs($this->voter)->post(route('hazards.classification.vote'), [
            'susdat_substance_id' => $this->substanceId,
            'votes' => [['classification_type' => 'JANUS', 'criterion' => 'P', 'classification_code' => 'made-up', 'vote_value' => 1]],
        ])->assertSessionHasErrors('votes');

        $this->assertSame(0, $this->countRows('hazards_classification_votes'));
    }

    public function test_classification_vote_for_a_code_the_page_offers_is_stored(): void
    {
        $code = (string) DB::table('hazards_comptox_substance_data')->where('id', $this->rowId)->value('assessment_class');
        $this->assertNotSame('', $code);

        $this->actingAs($this->voter)->post(route('hazards.classification.vote'), [
            'susdat_substance_id' => $this->substanceId,
            'votes' => [['classification_type' => 'JANUS', 'criterion' => 'P', 'classification_code' => $code, 'vote_value' => 2]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $this->countRows('hazards_classification_votes'));
    }

    public function test_selection_json_route_is_gone(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('hazards.derivation.selection.show'));
    }

    public function test_substance_data_json_does_not_leak_user_details(): void
    {
        DB::table('hazards_comptox_substance_data')->where('id', $this->rowId)->update(['editor' => $this->voter->id]);

        $json = $this->actingAs($this->voter)->get(route('hazards.derivation.substance-data.show', $this->rowId))
            ->assertOk()->getContent();

        $this->assertStringNotContainsString($this->voter->email, $json);
        $this->assertStringContainsString('Voter', $json);
    }

    public function test_non_numeric_route_ids_give_404(): void
    {
        $user = $this->voter;
        foreach ([
            '/hazards/derivation/abc',
            '/hazards/classification/abc',
            '/hazards/data/show/abc',
            '/hazards/data/form/abc',
            '/hazards/derivation/substance-data/abc',
            '/hazards/derivation/metadata/abc',
            '/hazards/derivation/metadata/abc/json',
            '/hazards/derivation/metadata/99999999999999999999',
        ] as $url) {
            $this->actingAs($user)->get($url)->assertNotFound();
        }
    }

    public function test_nested_array_search_input_does_not_error(): void
    {
        $this->actingAs($this->voter)
            ->get('/hazards/data/search/search?substances[]='.$this->substanceId.'&dataDomainSearch[a][b]=x&referenceTypeSearch[a][]=x&testTypeSearch[a][b]=1')
            ->assertOk();

        $this->actingAs($this->voter)
            ->get('/hazards/data/search/search?substances[]=1.5&substances[a][b]=2')
            ->assertRedirect(route('hazards.data.search.filter'));
    }

    private function autoMetadataCount(): int
    {
        return DB::table('hazards_derivation_metadata')
            ->join('hazards_derivation_selections as s', 's.id', '=', 'hazards_derivation_metadata.selection_id')
            ->where('s.kind', 'auto')
            ->count();
    }
}
