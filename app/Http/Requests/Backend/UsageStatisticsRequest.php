<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class UsageStatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.((int) date('Y') + 1)],
            'user' => ['nullable', 'integer', 'exists:users,id'],
            'guest' => ['nullable', 'boolean'],
        ];
    }

    public function year(): int
    {
        return (int) ($this->validated('year') ?? date('Y'));
    }
}
