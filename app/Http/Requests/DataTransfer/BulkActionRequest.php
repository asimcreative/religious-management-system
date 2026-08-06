<?php

namespace App\Http\Requests\DataTransfer;

use App\Support\DataTransfer\BulkAction;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A bulk action over a set of selected rows.
 *
 * authorize() only establishes that the caller may be in this module at all;
 * the real decision is per record and belongs to the policy, which
 * BulkActionService applies to each one.
 */
class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->definition()->permissionFor('view');

        return $permission === null || ($this->user()?->can($permission) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(BulkAction::values())],
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in($this->allowedStatusValues())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('action') === BulkAction::Status->value && ! filled($this->input('status'))) {
                $validator->errors()->add('status', __('data_transfer.errors.required', [
                    'column' => __('data_transfer.bulk.status_label'),
                ]));
            }
        });
    }

    public function action(): BulkAction
    {
        return BulkAction::from((string) $this->validated('action'));
    }

    /** @return array<int, int> */
    public function ids(): array
    {
        return array_map('intval', $this->validated('ids'));
    }

    /**
     * The stored values this module's status column accepts.
     *
     * Derived from the definition, so a caller cannot set a status the module
     * does not offer.
     *
     * @return array<int, string>
     */
    private function allowedStatusValues(): array
    {
        $column = $this->definition()->statusColumn();

        if ($column === null) {
            return [];
        }

        return array_map(
            static fn (mixed $value): string => (string) $value,
            array_keys($column->valueMap()),
        );
    }

    public function definition(): ResourceDefinitionContract
    {
        /** @var ResourceDefinitionContract $definition */
        $definition = $this->route('resource');

        return $definition;
    }
}
