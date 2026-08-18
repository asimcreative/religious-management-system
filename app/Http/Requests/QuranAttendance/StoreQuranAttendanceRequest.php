<?php

namespace App\Http\Requests\QuranAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuranAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('quran.attendance.create') || $this->user()?->can('quran.attendance.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'class_id' => [
                'required',
                Rule::exists('quran_classes', 'id')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'),
            ],
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*' => [
                'nullable',
                'integer',
                Rule::exists('attendance_reasons', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 1)
                    ->whereNull('deleted_at'),
            ],
            'remarks' => ['nullable', 'array'],
            'remarks.*' => ['nullable', 'string', 'max:500'],

            'teacher_absent' => ['sometimes', 'boolean'],
            'teacher_absence_reason_id' => [
                Rule::requiredIf((bool) $this->boolean('teacher_absent')),
                'nullable',
                'integer',
                Rule::exists('attendance_reasons', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 1)
                    ->whereNull('deleted_at'),
            ],
            'teacher_absence_remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
