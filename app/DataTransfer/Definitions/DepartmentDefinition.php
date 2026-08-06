<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\Department;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class DepartmentDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'departments';
    }

    public function modelClass(): string
    {
        return Department::class;
    }

    public function label(): string
    {
        return __('masters.departments');
    }

    public function singularLabel(): string
    {
        return __('masters.department');
    }

    public function icon(): string
    {
        return 'bi-diagram-3';
    }

    public function indexRoute(): string
    {
        return 'masters.departments.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'department.manage',
            'import' => 'department.import',
            'export' => 'department.export',
            'sample' => 'department.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            // Employees reference their department by name, so bulk import
            // treats the name as the natural key even though the database
            // does not constrain it.
            Column::string('department_name')
                ->label(__('masters.department_name'))
                ->required()
                ->unique()
                ->rules(['max:255'])
                ->sample('Administration')
                ->width(28),

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
        return ['department_name'];
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
