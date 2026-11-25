<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

use App\Models\Backend\Template;
use App\Services\Backend\TemplateVersionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTemplateRequest extends FormRequest
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
        $otherVersions = $versions->history($template)->where('id', '!=', $template->id)->pluck('version')->filter()->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'version' => ['required', 'string', 'max:20', Rule::notIn($otherVersions)],
            'valid_from' => ['nullable', 'date'],
            'database_entity_id' => ['nullable', 'integer', 'exists:database_entities,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'version.not_in' => 'This version already exists in the history of the template.',
        ];
    }
}
