<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_plain_text_token_column_no_longer_exists(): void
    {
        $this->assertFalse(Schema::hasColumn('personal_access_tokens', 'plain_text_token'));
    }

    public function test_new_token_is_flashed_once_and_only_its_hash_is_stored(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('apiresources.index'))
            ->post(route('apiresources.store'));

        $response->assertRedirect(route('apiresources.index'));
        $response->assertSessionHas('new_api_token');

        $plainTextToken = session('new_api_token');
        [, $secret] = explode('|', $plainTextToken, 2);

        $stored = PersonalAccessToken::query()->where('tokenable_id', $user->id)->sole();
        $this->assertSame(hash('sha256', $secret), $stored->token);
        $this->assertStringNotContainsString($secret, json_encode($stored->getAttributes()));
    }

    public function test_page_warns_that_the_new_token_will_not_be_shown_again(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['new_api_token' => '1|example-secret'])
            ->get(route('apiresources.index'));

        $response->assertOk();
        $response->assertSee('1|example-secret');
        $response->assertSee('It will NOT be shown again.');
    }

    public function test_page_does_not_show_existing_tokens(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('existing')->plainTextToken;

        $response = $this->actingAs($user)->get(route('apiresources.index'));

        $response->assertOk();
        $response->assertSee('existing');
        $response->assertDontSee($plainTextToken);
    }
}
