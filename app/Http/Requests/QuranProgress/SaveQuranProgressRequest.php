<?php

namespace App\Http\Requests\QuranProgress;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveQuranProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->can('quran.progress.create') || $user->can('quran.progress.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'teacher_id' => [
                'required',
                Rule::exists('teachers', 'id')->where('company_id', $companyId),
            ],
            'quran_department_id' => [
                'required',
                Rule::exists('quran_departments', 'id')->where('company_id', $companyId),
            ],
            'quran_status_id' => [
                'required',
                Rule::exists('quran_statuses', 'id')->where('company_id', $companyId),
            ],
            'current_lesson' => ['nullable', 'string', 'max:100'],
            'current_surah' => ['nullable', 'string', 'max:100'],
            'current_sipara' => ['nullable', 'integer', 'min:1', 'max:30'],
            'current_page' => ['nullable', 'integer', 'min:1', 'max:604'],
            'completion_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
