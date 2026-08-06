<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\AttendanceReason;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class AttendanceReasonDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'attendance-reasons';
    }

    public function modelClass(): string
    {
        return AttendanceReason::class;
    }

    public function label(): string
    {
        return __('masters.attendance_reasons');
    }

    public function singularLabel(): string
    {
        return __('masters.attendance_reason');
    }

    public function icon(): string
    {
        return 'bi-chat-left-text';
    }

    public function indexRoute(): string
    {
        return 'masters.attendance-reasons.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'attendance_reason.manage',
            'import' => 'attendance_reason.import',
            'export' => 'attendance_reason.export',
            'sample' => 'attendance_reason.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::string('reason_name')
                ->label(__('masters.reason_name'))
                ->required()
                ->unique()
                ->rules(['max:255'])
                ->sample('Sick Leave')
                ->width(24),

            Column::string('color')
                ->label(__('masters.color'))
                ->rules(['regex:/^#[0-9A-Fa-f]{6}$/'])
                ->help(__('masters.color_help'))
                ->sample('#F59E0B'),

            Column::string('icon')
                ->label(__('masters.icon'))
                ->rules(['max:50'])
                ->help(__('masters.icon_help'))
                ->sample('bi-thermometer'),

            // These two decide how attendance reports classify a record, so
            // they are worth stating explicitly in the template rather than
            // leaving to a default.
            Column::boolean('counts_as_absent')
                ->label(__('masters.counts_as_absent'))
                ->help(__('masters.counts_as_absent_help'))
                ->sample(__('data_transfer.no')),

            Column::boolean('counts_as_leave')
                ->label(__('masters.counts_as_leave'))
                ->help(__('masters.counts_as_leave_help'))
                ->sample(__('data_transfer.yes')),

            Column::enum('status', Status::class)
                ->label(__('masters.status'))
                ->required()
                ->only([Status::Active, Status::Inactive])
                ->sample('Active'),
        ];
    }

    /** @return array<int, string> */
    protected function searchColumns(): array
    {
        return ['reason_name'];
    }

    /** @return array<string, string> */
    protected function filters(): array
    {
        return ['status' => 'status'];
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return ['created_at' => 'desc'];
    }
}
