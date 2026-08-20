<?php

namespace App\Services;

use App\Contracts\Repositories\QuranClassAdmissionRepositoryInterface;
use App\Models\QuranClassAdmission;
use App\Models\QuranClassMember;
use App\Models\QuranStatus;
use Illuminate\Support\Facades\DB;

/**
 * Saves the optional "Admission Form" details for a Quran Class membership.
 *
 * Two tables, one submission: the admission-specific facts (reading level,
 * classes per week, previously-completed flag, admission date, remarks) are
 * new to `quran_class_admissions` — a standalone table (own id, own audit
 * columns), so its create/update audit trail is handled the same way as
 * every other master/business table: BusinessAuditObserver, registered in
 * AppServiceProvider, not a manual call here. The "Current Starting Point"
 * and "Quran Class Type" fields are not new data — they are the employee's
 * existing, ongoing QuranProgress record, created or updated through
 * QuranProgressService so its history trail keeps working exactly as it
 * does everywhere else in the app. Bypassing that service (writing
 * QuranProgress directly) would silently skip the history snapshot.
 */
class QuranClassAdmissionService
{
    public function __construct(
        private readonly QuranClassAdmissionRepositoryInterface $admissionRepository,
        private readonly QuranProgressService $progressService,
    ) {}

    public function findByMember(int $quranClassMemberId): ?QuranClassAdmission
    {
        return $this->admissionRepository->findByMember($quranClassMemberId);
    }

    /**
     * @param  array<string, mixed>  $data  Validated StoreQuranClassAdmissionRequest input.
     */
    public function store(QuranClassMember $member, array $data): QuranClassAdmission
    {
        return DB::transaction(function () use ($member, $data): QuranClassAdmission {
            $companyId = (int) $member->quranClass->company_id;

            $admission = $this->admissionRepository->upsertForMember($member->id, [
                'company_id' => $companyId,
                'current_reading_level' => $data['current_reading_level'],
                'previously_completed_quran' => $data['previously_completed_quran'],
                'admission_date' => $data['admission_date'],
                'classes_per_week' => $data['classes_per_week'],
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->syncQuranProgress($member, $data, $companyId);

            return $admission;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncQuranProgress(QuranClassMember $member, array $data, int $companyId): void
    {
        $employee = $member->employee;
        $teacherId = $member->quranClass->teacher_id;

        $progressFields = [
            'teacher_id' => $teacherId,
            'quran_department_id' => $data['quran_department_id'],
            'current_lesson' => $data['current_lesson'] ?? null,
            'current_surah' => $data['current_surah'] ?? null,
            'current_sipara' => $data['current_sipara'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ];

        $existing = $this->progressService->findByEmployee($employee->id);

        if ($existing !== null) {
            // Only the fields this admission actually concerns itself with —
            // completion_percentage and quran_status_id are left untouched so
            // a student's already-tracked progress is never reset just because
            // they were admitted to a (new) class.
            $this->progressService->updateProgress($existing, $progressFields);

            return;
        }

        $this->progressService->createProgress([
            ...$progressFields,
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'quran_status_id' => $this->defaultActiveStatusId($companyId),
            'completion_percentage' => 0,
        ]);
    }

    /**
     * The company's own "Active" QuranStatus, falling back to whichever
     * active status sorts first if it has been renamed — a brand new
     * admission always starts as actively studying, never "Completed" or
     * "Withdrawn".
     */
    private function defaultActiveStatusId(int $companyId): ?int
    {
        $status = QuranStatus::query()
            ->where('company_id', $companyId)
            ->active()
            ->where('status_name', 'Active')
            ->first()
            ?? QuranStatus::query()
                ->where('company_id', $companyId)
                ->active()
                ->orderBy('display_order')
                ->first();

        return $status?->id;
    }
}
