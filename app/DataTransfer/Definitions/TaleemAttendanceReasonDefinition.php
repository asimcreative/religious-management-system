<?php

namespace App\DataTransfer\Definitions;

use App\Enums\AttendanceReasonType;

class TaleemAttendanceReasonDefinition extends AttendanceReasonDefinitionBase
{
    protected function type(): AttendanceReasonType
    {
        return AttendanceReasonType::Taleem;
    }

    public function key(): string
    {
        return 'taleem-attendance-reasons';
    }

    public function label(): string
    {
        return __('masters.attendance_reason_type_taleem');
    }

    public function singularLabel(): string
    {
        return __('masters.attendance_reason');
    }

    public function icon(): string
    {
        return 'bi-journal-text';
    }

    public function indexRoute(): string
    {
        return 'masters.attendance-reasons.taleem.index';
    }
}
