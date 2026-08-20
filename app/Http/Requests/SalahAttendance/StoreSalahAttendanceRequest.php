<?php

namespace App\Http\Requests\SalahAttendance;

use App\Enums\AttendanceReasonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalahAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('salah.attendance.create') || $this->user()?->can('salah.attendance.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'jamaat_id' => [
                'required',
                Rule::exists('jamaats', 'id')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'),
            ],
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', 'array'],
            'attendance.*.*' => [
                'nullable',
                Rule::exists('attendance_reasons', 'id')
                    ->where('company_id', $companyId)
                    ->where('type', AttendanceReasonType::Salah->value)
                    ->where('status', 1)
                    ->whereNull('deleted_at'),
            ],
            'remarks' => ['nullable', 'array'],
            'remarks.*' => ['nullable', 'string', 'max:500'],

            'taleem_held' => ['sometimes', 'boolean'],
            'taleem_reason_id' => [
                Rule::requiredIf(! $this->boolean('taleem_held', true)),
                'nullable',
                'integer',
                Rule::exists('attendance_reasons', 'id')
                    ->where('company_id', $companyId)
                    ->where('type', AttendanceReasonType::Salah->value)
                    ->where('status', 1)
                    ->whereNull('deleted_at'),
            ],
            'taleem_remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
