<?php

namespace App\DataTransfer\Definitions;

use App\Models\Employee;
use App\Models\QuranDepartment;
use App\Models\QuranProgress;
use App\Models\QuranStatus;
use App\Models\Teacher;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class QuranProgressDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'quran-progress';
    }

    public function modelClass(): string
    {
        return QuranProgress::class;
    }

    public function label(): string
    {
        return __('quran_progress.quran_progress');
    }

    public function singularLabel(): string
    {
        return __('quran_progress.progress_detail');
    }

    public function icon(): string
    {
        return 'bi-graph-up-arrow';
    }

    public function indexRoute(): string
    {
        return 'quran-progress.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'quran.progress.view',
            'import' => 'quran.progress.import',
            'export' => 'quran.progress.export',
            'sample' => 'quran.progress.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            // One progress record per employee, per the (company_id,
            // employee_id) unique index. Re-importing an employee is
            // therefore an update, not a second record.
            Column::lookup('employee_id', Employee::class, 'employee_code')
                ->label(__('quran_progress.employee'))
                ->required()
                ->unique()
                ->sample('EMP-0001')
                ->width(16),

            Column::related('employee_name', 'employee', 'employee_name')
                ->label(__('employees.employee_name'))
                ->width(24),

            Column::lookup('teacher_id', Teacher::class, 'teacher_code')
                ->label(__('quran_progress.teacher'))
                ->sample('TCH-001')
                ->width(16),

            Column::lookup('quran_department_id', QuranDepartment::class, 'department_name')
                ->label(__('quran_progress.department'))
                ->sample('Hifz')
                ->width(18),

            Column::lookup('quran_status_id', QuranStatus::class, 'status_name')
                ->label(__('quran_progress.quran_status'))
                ->sample('In Progress')
                ->width(18),

            Column::string('current_lesson')
                ->label(__('quran_progress.lesson'))
                ->rules(['max:255'])
                ->sample('Lesson 12'),

            Column::string('current_surah')
                ->label(__('quran_progress.surah'))
                ->rules(['max:255'])
                ->sample('Al-Baqarah'),

            Column::integer('current_sipara')
                ->label(__('quran_progress.sipara'))
                ->rules(['min:1', 'max:30'])
                ->sample(3),

            Column::integer('current_page')
                ->label(__('quran_progress.page'))
                ->rules(['min:1', 'max:999'])
                ->sample(45),

            Column::decimal('completion_percentage')
                ->label(__('quran_progress.completion'))
                ->rules(['min:0', 'max:100'])
                ->help(__('quran_progress.completion_help'))
                ->sample(35.50),

            Column::text('remarks')
                ->label(__('quran_progress.remarks'))
                ->rules(['max:5000'])
                ->sample('')
                ->width(30),
        ];
    }

    /** @return array<int, string> */
    protected function extraEagerLoads(): array
    {
        return ['employee'];
    }

    /** @return array<string, array<int, string>> */
    protected function searchRelations(): array
    {
        return ['employee' => ['employee_name', 'employee_code']];
    }

    /** @return array<string, string> */
    protected function filters(): array
    {
        return [
            'quran_department_id' => 'quran_department_id',
            'quran_status_id' => 'quran_status_id',
            'teacher_id' => 'teacher_id',
        ];
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return ['created_at' => 'desc'];
    }

    /**
     * Progress is one living record per student, edited rather than deleted; the module has no destroy route at all.
     */
    public function supportsBulkActions(): bool
    {
        return false;
    }
}
