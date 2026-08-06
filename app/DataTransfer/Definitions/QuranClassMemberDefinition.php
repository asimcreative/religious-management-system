<?php

namespace App\DataTransfer\Definitions;

use App\Models\Employee;
use App\Models\QuranClass;
use App\Models\QuranClassMember;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use Illuminate\Database\Eloquent\Builder;

class QuranClassMemberDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'quran-class-members';
    }

    public function modelClass(): string
    {
        return QuranClassMember::class;
    }

    public function label(): string
    {
        return __('quran_classes.active_members');
    }

    public function singularLabel(): string
    {
        return __('quran_classes.add_member');
    }

    public function icon(): string
    {
        return 'bi-person-plus';
    }

    public function indexRoute(): ?string
    {
        // Membership lives on each class's own page rather than a list of its
        // own, so there is no single route to return to.
        return null;
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

    /**
     * quran_class_members carries no company_id of its own, so it cannot use
     * BelongsToCompany. Tenancy is inherited from the class instead: whereHas
     * runs QuranClass's own global scopes, which enforce both the company
     * boundary and the caller's role boundary.
     */
    public function newQuery(): Builder
    {
        return QuranClassMember::query()
            ->with($this->eagerLoads())
            ->whereHas('quranClass');
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::lookup('class_id', QuranClass::class, 'class_code')
                ->label(__('quran_classes.class_code'))
                ->required()
                ->relation('quranClass')
                ->sample('QC-001')
                ->width(14),

            Column::lookup('employee_id', Employee::class, 'employee_code')
                ->label(__('quran_classes.select_employee'))
                ->required()
                ->sample('EMP-0001')
                ->width(16),

            Column::related('employee_name', 'employee', 'employee_name')
                ->label(__('employees.employee_name'))
                ->width(24),

            Column::boolean('is_active')
                ->label(__('masters.status'))
                ->sample(__('data_transfer.yes')),

            Column::date('joined_at')
                ->label(__('quran_classes.joined_at'))
                ->required()
                ->sample(now()->format('Y-m-d')),

            Column::date('left_at')
                ->label(__('quran_classes.remove_member'))
                ->sample(''),
        ];
    }

    /**
     * A membership is identified by the class and the employee together —
     * the (class_id, employee_id) unique index.
     *
     * @return array<int, array<int, string>>
     */
    public function uniqueGroups(): array
    {
        return [['class_id', 'employee_id']];
    }

    /** @return array<int, string> */
    protected function extraEagerLoads(): array
    {
        return ['employee'];
    }

    /** @return array<string, string> */
    protected function filters(): array
    {
        return ['class_id' => 'class_id'];
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return ['joined_at' => 'desc'];
    }

    /**
     * Membership is managed from the class page, where removing a member also closes their dates.
     */
    public function supportsBulkActions(): bool
    {
        return false;
    }
}
