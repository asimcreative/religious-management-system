<?php

namespace App\Http\Requests\DataTransfer;

use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\DuplicateStrategy;
use App\Support\DataTransfer\ImportMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $definition = $this->definition();

        if (! $definition->supportsImport()) {
            return false;
        }

        $permission = $definition->permissionFor('import');

        return $permission === null || ($this->user()?->can($permission) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                // "txt" is here because a hand-saved CSV frequently arrives as
                // text/plain, and rejecting it would be pedantry the user has
                // no way to act on.
                'mimes:'.implode(',', array_merge(config('data-transfer.allowed_extensions', []), ['txt'])),
                'max:'.config('data-transfer.limits.max_upload_kb', 10240),
            ],
            'mode' => ['required', Rule::in(ImportMode::values())],
            'duplicate_strategy' => ['required', Rule::in(DuplicateStrategy::values())],
        ];
    }

    public function mode(): ImportMode
    {
        return ImportMode::from($this->validated('mode'));
    }

    public function duplicateStrategy(): DuplicateStrategy
    {
        return DuplicateStrategy::from($this->validated('duplicate_strategy'));
    }

    public function definition(): ResourceDefinitionContract
    {
        /** @var ResourceDefinitionContract $definition */
        $definition = $this->route('resource');

        return $definition;
    }
}
