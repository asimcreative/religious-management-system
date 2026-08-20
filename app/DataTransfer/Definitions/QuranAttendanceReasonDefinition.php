<?php

namespace App\DataTransfer\Definitions;

use App\Enums\AttendanceReasonType;

class QuranAttendanceReasonDefinition extends AttendanceReasonDefinitionBase
{
    protected function type(): AttendanceReasonType
    {
        return AttendanceReasonType::Quran;
    }

    public function key(): string
    {
        return 'quran-attendance-reasons';
    }

    public function label(): string
    {
        return __('masters.quran_attendance_reasons');
    }

    public function singularLabel(): string
    {
        return __('masters.quran_attendance_reason');
    }

    public function icon(): string
    {
        return 'bi-chat-square-text';
    }

    public function indexRoute(): string
    {
        return 'masters.quran-attendance-reasons.index';
    }
}
