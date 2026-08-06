<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\QuranClass;
use App\Models\Teacher;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class QuranClassDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'quran-classes';
    }

    public function modelClass(): string
    {
        return QuranClass::class;
    }

    public function label(): string
    {
        return __('quran_classes.quran_classes');
    }

    public function singularLabel(): string
    {
        return __('quran_classes.class_name');
    }

    public function icon(): string
    {
        return 'bi-book';
    }

    public function indexRoute(): string
    {
        return 'quran-classes.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'quran.class.view',
            'import' => 'quran.class.import',
            'export' => 'quran.class.export',
            'sample' => 'quran.class.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::string('class_code')
                ->label(__('quran_classes.class_code'))
                ->required()
                ->unique()
                ->rules(['max:50'])
                ->sample('QC-001')
                ->width(14),

            Column::string('class_name')
                ->label(__('quran_classes.class_name'))
                ->required()
                ->rules(['max:100'])
                ->sample('Morning Hifz')
                ->width(24),

            Column::lookup('teacher_id', Teacher::class, 'teacher_code')
                ->label(__('quran_classes.teacher'))
                ->required()
                ->sample('TCH-001')
                ->width(16),

            Column::related('teacher_name', 'teacher.employee', 'employee_name')
                ->label(__('teachers.teacher_name'))
                ->width(24),

            Column::lookup('branch_id', Branch::class, 'branch_name')
                ->label(__('quran_classes.branch'))
                ->required()
                ->sample('Main Branch')
                ->width(20),

            Column::time('start_time')
                ->label(__('quran_classes.start_time'))
                ->sample('08:00'),

            Column::time('end_time')
                ->label(__('quran_classes.end_time'))
                ->sample('10:00'),

            Column::integer('max_strength')
                ->label(__('quran_classes.max_strength'))
                ->required()
                ->rules(['min:1', 'max:999'])
                ->help(__('quran_classes.max_strength_help'))
                ->sample(25),

            Column::enum('status', Status::class)
                ->label(__('quran_classes.status'))
                ->required()
                ->sample('Active'),
        ];
    }

    /**
     * teacher.employee is read by the computed teacher name column, which no
     * lookup implies on its own.
     *
     * @return array<int, string>
     */
    protected function extraEagerLoads(): array
    {
        return ['teacher.employee'];
    }

    /**
     * End time must follow start time, exactly as StoreQuranClassRequest
     * enforces it on the form.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function validateRow(array $attributes, array $context): array
    {
        $start = $attributes['start_time'] ?? null;
        $end = $attributes['end_time'] ?? null;

        if ($start !== null && $end !== null && $end <= $start) {
            return [__('data_transfer.errors.invalid', ['column' => __('quran_classes.end_time')])];
        }

        return [];
    }

    /** @return array<int, string> */
    protected function searchColumns(): array
    {
        return ['class_name', 'class_code'];
    }

    /** @return array<string, string> */
    protected function filters(): array
    {
        return [
            'branch_id' => 'branch_id',
            'teacher_id' => 'teacher_id',
            'status' => 'status',
        ];
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return ['created_at' => 'desc'];
    }
}
