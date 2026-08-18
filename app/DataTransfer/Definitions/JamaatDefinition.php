<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class JamaatDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'jamaats';
    }

    public function modelClass(): string
    {
        return Jamaat::class;
    }

    public function label(): string
    {
        return __('jamaats.jamaats');
    }

    public function singularLabel(): string
    {
        return __('jamaats.jamaat_name');
    }

    public function icon(): string
    {
        return 'bi-people-fill';
    }

    public function indexRoute(): string
    {
        return 'jamaats.index';
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

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::string('jamaat_number')
                ->label(__('jamaats.jamaat_number'))
                ->required()
                ->unique()
                ->rules(['max:50'])
                ->sample('J-01')
                ->width(14),

            Column::string('jamaat_name')
                ->label(__('jamaats.jamaat_name'))
                ->required()
                ->rules(['max:255'])
                ->sample('Jamaat Al-Fajr')
                ->width(24),

            Column::lookup('branch_id', Branch::class, 'branch_name')
                ->label(__('jamaats.branch'))
                ->required()
                ->sample('Main Branch')
                ->width(20),

            Column::lookup('leader_id', Employee::class, 'employee_code')
                ->label(__('jamaats.leader'))
                ->required()
                ->relation('leader')
                ->sample('EMP-0001')
                ->width(16),

            Column::lookup('vice_leader_id', Employee::class, 'employee_code')
                ->label(__('jamaats.vice_leader'))
                ->relation('viceLeader')
                ->sample('EMP-0002')
                ->width(16),

            Column::enum('status', Status::class)
                ->label(__('jamaats.status'))
                ->required()
                ->sample('Active'),
        ];
    }

    /**
     * The same person cannot lead and deputise for one jamaat — the same rule
     * StoreJamaatRequest applies with "different:leader_id". Nor can either
     * seat go to someone already an active member, leader or vice leader of
     * a *different* jamaat — the same rule StoreJamaatRequest/UpdateJamaatRequest
     * apply via Jamaat::leadershipConflictFor().
     *
     * Excludes by this row's own jamaat_number rather than an id: an import
     * row has no id for a jamaat that doesn't exist yet (create), and even
     * for an update the row-validation pass here runs before the id of the
     * matched existing record is threaded through — the natural key is the
     * only "which jamaat is this row itself" the row can name.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function validateRow(array $attributes, array $context): array
    {
        $leader = $attributes['leader_id'] ?? null;
        $viceLeader = $attributes['vice_leader_id'] ?? null;

        if ($leader !== null && $leader === $viceLeader) {
            return [__('data_transfer.errors.invalid', ['column' => __('jamaats.vice_leader')])];
        }

        $jamaatNumber = $attributes['jamaat_number'] ?? null;
        $errors = [];

        foreach (['leader_id' => $leader, 'vice_leader_id' => $viceLeader] as $column => $employeeId) {
            if ($employeeId === null || ! is_numeric($employeeId)) {
                continue;
            }

            $conflict = Jamaat::leadershipConflictFor((int) $employeeId, exceptJamaatNumber: $jamaatNumber);

            if ($conflict !== null) {
                $errors[] = __("jamaats.leadership_conflict_{$conflict['role']}", ['jamaat' => $conflict['jamaat']]);
            }
        }

        return $errors;
    }

    /** @return array<int, string> */
    protected function searchColumns(): array
    {
        return ['jamaat_name', 'jamaat_number'];
    }

    /** @return array<string, string> */
    protected function filters(): array
    {
        return [
            'branch_id' => 'branch_id',
            'status' => 'status',
        ];
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return ['created_at' => 'desc'];
    }
}
