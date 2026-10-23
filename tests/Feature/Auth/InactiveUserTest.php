<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_session_keeps_working(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_open_session_of_deactivated_user_is_logged_out(): void
    {
        $user = User::factory()->create();
        $user->update(['active' => false]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => LoginRequest::INACTIVE_ACCOUNT_MESSAGE]);
        $this->assertGuest();
    }

    public function test_deactivated_user_json_request_is_forbidden(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->getJson('/profile')->assertForbidden();

        $this->assertGuest();
    }

    public function test_api_token_of_deactivated_user_is_rejected(): void
    {
        $user = User::factory()->inactive()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/empodat/substance/NS00000001');

        $response->assertForbidden();
        $response->assertJson(['message' => LoginRequest::INACTIVE_ACCOUNT_MESSAGE]);
    }
}
