<?php

namespace App\Services;

use App\Contracts\Repositories\JamaatTaleemRepositoryInterface;
use App\Models\Jamaat;
use App\Models\JamaatTaleem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JamaatTaleemService extends BaseService
{
    private readonly JamaatTaleemRepositoryInterface $taleemRepository;

    public function __construct(JamaatTaleemRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->taleemRepository = $repository;
    }

    public function search(?string $search, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->taleemRepository->search($search, $filters, $perPage);
    }

    /**
     * Get the Taleem record for a jamaat on a specific date, if any.
     */
    public function getForJamaatDate(int $jamaatId, string $date): ?JamaatTaleem
    {
        return $this->taleemRepository->getForJamaatDate($jamaatId, $date);
    }

    /**
     * Record whether Taleem was held for a jamaat on a date.
     *
     * Unlike a teacher-absence record, every save writes exactly one row here
     * — held or not held are both a real, reportable outcome, not "record only
     * exists when something went wrong". Must be called from inside an
     * already-open transaction — see SalahAttendanceService::saveAllPrayersAttendance(),
     * which locks the Jamaat row this depends on before calling here.
     *
     * Deliberately not updateOrCreate(): its search half runs as a raw
     * where($attributes) that never passes attendance_date through the
     * model's date cast, while its create half does — on SQLite that stores
     * a bare "2026-08-18" search against an already-persisted
     * "2026-08-18 00:00:00", the two never match, and the second save hits
     * the unique constraint trying to insert a duplicate. whereDate() sidesteps
     * the mismatch entirely, the same reason it is used everywhere else in
     * this codebase that keys a lookup by a date-cast column.
     */
    public function saveForJamaatDate(
        Jamaat $jamaat,
        string $date,
        int $companyId,
        bool $held,
        ?int $reasonId,
        ?string $remarks,
        User $actor,
    ): JamaatTaleem {
        $attributes = [
            'leader_id' => $jamaat->leader_id,
            'held' => $held,
            'attendance_reason_id' => $held ? null : $reasonId,
            'remarks' => $remarks,
            'created_by' => $actor->id,
        ];

        $existing = JamaatTaleem::query()
            ->where('company_id', $companyId)
            ->where('jamaat_id', $jamaat->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if ($existing !== null) {
            $existing->update($attributes);

            return $existing;
        }

        return JamaatTaleem::create(array_merge($attributes, [
            'company_id' => $companyId,
            'jamaat_id' => $jamaat->id,
            'attendance_date' => $date,
        ]));
    }
}
