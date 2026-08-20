<?php

namespace App\Http\Requests\SalahAttendanceReason;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalahAttendanceReasonRequest extends FormRequest
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
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'counts_as_absent' => ['boolean'],
            'counts_as_leave' => ['boolean'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
