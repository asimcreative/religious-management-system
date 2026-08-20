<?php

namespace App\Http\Requests\QuranDepartment;

use App\Rules\ValidProgressFieldsSchema;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuranDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('quran_department.manage') ?? false;
    }

    /**
     * Turns the builder's raw POST rows (comma string for options, checkbox
     * presence for required) into the exact shape that gets persisted, so
     * validated() is already the final array — no controller/service step
     * needed to reshape it.
     */
    protected function prepareForValidation(): void
    {
        $rows = $this->input('progress_fields_schema');

        if (! is_array($rows)) {
            return;
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $key = trim((string) ($row['key'] ?? ''));

            if ($label === '' && $key === '') {
                continue;
            }

            $type = $row['type'] ?? null;
            $field = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'required' => filter_var($row['required'] ?? false, FILTER_VALIDATE_BOOL),
            ];

            if ($type === 'number') {
                foreach (['min', 'max'] as $bound) {
                    $value = $row[$bound] ?? null;
                    $field[$bound] = ($value === null || $value === '') ? null : (int) $value;
                }
            }

            if ($type === 'select') {
                $options = array_values(array_filter(array_map(
                    static fn ($option) => trim((string) $option),
                    explode(',', (string) ($row['options'] ?? ''))
                ), static fn ($option) => $option !== ''));

                $field['options'] = $options;
            }

            $normalized[] = $field;
        }

        $this->merge(['progress_fields_schema' => $normalized]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'department_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'progress_fields_schema' => ['nullable', 'array', 'max:20', new ValidProgressFieldsSchema],
            'display_order' => ['required', 'integer', 'min:0', 'max:255'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
