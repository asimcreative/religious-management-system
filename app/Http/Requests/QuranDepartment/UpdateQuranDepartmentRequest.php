<?php

namespace App\Http\Requests\QuranDepartment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuranDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('quran_department.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'department_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'display_order' => ['required', 'integer', 'min:0', 'max:255'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
