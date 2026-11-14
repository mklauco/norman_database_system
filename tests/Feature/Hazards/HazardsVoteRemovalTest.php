<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Models\User;
use Database\Seeders\Hazards\HazardsDataSeeder;
use Database\Seeders\Hazards\HazardsSetupSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class HazardsVoteRemovalTest extends HazardsTestCase
{
    private int $substanceId;

    private int $selectionId;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useTempStorage();
        $this->seed(HazardsSetupSeeder::class);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->substanceId = $this->makeSubstance('11111111')->id;
        $this->writeJanus([$this->janusRow('NS11111111')]);
        $this->seed(HazardsDataSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('hazards');
        $this->actingAs($this->owner)->post(route('hazards.derivation.vote'), [
            'susdat_substance_id' => $this->substanceId,
            'bucket' => 'P_pred',
            'hazards_substance_data_id' => (int) DB::table('hazards_comptox_substance_data')->where('source_key', 'janus:NS11111111:persistency')->value('id'),
        ])->assertSessionHasNoErrors();
        $this->selectionId = (int) DB::table('hazards_derivation_selections')->where('kind', 'vote')->value('id');
    }

    private function isCurrent(): bool
    {
        return (bool) DB::table('hazards_derivation_selections')->where('id', $this->selectionId)->value('is_current');
    }

    private function remove(User $user, bool $expectForbidden = false): void
    {
        $response = $this->actingAs($user)->post(route('hazards.derivation.vote.remove'), ['selection_id' => $this->selectionId]);

        if ($expectForbidden) {
            $response->assertForbidden();
        } else {
            $response->assertRedirect();
        }
    }

    public function test_another_hazards_user_cannot_remove_the_vote(): void
    {
        $other = User::factory()->create();
        $other->assignRole('hazards');

        $this->remove($other, true);

        $this->assertTrue($this->isCurrent());
    }

    public function test_the_owner_can_remove_the_vote(): void
    {
        $this->remove($this->owner);

        $this->assertFalse($this->isCurrent());
    }

    public function test_an_admin_can_remove_any_vote(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->remove($admin);

        $this->assertFalse($this->isCurrent());
    }

    public function test_user_without_role_cannot_remove_a_vote(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('hazards.derivation.vote.remove'), ['selection_id' => $this->selectionId])
            ->assertForbidden();

        $this->assertTrue($this->isCurrent());
    }

    public function test_removing_an_unknown_selection_fails_validation(): void
    {
        $this->actingAs($this->owner)
            ->post(route('hazards.derivation.vote.remove'), ['selection_id' => 999999])
            ->assertSessionHasErrors('selection_id');
    }
}
