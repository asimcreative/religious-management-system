<?php

namespace App\Http\Requests\QuranProgress;

use App\Models\QuranProgress;
use App\Services\RoleDataAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveQuranProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $progress = $this->route('quran_progress');

        if ($progress instanceof QuranProgress) {
            return $user->can('update', $progress);
        }

        return $user->can('quran.progress.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;
        $progress = $this->route('quran_progress');

        $employeeRules = [
            'required',
            Rule::exists('employees', 'id')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at'),
        ];

        if ($progress instanceof QuranProgress) {
            $employeeRules[] = Rule::in([$progress->employee_id]);
        } else {
            $employeeRules[] = Rule::unique('quran_progress', 'employee_id')
                ->where('company_id', $companyId);
        }

        return [
            'employee_id' => $employeeRules,
            'teacher_id' => [
                'required',
                Rule::exists('teachers', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'quran_department_id' => [
                'required',
                Rule::exists('quran_departments', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'quran_status_id' => [
                'required',
                Rule::exists('quran_statuses', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'current_lesson' => ['nullable', 'string', 'max:100'],
            'current_surah' => ['nullable', 'string', 'max:100'],
            'current_sipara' => ['nullable', 'integer', 'min:1', 'max:30'],
            'current_page' => ['nullable', 'integer', 'min:1', 'max:604'],
            'completion_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('employee_id') || $validator->errors()->has('teacher_id')) {
                return;
            }

            $user = $this->user();

            if ($user !== null && ! app(RoleDataAccessService::class)->canManageQuranProgress(
                $user,
                (int) $this->input('employee_id'),
                (int) $this->input('teacher_id'),
            )) {
                $validator->errors()->add('employee_id', 'You may only manage progress for students assigned to your classes.');
            }
        }];
    }
}
