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

    public function test_new_token_expires_after_twelve_months(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('apiresources.store'));

        $stored = PersonalAccessToken::query()->where('tokenable_id', $user->id)->sole();
        $this->assertTrue($stored->expires_at->equalTo(now()->addMonths(12)));
    }

    public function test_expiry_migration_dates_existing_tokens_from_their_creation(): void
    {
        $user = User::factory()->create();
        $old = $user->createToken('old')->accessToken;
        $old->forceFill(['created_at' => '2024-03-15 10:00:00'])->save();
        $dated = $user->createToken('dated', ['*'], now()->addDays(5))->accessToken;
        $datedExpiry = $dated->fresh()->expires_at;

        $migration = require database_path('migrations/2026_10_24_065653_set_twelve_month_expiry_on_existing_api_tokens.php');
        $migration->up();

        $this->assertSame('2025-03-15 10:00:00', $old->fresh()->expires_at->format('Y-m-d H:i:s'));
        $this->assertTrue($dated->fresh()->expires_at->equalTo($datedExpiry));
    }

    public function test_expired_token_is_rejected_by_the_api(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('old', ['*'], now()->subDay())->plainTextToken;

        $this->withToken($plainTextToken)->getJson('/api/v1/empodat/substance/NS00000001')->assertUnauthorized();
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
