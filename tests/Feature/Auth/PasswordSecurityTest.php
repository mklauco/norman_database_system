<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_gives_the_same_answer_for_known_and_unknown_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        foreach ([$known, $unknown] as $response) {
            $response->assertSessionHasNoErrors();
            $response->assertSessionHas('status', PasswordResetLinkController::LINK_REQUESTED_MESSAGE);
        }

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_is_throttled_per_ip(): void
    {
        Notification::fake();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/forgot-password', ['email' => "person{$attempt}@example.com"])->assertRedirect();
        }

        $this->post('/forgot-password', ['email' => 'person6@example.com'])->assertStatus(429);
    }

    public function test_reset_password_gives_the_same_error_for_unknown_email_and_bad_token(): void
    {
        $user = User::factory()->create();
        $payload = ['token' => 'not-a-real-token', 'password' => 'new-password', 'password_confirmation' => 'new-password'];

        $known = $this->post('/reset-password', $payload + ['email' => $user->email]);
        $unknown = $this->post('/reset-password', $payload + ['email' => 'nobody@example.com']);

        $known->assertSessionHasErrors(['email' => __(Password::INVALID_TOKEN)]);
        $unknown->assertSessionHasErrors(['email' => __(Password::INVALID_TOKEN)]);
    }

    public function test_reset_password_is_throttled_per_ip(): void
    {
        $payload = ['token' => 'x', 'email' => 'nobody@example.com', 'password' => 'new-password', 'password_confirmation' => 'new-password'];

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post('/reset-password', $payload)->assertRedirect();
        }

        $this->post('/reset-password', $payload)->assertStatus(429);
    }

    public function test_confirm_password_is_throttled(): void
    {
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->actingAs($user)->post('/confirm-password', ['password' => 'wrong-password'])->assertRedirect();
        }

        $this->actingAs($user)->post('/confirm-password', ['password' => 'wrong-password'])->assertStatus(429);
    }

    public function test_password_reset_revokes_api_tokens(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->createToken('script');

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasNoErrors();

            return true;
        });

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_password_change_revokes_api_tokens_and_keeps_the_current_session(): void
    {
        $user = User::factory()->create();
        $user->createToken('script');

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $user->tokens()->count());
        $this->actingAs($user)->get('/profile')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_session_started_with_an_old_password_is_logged_out(): void
    {
        $user = User::factory()->create();
        $oldPasswordHash = $user->getAuthPassword();

        $user->forceFill(['password' => Hash::make('changed-elsewhere')])->save();

        $response = $this->actingAs($user)
            ->withSession(['password_hash_web' => $oldPasswordHash])
            ->get('/profile');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
