<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('teacher.update') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;
        $teacherId = $this->route('teacher');

        return [
            'teacher_code' => [
                'required', 'string', 'max:50',
                Rule::unique('teachers')->where('company_id', $companyId)->ignore($teacherId),
            ],
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
                Rule::unique('teachers')->where('company_id', $companyId)->ignore($teacherId),
            ],
            'status' => ['required', 'integer', 'in:0,1,2'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => [
                'required', 'integer',
                Rule::exists('branches', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
        ];
    }
}
