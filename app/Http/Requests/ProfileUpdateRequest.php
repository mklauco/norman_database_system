<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\PersonName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100', new PersonName],
            'last_name' => ['required', 'string', 'max:100', new PersonName],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'indisposable', Rule::unique(User::class)->ignore($this->user()->id)],
            'username' => ['nullable', 'string', 'max:255'],
            'salutation' => ['nullable', 'string', 'max:10'],
            'organisation_id' => ['nullable', 'integer', 'exists:list_data_source_organisations,id'],
            'country_id' => ['nullable', 'integer', 'exists:list_countries,id'],
        ];
    }
}
