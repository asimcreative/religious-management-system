<?php

namespace App\Http\Requests\AttendanceReason;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attendance_reason.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason_name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:50'],
            'counts_as_absent' => ['boolean'],
            'counts_as_leave' => ['boolean'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
