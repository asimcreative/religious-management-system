<?php

namespace App\Services;

use App\Contracts\Repositories\QuranProgressRepositoryInterface;
use App\Models\QuranProgress;
use App\Models\QuranProgressHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class QuranProgressService extends BaseService
{
    private readonly QuranProgressRepositoryInterface $progressRepository;

    public function __construct(QuranProgressRepositoryInterface $repository)
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

    /** @param array<string, mixed> $data */
    public function createProgress(array $data): QuranProgress
    {
        return DB::transaction(function () use ($data) {
            /** @var QuranProgress $progress */
            $progress = $this->progressRepository->create($data);

            $this->recordHistory($progress);

            return $progress;
        });
    }

    /** @param array<string, mixed> $data */
    public function updateProgress(QuranProgress $quranProgress, array $data): QuranProgress
    {
        return DB::transaction(function () use ($quranProgress, $data) {
            /** @var QuranProgress $progress */
            $progress = QuranProgress::query()
                ->lockForUpdate()
                ->findOrFail($quranProgress->id);

            $progress->update($data);
            $progress->refresh();

            $this->recordHistory($progress);

            return $progress;
        });
    }

    private function recordHistory(QuranProgress $progress): void
    {
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
    }
}
