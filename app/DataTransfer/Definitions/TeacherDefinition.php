<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Teacher;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeacherDefinition extends AbstractResourceDefinition
{
    /** Separator used for the multi-value branch column. */
    private const BRANCH_SEPARATOR = ',';

    public function key(): string
    {
        return 'teachers';
    }

    public function modelClass(): string
    {
        return Teacher::class;
    }

    public function label(): string
    {
        return __('teachers.teachers');
    }

    public function singularLabel(): string
    {
        return __('teachers.teacher_name');
    }

    public function icon(): string
    {
        return 'bi-mortarboard';
    }

    public function indexRoute(): string
    {
        return 'teachers.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'teacher.view',
            'import' => 'teacher.import',
            'export' => 'teacher.export',
            'sample' => 'teacher.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::string('teacher_code')
                ->label(__('teachers.teacher_code'))
                ->required()
                ->unique()
                ->rules(['max:50'])
                ->sample('TCH-001')
                ->width(16),

            // One teacher record per employee, matching StoreTeacherRequest.
            // Matched on employee_code rather than name because a code is
            // unique and a name is not.
            Column::lookup('employee_id', Employee::class, 'employee_code')
                ->label(__('teachers.teacher_name'))
                ->required()
                ->unique()
                ->sample('EMP-0001')
                ->width(18),

            Column::related('employee_name', 'employee', 'employee_name')
                ->label(__('employees.employee_name'))
                ->width(24),

            // A pivot cannot be expressed as one cell, so the branches arrive
            // as a separated list and are attached by afterWrite() once the
            // teacher has an id.
            Column::string('branches')
                ->label(__('teachers.branches'))
                ->required()
                ->help(__('teachers.assign_branches_hint'))
                ->exportUsing(fn (Teacher $teacher): string => $teacher->branches
                    ->pluck('branch_name')
                    ->implode(self::BRANCH_SEPARATOR.' '))
                ->sample('Main Branch, North Branch')
                ->width(30),

            Column::enum('status', Status::class)
                ->label(__('teachers.status'))
                ->required()
                ->sample('Active'),

            Column::text('notes')
                ->label(__('teachers.notes'))
                ->rules(['max:5000'])
                ->sample('')
                ->width(30),
        ];
    }

    /** @return array<int, string> */
    protected function extraEagerLoads(): array
    {
        return ['branches'];
    }

    /**
     * Reject branch names this company does not have, at preview time, so the
     * user sees the problem with its row number instead of a failed import.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function validateRow(array $attributes, array $context): array
    {
        $names = $this->branchNames($attributes);

        if ($names === []) {
            return [__('data_transfer.errors.required', ['column' => __('teachers.branches')])];
        }

        $known = array_map(
            static fn (string $name): string => mb_strtolower($name),
            array_keys($this->resolveBranchIds($names)),
        );

        // Reported with the spelling the user typed, not a normalised one:
        // an error that quotes something they did not write is harder to act on.
        $missing = array_filter(
            $names,
            static fn (string $name): bool => ! in_array(mb_strtolower($name), $known, true),
        );

        return array_map(
            static fn (string $name): string => __('data_transfer.errors.lookup_missing', [
                'column' => __('teachers.branches'),
                'value' => $name,
            ]),
            array_values($missing),
        );
    }

    /**
     * The branch list is not a column on teachers, so it is removed before
     * the model is filled and re-read by afterWrite().
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function prepareForWrite(array $attributes, array $context): array
    {
        unset($attributes['branches']);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     */
    public function afterWrite(Model $record, array $attributes, array $context): void
    {
        $names = $this->branchNames($attributes);

        if (! $record instanceof Teacher || $names === []) {
            return;
        }

        // Resolved through the scoped Branch query, so a teacher can never be
        // attached to another company's branch.
        $record->branches()->sync(array_values($this->resolveBranchIds($names)));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<int, string>
     */
    private function branchNames(array $attributes): array
    {
        $raw = $attributes['branches'] ?? '';

        return array_values(array_filter(array_map(
            static fn (string $name): string => trim($name),
            explode(self::BRANCH_SEPARATOR, (string) $raw),
        )));
    }

    /**
     * @param  array<int, string>  $names
     * @return array<string, int> Branch name => id.
     */
    private function resolveBranchIds(array $names): array
    {
        return Branch::query()
            ->whereIn('branch_name', $names)
            ->pluck('id', 'branch_name')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /** @return array<int, string> */
    protected function searchColumns(): array
    {
        return ['teacher_code'];
    }

    /**
     * A teacher's readable name lives on the employee behind them.
     *
     * @return array<string, array<int, string>>
     */
    protected function searchRelations(): array
    {
        return ['employee' => ['employee_name', 'employee_code']];
    }

    /**
     * Branch is a pivot, not a column, so it filters through the relation.
     * The Branch model's own scopes apply inside whereHas, which keeps the
     * filter tenant-safe without repeating the boundary here.
     *
     * @return array<string, string|Closure>
     */
    protected function filters(): array
    {
        return [
            'status' => 'status',
            'branch_id' => fn (Builder $query, $value) => $query->whereHas(
                'branches',
                fn (Builder $branches) => $branches->whereKey($value),
            ),
        ];
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return ['created_at' => 'desc'];
    }
}
