<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee.update') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;
        $employeeId = $this->route('employee');

        return [
            'employee_code' => [
                'required', 'string', 'max:50',
                Rule::unique('employees')->where('company_id', $companyId)->ignore($employeeId),
            ],
            'employee_name' => ['required', 'string', 'max:255'],
            'cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'designation_id' => [
                'required',
                Rule::exists('designations', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'employment_status' => ['required', 'integer', 'in:0,1,2'],
            'quran_department_id' => ['nullable', Rule::exists('quran_departments', 'id')->where('company_id', $companyId)->whereNull('deleted_at')],
            'quran_status_id' => ['nullable', Rule::exists('quran_statuses', 'id')->where('company_id', $companyId)->whereNull('deleted_at')],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
