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
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                $isYear = is_numeric($value) && (string) (int) $value === (string) $value && (int) $value >= 2000 && (int) $value <= (int) date('Y') + 1;
                if ($value !== 'all' && ! $isYear) {
                    $fail('The year must be "all" or a year between 2000 and '.((int) date('Y') + 1).'.');
                }
            }],
            'user' => ['nullable', 'integer', 'exists:users,id'],
            'guest' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Selected year; null means all years.
     */
    public function year(): ?int
    {
        $year = $this->validated('year');

        if ($year === 'all') {
            return null;
        }

        return (int) ($year ?? date('Y'));
    }

    public function yearLabel(): string
    {
        $year = $this->year();

        return $year === null ? 'All years' : (string) $year;
    }
}
