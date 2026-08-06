<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\Branch;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class BranchDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'branches';
    }

    public function modelClass(): string
    {
        return Branch::class;
    }

    public function label(): string
    {
        return __('masters.branches');
    }

    public function singularLabel(): string
    {
        return __('masters.branch');
    }

    public function icon(): string
    {
        return 'bi-building';
    }

    public function indexRoute(): string
    {
        return 'masters.branches.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'branch.manage',
            'import' => 'branch.import',
            'export' => 'branch.export',
            'sample' => 'branch.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            // The database does not constrain branch_name, but every module
            // that references a branch does so by name, so bulk import treats
            // it as the natural key. Two branches with one name would make
            // those references ambiguous.
            Column::string('branch_name')
                ->label(__('masters.branch_name'))
                ->required()
                ->unique()
                ->rules(['max:255'])
                ->sample('Main Branch')
                ->width(28),

            Column::text('address')
                ->label(__('masters.address'))
                ->rules(['max:1000'])
                ->sample('12 Jinnah Road, Lahore')
                ->width(34),

            Column::phone('phone')
                ->label(__('masters.phone'))
                ->rules(['max:50'])
                ->sample('042-35771234'),

            // The branch form offers Active and Inactive only; import must not
            // be able to set a status the form cannot.
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
        return ['branch_name', 'address', 'phone'];
    }

    /** @return array<string, string> */
    protected function filters(): array
    {
        return ['status' => 'status'];
    }

    /**
     * Matches BranchRepository::search(), which orders newest first, so
     * "Export current page" returns the rows the user is looking at.
     *
     * @return array<string, string>
     */
    protected function defaultSort(): array
    {
        return ['created_at' => 'desc'];
    }
}
