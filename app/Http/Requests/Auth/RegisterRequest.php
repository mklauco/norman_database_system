<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\PersonName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    /**
     * Session key holding the timestamp at which the registration form was rendered.
     */
    public const FORM_RENDERED_AT_SESSION_KEY = 'registration_form_rendered_at';

    /**
     * Hidden field that humans never see; bots that fill every input fill this one too.
     */
    public const HONEYPOT_FIELD = 'website';

    /**
     * Submissions faster than this after the form was rendered are treated as automated.
     */
    public const MIN_FILL_SECONDS = 3;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100', new PersonName],
            'last_name' => ['required', 'string', 'max:100', new PersonName],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'indisposable', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $reason = $this->botSignal();

                if ($reason === null) {
                    return;
                }

                Log::warning('Registration rejected as automated', [
                    'reason' => $reason,
                    'ip_address' => $this->ip(),
                    'email' => $this->input('email'),
                    'user_agent' => $this->userAgent(),
                ]);

                $validator->errors()->add('email', 'Registration could not be completed. Please reload the page and try again.');
            },
        ];
    }

    /**
     * Returns why the submission looks automated, or null when it looks human.
     */
    private function botSignal(): ?string
    {
        if (filled($this->input(self::HONEYPOT_FIELD))) {
            return 'honeypot';
        }

        $renderedAt = $this->session()->get(self::FORM_RENDERED_AT_SESSION_KEY);

        if (! is_int($renderedAt)) {
            return 'form_not_rendered';
        }

        if (now()->timestamp - $renderedAt < self::MIN_FILL_SECONDS) {
            return 'submitted_too_fast';
        }

        return null;
    }
}
