<?php

namespace App\Repositories;

use App\Contracts\Repositories\JamaatRepositoryInterface;
use App\Models\Jamaat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class JamaatRepository extends BaseRepository implements JamaatRepositoryInterface
{
    public function __construct(Jamaat $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['branch', 'leader', 'viceLeader'])
            ->withCount('activeMembers')
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('jamaat_name', 'like', "%{$search}%")
                        ->orWhere('jamaat_number', 'like', "%{$search}%")
                        ->orWhereHas('leader', function (Builder $eq) use ($search) {
                            $eq->where('employee_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $v) => $q->where('branch_id', $v))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn (Builder $q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate($perPage);
    }

    public function findWithRelations(int $id): Jamaat
    {
        /** @var Jamaat */
        return $this->model->newQuery()
            ->with(['branch', 'leader', 'viceLeader', 'activeMembers', 'creator', 'updater'])
            ->withCount('activeMembers')
            ->findOrFail($id);
    }

    public function hasAttendanceRecords(int $id): bool
    {
        /** @var Jamaat $model */
        $model = $this->model->newQuery()->findOrFail($id);

        return $model->salahAttendances()->exists()
            || $model->activeMembers()->exists();
    }

    public function restore(int $id): bool
    {
        $model = Jamaat::onlyTrashed()->findOrFail($id);

        return $model->restore();
    }
}
