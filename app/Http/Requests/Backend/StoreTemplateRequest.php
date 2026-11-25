<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'version' => ['required', 'string', 'max:20'],
            'valid_from' => ['nullable', 'date'],
            'database_entity_id' => ['nullable', 'integer', 'exists:database_entities,id'],
            'template_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt,zip,rar', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template_file.required' => 'Please select the template file.',
            'template_file.max' => 'The template file must not be larger than 10 MB.',
        ];
    }
}
