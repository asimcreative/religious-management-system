<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('teacher.create') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'teacher_code' => [
                'required', 'string', 'max:50',
                Rule::unique('teachers')->where('company_id', $companyId),
            ],
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
                Rule::unique('teachers')->where('company_id', $companyId),
            ],
            'status' => ['required', 'integer', 'in:0,1,2'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => [
                'required', 'integer',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
            ],
        ];
    }
}
