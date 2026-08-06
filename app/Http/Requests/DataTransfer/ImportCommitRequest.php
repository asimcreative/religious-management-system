<?php

namespace App\Http\Requests\DataTransfer;

use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Confirms an import that has already been staged and dry-run.
 *
 * The client sends the log id, never a file path. The file lives on a private
 * disk under a name the browser has never seen, so there is nothing here for a
 * caller to point somewhere else.
 */
class ImportCommitRequest extends FormRequest
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
            'import_log_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function importLogId(): int
    {
        return (int) $this->validated('import_log_id');
    }

    public function definition(): ResourceDefinitionContract
    {
        /** @var ResourceDefinitionContract $definition */
        $definition = $this->route('resource');

        return $definition;
    }
}
