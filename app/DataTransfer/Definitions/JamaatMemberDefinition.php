<?php

namespace App\DataTransfer\Definitions;

use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatMember;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use Illuminate\Database\Eloquent\Builder;

class JamaatMemberDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'jamaat-members';
    }

    public function modelClass(): string
    {
        return JamaatMember::class;
    }

    public function label(): string
    {
        return __('jamaats.members');
    }

    public function singularLabel(): string
    {
        return __('jamaats.add_member');
    }

    public function icon(): string
    {
        return 'bi-person-plus';
    }

    public function indexRoute(): ?string
    {
        return null;
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'jamaat.view',
            'import' => 'jamaat.import',
            'export' => 'jamaat.export',
            'sample' => 'jamaat.import',
        ];
    }

    /**
     * Like class membership, this table has no company_id. Scoping through
     * the jamaat applies that model's company and role boundaries.
     */
    public function newQuery(): Builder
    {
        return JamaatMember::query()
            ->with($this->eagerLoads())
            ->whereHas('jamaat');
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::lookup('jamaat_id', Jamaat::class, 'jamaat_number')
                ->label(__('jamaats.jamaat_number'))
                ->required()
                ->sample('J-01')
                ->width(14),

            Column::lookup('employee_id', Employee::class, 'employee_code')
                ->label(__('jamaats.employee_code'))
                ->required()
                ->sample('EMP-0001')
                ->width(16),

            Column::related('employee_name', 'employee', 'employee_name')
                ->label(__('jamaats.employee_name'))
                ->width(24),

            Column::boolean('is_active')
                ->label(__('jamaats.status'))
                ->sample(__('data_transfer.yes')),

            Column::date('joined_at')
                ->label(__('jamaats.joined_at'))
                ->required()
                ->sample(now()->format('Y-m-d')),

            Column::date('left_at')
                ->label(__('jamaats.remove_member'))
                ->sample(''),
        ];
    }

    /** @return array<int, array<int, string>> */
    public function uniqueGroups(): array
    {
        return [['jamaat_id', 'employee_id']];
    }

    /** @return array<int, string> */
    protected function extraEagerLoads(): array
    {
        return ['employee'];
    }

    /** @return array<string, string> */
    protected function filters(): array
    {
        return ['jamaat_id' => 'jamaat_id'];
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return ['joined_at' => 'desc'];
    }

    /**
     * Membership is managed from the jamaat page, where removing a member also closes their dates.
     */
    public function supportsBulkActions(): bool
    {
        return false;
    }
}
