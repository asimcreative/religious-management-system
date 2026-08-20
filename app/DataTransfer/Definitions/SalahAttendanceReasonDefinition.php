<?php

namespace App\DataTransfer\Definitions;

use App\Enums\AttendanceReasonType;

class SalahAttendanceReasonDefinition extends AttendanceReasonDefinitionBase
{
    protected function type(): AttendanceReasonType
    {
        return AttendanceReasonType::Salah;
    }

    public function key(): string
    {
        return 'salah-attendance-reasons';
    }

    public function label(): string
    {
        return __('masters.salah_attendance_reasons');
    }

    public function singularLabel(): string
    {
        return __('masters.salah_attendance_reason');
    }

    public function icon(): string
    {
        return 'bi-chat-left-text';
    }

    public function indexRoute(): string
    {
        return 'masters.salah-attendance-reasons.index';
    }
}
