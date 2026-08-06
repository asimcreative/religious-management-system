<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\QuranDepartment;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class QuranDepartmentDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'quran-departments';
    }

    public function modelClass(): string
    {
        return QuranDepartment::class;
    }

    public function label(): string
    {
        return __('masters.quran_departments');
    }

    public function singularLabel(): string
    {
        return __('masters.quran_department');
    }

    public function icon(): string
    {
        return 'bi-bookmarks';
    }

    public function indexRoute(): string
    {
        return 'masters.quran-departments.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'quran_department.manage',
            'import' => 'quran_department.import',
            'export' => 'quran_department.export',
            'sample' => 'quran_department.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::string('department_name')
                ->label(__('masters.department_name'))
                ->required()
                ->unique()
                ->rules(['max:255'])
                ->sample('Hifz')
                ->width(24),

            Column::text('description')
                ->label(__('masters.description'))
                ->rules(['max:1000'])
                ->sample('Memorisation stream')
                ->width(34),

            Column::integer('display_order')
                ->label(__('masters.display_order'))
                ->required()
                ->rules(['min:0', 'max:255'])
                ->help(__('masters.display_order_help'))
                ->sample(1),

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
        return ['department_name', 'description'];
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
