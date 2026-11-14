<?php

declare(strict_types=1);

namespace App\Http\Requests\Hazards;

use Illuminate\Foundation\Http\FormRequest;

class HazardsClassificationVoteRequest extends FormRequest
{
    public const MAX_VOTES = 40;

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
            'susdat_substance_id' => ['required', 'integer', 'exists:susdat_substances,id'],
            'votes' => ['nullable', 'array', 'max:'.self::MAX_VOTES],
            'votes.*' => ['array'],
            'votes.*.classification_type' => ['nullable', 'string', 'max:50'],
            'votes.*.criterion' => ['nullable', 'string', 'in:P,B,M,T'],
            'votes.*.classification_code' => ['nullable', 'string', 'max:50'],
            'votes.*.vote_value' => ['nullable', 'integer', 'between:1,3'],
        ];
    }
}
