<?php

declare(strict_types=1);

namespace App\Http\Requests\Hazards;

use Illuminate\Foundation\Http\FormRequest;

class HazardsRemoveVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'selection_id' => ['required', 'integer', 'exists:hazards_derivation_selections,id'],
        ];
    }
}
