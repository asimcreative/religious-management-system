<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\Designation;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class DesignationDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'designations';
    }

    public function modelClass(): string
    {
        return Designation::class;
    }

    public function label(): string
    {
        return __('masters.designations');
    }

    public function singularLabel(): string
    {
        return __('masters.designation');
    }

    public function icon(): string
    {
        return 'bi-award';
    }

    public function indexRoute(): string
    {
        return 'masters.designations.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'designation.manage',
            'import' => 'designation.import',
            'export' => 'designation.export',
            'sample' => 'designation.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            // Employees reference their designation by name, so bulk import
            // treats the name as the natural key even though the database
            // does not constrain it.
            Column::string('designation_name')
                ->label(__('masters.designation_name'))
                ->required()
                ->unique()
                ->rules(['max:255'])
                ->sample('Officer')
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
        return ['designation_name'];
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
