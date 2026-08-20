<?php

namespace App\Repositories;

use App\Contracts\Repositories\JamaatTaleemRepositoryInterface;
use App\Models\JamaatTaleem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class JamaatTaleemRepository extends BaseRepository implements JamaatTaleemRepositoryInterface
{
    public function __construct(JamaatTaleem $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['jamaat', 'leader', 'attendanceReason'])
            ->when($filters['jamaat_id'] ?? null, fn (Builder $q, $v) => $q->where('jamaat_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v))
            ->when($search, function (Builder $query) use ($search) {
                $query->whereHas('jamaat', function (Builder $q) use ($search) {
                    $q->where('jamaat_name', 'like', "%{$search}%")
                        ->orWhere('jamaat_number', 'like', "%{$search}%");
                });
            })
            ->latest('attendance_date')
            ->paginate($perPage);
    }

    /**
     * Get the Taleem record for a jamaat on a specific date, if any.
     */
    public function getForJamaatDate(int $jamaatId, string $date): ?JamaatTaleem
    {
        return $this->model->newQuery()
            ->with(['attendanceReason'])
            ->where('jamaat_id', $jamaatId)
            ->whereDate('attendance_date', $date)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, JamaatTaleem>
     */
    public function getForFilters(array $filters): Collection
    {
        return $this->model->newQuery()
            ->with(['attendanceReason'])
            ->when($filters['jamaat_id'] ?? null, fn (Builder $q, $v) => $q->where('jamaat_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v))
            ->get();
    }
}
