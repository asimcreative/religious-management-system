<?php

namespace App\DataTransfer\Definitions;

use App\Enums\AttendanceReasonType;
use App\Enums\Status;
use App\Models\AttendanceReason;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared column/permission shape for the Salah and Quran attendance-reason
 * lists. Each subclass only declares which type it manages.
 *
 * type is never a spreadsheet column: the screen a file is uploaded through
 * already says which list it belongs to, and letting a cell decide instead
 * would let a Salah reason slip into the Quran list (or vice versa) on a
 * typo.
 */
abstract class AttendanceReasonDefinitionBase extends AbstractResourceDefinition
{
    abstract protected function type(): AttendanceReasonType;

    public function modelClass(): string
    {
        return AttendanceReason::class;
    }

    public function newQuery(): Builder
    {
        return parent::newQuery()->where('type', $this->type());
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

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function prepareForWrite(array $attributes, array $context): array
    {
        $attributes['type'] = $this->type();

        return $attributes;
    }
}
