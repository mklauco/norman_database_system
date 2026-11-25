<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

use App\Models\Backend\Template;
use App\Services\Backend\TemplateVersionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(TemplateVersionService $versions): array
    {
        /** @var Template $template */
        $template = $this->route('template');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'version' => ['required', 'string', 'max:20', Rule::notIn($versions->history($template)->pluck('version')->filter()->all())],
            'valid_from' => ['nullable', 'date'],
            'template_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt,zip,rar', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'version.not_in' => 'This version already exists in the history of the template.',
            'template_file.required' => 'Please select the file of the new version.',
            'template_file.max' => 'The template file must not be larger than 10 MB.',
        ];
    }
}
