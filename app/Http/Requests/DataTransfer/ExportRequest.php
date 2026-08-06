<?php

namespace App\Http\Requests\DataTransfer;

use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\ExportFormat;
use App\Support\DataTransfer\ExportOptions;
use App\Support\DataTransfer\ExportScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->definition()->permissionFor('export');

        return $permission === null || ($this->user()?->can($permission) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'format' => ['required', Rule::in(ExportFormat::values())],
            'scope' => ['required', Rule::in(ExportScope::values())],
            'ids' => ['sometimes', 'array', 'max:10000'],
            'ids.*' => ['integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];

        // Only the filters the module declares are accepted. Anything else in
        // the query string is neither validated nor carried into the query.
        foreach ($this->definition()->filterKeys() as $key) {
            $rules[$key] = ['sometimes', 'nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function options(): ExportOptions
    {
        return ExportOptions::fromInput($this->validated(), $this->definition()->filterKeys());
    }

    public function definition(): ResourceDefinitionContract
    {
        /** @var ResourceDefinitionContract $definition */
        $definition = $this->route('resource');

        return $definition;
    }
}
