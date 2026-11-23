<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

use App\Services\Backend\UserActivityStatisticsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserActivityTimelineRequest extends FormRequest
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
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.app(UserActivityStatisticsService::class)->today()],
            'user' => ['nullable', 'integer', 'exists:users,id'],
            'area' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', Rule::in(array_keys(UserActivityStatisticsService::ACTION_LABELS))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.date_format' => 'The date must be in the format YYYY-MM-DD.',
            'date.before_or_equal' => 'The date cannot be in the future.',
            'user.exists' => 'The selected user does not exist.',
            'action.in' => 'The selected action is not valid.',
        ];
    }

    /**
     * Selected date (Y-m-d); defaults to today in the display timezone.
     */
    public function selectedDate(): string
    {
        return $this->filled('date') ? (string) $this->validated('date') : app(UserActivityStatisticsService::class)->today();
    }

    public function selectedUserId(): ?int
    {
        return $this->filled('user') ? (int) $this->validated('user') : null;
    }

    public function selectedArea(): ?string
    {
        return $this->filled('area') ? (string) $this->validated('area') : null;
    }

    public function selectedAction(): ?string
    {
        return $this->filled('action') ? (string) $this->validated('action') : null;
    }
}
