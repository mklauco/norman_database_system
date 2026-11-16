<?php

declare(strict_types=1);

namespace App\Http\Requests\Empodat;

use Illuminate\Foundation\Http\FormRequest;

class StoreSearchTimingRequest extends FormRequest
{
    public const MAX_MS = 3600000;

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
            'data_ms' => ['required', 'integer', 'min:0', 'max:'.self::MAX_MS],
            'page_ms' => ['required', 'integer', 'min:0', 'max:'.self::MAX_MS, 'gte:data_ms'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page_ms.gte' => 'The page time must not be lower than the data time.',
        ];
    }
}
