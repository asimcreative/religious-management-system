<?php

namespace App\Http\Requests\QuranClassAdmission;

use App\Models\Employee;
use App\Models\QuranClass;
use App\Models\QuranProgress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuranClassAdmissionRequest extends FormRequest
{
    /**
     * Admitting the member itself only ever needed quran.class.update. This
     * form additionally writes the employee's QuranProgress record, so
     * saving it also needs whichever of quran.progress.create/.update
     * actually applies — decided the same way QuranProgress's own request
     * decides it, by whether the employee already has a row.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $quranClass = $this->route('quran_class');

        if (! $quranClass instanceof QuranClass || ! $user->can('update', $quranClass)) {
            return false;
        }

        $employee = $this->route('employee');

        if (! $employee instanceof Employee) {
            return false;
        }

        $hasExistingProgress = QuranProgress::query()->where('employee_id', $employee->id)->exists();

        return $user->can($hasExistingProgress ? 'quran.progress.update' : 'quran.progress.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'quran_department_id' => [
                'required',
                Rule::exists('quran_departments', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'current_reading_level' => ['required', 'integer', 'min:1', 'max:10'],
            'previously_completed_quran' => ['required', 'boolean'],
            'admission_date' => ['required', 'date', 'before_or_equal:today'],
            'classes_per_week' => ['required', 'integer', Rule::in([5, 6])],
            'current_lesson' => ['nullable', 'string', 'max:100'],
            'current_surah' => ['nullable', 'string', 'max:100'],
            'current_sipara' => ['nullable', 'integer', 'min:1', 'max:30'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
