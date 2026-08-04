<?php

namespace App\Repositories;

use App\Models\SalahAttendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SalahAttendanceRepository extends BaseRepository
{
    public function __construct(SalahAttendance $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['prayer', 'jamaat', 'employee', 'attendanceReason'])
            ->when($filters['jamaat_id'] ?? null, fn (Builder $q, $v) => $q->where('jamaat_id', $v))
            ->when($filters['prayer_id'] ?? null, fn (Builder $q, $v) => $q->where('prayer_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v))
            ->when($search, function (Builder $query) use ($search) {
                $query->whereHas('employee', function (Builder $q) use ($search) {
                    $q->where('employee_name', 'like', "%{$search}%");
                });
            })
            ->latest('attendance_date')
            ->paginate($perPage);
    }

    /**
     * Get attendance for a Jamaat on a specific date and prayer.
     */
    public function getForJamaatDatePrayer(int $jamaatId, string $date, int $prayerId): Collection
    {
        /** @var Collection */
        return $this->model->newQuery()
            ->where('jamaat_id', $jamaatId)
            ->where('attendance_date', $date)
            ->where('prayer_id', $prayerId)
            ->get();
    }

    /**
     * Check if attendance already exists for a Jamaat on a date and prayer.
     */
    public function existsForJamaatDatePrayer(int $jamaatId, string $date, int $prayerId): bool
    {
        return $this->model->newQuery()
            ->where('jamaat_id', $jamaatId)
            ->where('attendance_date', $date)
            ->where('prayer_id', $prayerId)
            ->exists();
    }
}
