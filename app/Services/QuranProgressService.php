<?php

namespace App\Services;

use App\Models\QuranProgress;
use App\Models\QuranProgressHistory;
use App\Repositories\QuranProgressRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class QuranProgressService extends BaseService
{
    private readonly QuranProgressRepository $progressRepository;

    public function __construct(QuranProgressRepository $repository)
    {
        parent::__construct($repository);
        $this->progressRepository = $repository;
    }

    public function search(?string $search, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->progressRepository->search($search, $filters, $perPage);
    }

    public function findWithRelations(int $id): QuranProgress
    {
        return $this->progressRepository->findWithRelations($id);
    }

    public function findByEmployee(int $employeeId): ?QuranProgress
    {
        return $this->progressRepository->findByEmployee($employeeId);
    }

    /**
     * Create or update progress for an employee.
     * Every update creates an immutable history record.
     */
    public function saveProgress(array $data): QuranProgress
    {
        return DB::transaction(function () use ($data) {
            $existing = $this->findByEmployee((int) $data['employee_id']);

            if ($existing) {
                // Update existing progress
                $existing->update($data);
                $progress = $existing->fresh();
            } else {
                // Create new progress
                /** @var QuranProgress $progress */
                $progress = $this->progressRepository->create($data);
            }

            /** @var QuranProgress $progress */

            // Create immutable history record
            QuranProgressHistory::create([
                'company_id' => $progress->company_id,
                'progress_id' => $progress->id,
                'employee_id' => $progress->employee_id,
                'teacher_id' => $progress->teacher_id,
                'quran_department_id' => $progress->quran_department_id,
                'quran_status_id' => $progress->quran_status_id,
                'lesson' => $progress->current_lesson,
                'surah' => $progress->current_surah,
                'sipara' => $progress->current_sipara,
                'page' => $progress->current_page,
                'percentage' => $progress->completion_percentage,
                'remarks' => $progress->remarks,
            ]);

            return $progress;
        });
    }
}
