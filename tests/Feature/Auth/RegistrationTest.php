<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // RegisteredUserController requires the 'user' role to exist (assigned at registration).
        Role::firstOrCreate(['name' => 'user']);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSessionHas(RegisterRequest::FORM_RENDERED_AT_SESSION_KEY);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->withRenderedForm()->post('/register', $this->validPayload());

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_users_must_verify_their_email_before_using_the_site(): void
    {
        $this->withRenderedForm()->post('/register', $this->validPayload());

        $this->get(route('home'))->assertRedirect(route('verification.notice'));
    }

    public function test_dotted_domain_in_name_is_rejected(): void
    {
        $response = $this->withRenderedForm()->post('/register', $this->validPayload([
            'first_name' => 'example.com',
        ]));

        $response->assertSessionHasErrors('first_name');
        $this->assertGuest();
    }

    public function test_names_with_diacritics_apostrophes_hyphens_and_dots_are_accepted(): void
    {
        $response = $this->withRenderedForm()->post('/register', $this->validPayload([
            'first_name' => 'Ľuboš J.',
            'last_name' => "O'Brien-Čirka",
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_spam_link_in_name_is_rejected(): void
    {
        $response = $this->withRenderedForm()->post('/register', $this->validPayload([
            'first_name' => '2579wb, * * * <a href="http://example.ru/go.php">$3,222 deposit available</a> * * *',
        ]));

        $response->assertSessionHasErrors('first_name');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_digits_and_urls_in_last_name_are_rejected(): void
    {
        $response = $this->withRenderedForm()->post('/register', $this->validPayload([
            'last_name' => 'www.example.com',
        ]));

        $response->assertSessionHasErrors('last_name');
        $this->assertGuest();
    }

    public function test_disposable_email_domain_is_rejected(): void
    {
        $response = $this->withRenderedForm()->post('/register', $this->validPayload([
            'email' => 'bgg-671lbkbz0p7mt7ywd@fextemp.com',
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'bgg-671lbkbz0p7mt7ywd@fextemp.com']);
    }

    public function test_filled_honeypot_is_rejected(): void
    {
        $response = $this->withRenderedForm()->post('/register', $this->validPayload([
            RegisterRequest::HONEYPOT_FIELD => 'http://example.ru',
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_submission_without_rendering_the_form_is_rejected(): void
    {
        $response = $this->post('/register', $this->validPayload());

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_submission_faster_than_minimum_fill_time_is_rejected(): void
    {
        $response = $this->withSession([RegisterRequest::FORM_RENDERED_AT_SESSION_KEY => now()->timestamp])
            ->post('/register', $this->validPayload());

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_attempts_are_throttled_per_ip(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withRenderedForm()
                ->post('/register', $this->validPayload(['first_name' => 'Spam1']))
                ->assertSessionHasErrors('first_name');
        }

        $this->withRenderedForm()
            ->post('/register', $this->validPayload())
            ->assertStatus(429);

        $this->assertSame(0, User::query()->where('email', 'test@example.com')->count());
    }

    private function withRenderedForm(): static
    {
        return $this->withSession([
            RegisterRequest::FORM_RENDERED_AT_SESSION_KEY => now()->subSeconds(RegisterRequest::MIN_FILL_SECONDS + 10)->timestamp,
        ]);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }
}
