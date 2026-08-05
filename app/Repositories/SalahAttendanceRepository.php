<?php

namespace App\Repositories;

use App\Contracts\Repositories\SalahAttendanceRepositoryInterface;
use App\Models\SalahAttendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as ManualPaginator;

class SalahAttendanceRepository extends BaseRepository implements SalahAttendanceRepositoryInterface
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
            ->whereDate('attendance_date', $date)
            ->where('prayer_id', $prayerId)
            ->get();
    }

    /**
     * History index: one paginated row per (date, jamaat, employee), prayers keyed by prayer_id.
     *
     * @param  array<string, mixed>  $filters
     */
    public function searchGrouped(?string $search, array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $records = $this->model->newQuery()
            ->with(['prayer', 'jamaat', 'employee', 'attendanceReason'])
            ->when($filters['jamaat_id'] ?? null, fn (Builder $q, $v) => $q->where('jamaat_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v))
            ->when($search, function (Builder $q) use ($search) {
                $q->whereHas('employee', fn (Builder $eq) => $eq->where('employee_name', 'like', "%{$search}%"));
            })
            ->latest('attendance_date')
            ->orderBy('employee_id')
            ->get();

        $grouped = $records
            ->groupBy(fn ($r) => $r->attendance_date->format('Y-m-d').'|'.$r->jamaat_id.'|'.$r->employee_id)
            ->map(fn ($rows) => [
                'date'     => $rows->first()->attendance_date,
                'jamaat'   => $rows->first()->jamaat,
                'employee' => $rows->first()->employee,
                'prayers'  => $rows->keyBy('prayer_id'),
            ])
            ->values();

        $page  = (int) request()->get('page', 1);
        $total = $grouped->count();
        $items = $grouped->forPage($page, $perPage);

        return new ManualPaginator($items, $total, $perPage, $page, [
            'path'  => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * Get all attendance records for a Jamaat on a specific date (all prayers).
     */
    public function getForJamaatDate(int $jamaatId, string $date): Collection
    {
        /** @var Collection */
        return $this->model->newQuery()
            ->where('jamaat_id', $jamaatId)
            ->whereDate('attendance_date', $date)
            ->get();
    }

    /**
     * Check if attendance already exists for a Jamaat on a date and prayer.
     */
    public function existsForJamaatDatePrayer(int $jamaatId, string $date, int $prayerId): bool
    {
        return $this->model->newQuery()
            ->where('jamaat_id', $jamaatId)
            ->whereDate('attendance_date', $date)
            ->where('prayer_id', $prayerId)
            ->exists();
    }
}
