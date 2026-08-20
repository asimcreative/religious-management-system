<?php

namespace Tests\Feature\Quran;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\QuranClass;
use App\Models\QuranClassMember;
use App\Models\QuranDepartment;
use App\Models\QuranProgress;
use App\Models\QuranProgressHistory;
use App\Models\QuranStatus;
use App\Models\Teacher;
use App\Models\User;
use Tests\TestCase;

/**
 * Quran Class Admission Form — the optional follow-up step after adding a
 * member to a Quran Class. See docs/features/quran-class-admission/README.md.
 */
class QuranClassAdmissionTest extends TestCase
{
    private function admin(array $extra = []): User
    {
        return $this->createUserWithCompany([
            'quran.class.view', 'quran.class.create', 'quran.class.update',
            'quran.progress.create', 'quran.progress.update',
            ...$extra,
        ]);
    }

    /**
     * @return array{class: QuranClass, teacher: Teacher, employee: Employee}
     */
    private function buildClass(User $user): array
    {
        $companyId = $user->company_id;

        $branch = Branch::factory()->create(['company_id' => $companyId]);
        $teacher = Teacher::factory()->create([
            'company_id' => $companyId,
            'employee_id' => Employee::factory()->create(['company_id' => $companyId])->id,
        ]);

        $class = QuranClass::factory()->create([
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
            'max_strength' => 5,
        ]);

        $employee = Employee::factory()->create(['company_id' => $companyId]);

        return compact('class', 'teacher', 'employee');
    }

    private function activeStatus(User $user): QuranStatus
    {
        return QuranStatus::factory()->create([
            'company_id' => $user->company_id,
            'status_name' => 'Active',
            'display_order' => 1,
        ]);
    }

    private function department(User $user): QuranDepartment
    {
        return QuranDepartment::factory()->create(['company_id' => $user->company_id]);
    }

    /** @return array<string, mixed> */
    private function admissionPayload(QuranDepartment $department): array
    {
        return [
            'quran_department_id' => $department->id,
            'current_reading_level' => 4,
            'previously_completed_quran' => false,
            'admission_date' => now()->toDateString(),
            'classes_per_week' => 5,
            'current_lesson' => 'Lesson 5',
            'current_surah' => 'Al-Baqarah',
            'current_sipara' => 2,
            'remarks' => 'Settling in well.',
        ];
    }

    private function member(QuranClass $class, Employee $employee): QuranClassMember
    {
        return QuranClassMember::query()
            ->where('class_id', $class->id)
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    // ── Add member → admission form ─────────────────────────────────────────

    public function test_adding_a_member_redirects_to_the_admission_form(): void
    {
        $user = $this->admin();
        ['class' => $class, 'employee' => $employee] = $this->buildClass($user);

        $this->actingAs($user)
            ->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id])
            ->assertRedirect(route('quran-classes.members.admission.create', [$class, $employee]));
    }

    public function test_admission_form_shows_the_employee_and_class_already_on_record(): void
    {
        $user = $this->admin();
        ['class' => $class, 'employee' => $employee] = $this->buildClass($user);

        $this->actingAs($user)->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id]);

        $this->actingAs($user)
            ->get(route('quran-classes.members.admission.create', [$class, $employee]))
            ->assertOk()
            ->assertSee($employee->employee_name)
            ->assertSee($class->class_name);
    }

    // ── Submit: brand new progress ────────────────────────────────────────

    public function test_submitting_admission_creates_admission_record_and_new_progress_with_history(): void
    {
        $user = $this->admin();
        ['class' => $class, 'employee' => $employee] = $this->buildClass($user);
        $department = $this->department($user);
        $status = $this->activeStatus($user);

        $this->actingAs($user)->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id]);
        $member = $this->member($class, $employee);

        $this->actingAs($user)
            ->post(route('quran-classes.members.admission.store', [$class, $employee]), $this->admissionPayload($department))
            ->assertRedirect(route('quran-classes.members.index', $class));

        $this->assertDatabaseHas('quran_class_admissions', [
            'company_id' => $user->company_id,
            'quran_class_member_id' => $member->id,
            'current_reading_level' => 4,
            'previously_completed_quran' => false,
            'classes_per_week' => 5,
        ]);

        $progress = QuranProgress::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame($class->teacher_id, $progress->teacher_id);
        $this->assertSame($department->id, $progress->quran_department_id);
        $this->assertSame($status->id, $progress->quran_status_id);
        $this->assertSame('0.00', (string) $progress->completion_percentage);
        $this->assertSame('Lesson 5', $progress->current_lesson);

        $this->assertSame(1, QuranProgressHistory::query()->where('progress_id', $progress->id)->count());
    }

    public function test_submitting_admission_writes_exactly_one_audit_log_row(): void
    {
        // quran_class_admissions is a standalone table (own id, own audit
        // columns), so its trail comes from the same BusinessAuditObserver
        // every other master/business table uses — not a manual service call.
        // This guards against ever double-logging it both ways.
        $user = $this->admin();
        ['class' => $class, 'employee' => $employee] = $this->buildClass($user);
        $department = $this->department($user);
        $this->activeStatus($user);

        $this->actingAs($user)->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id]);
        $member = $this->member($class, $employee);

        $this->actingAs($user)->post(route('quran-classes.members.admission.store', [$class, $employee]), $this->admissionPayload($department));

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'quran_class_admissions')
            ->where('record_id', $member->admission?->id)
            ->count());
    }

    // ── Submit: existing progress is updated, never reset ────────────────

    public function test_submitting_admission_for_employee_with_existing_progress_preserves_status_and_percentage(): void
    {
        $user = $this->admin();
        ['class' => $class, 'employee' => $employee] = $this->buildClass($user);
        $department = $this->department($user);
        $this->activeStatus($user);
        $completedStatus = QuranStatus::factory()->create([
            'company_id' => $user->company_id,
            'status_name' => 'Completed',
        ]);

        $existing = QuranProgress::factory()->create([
            'company_id' => $user->company_id,
            'employee_id' => $employee->id,
            'quran_status_id' => $completedStatus->id,
            'completion_percentage' => 55.00,
        ]);

        $this->actingAs($user)->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id]);

        $this->actingAs($user)
            ->post(route('quran-classes.members.admission.store', [$class, $employee]), $this->admissionPayload($department))
            ->assertRedirect(route('quran-classes.members.index', $class));

        $progress = $existing->refresh();
        $this->assertSame($completedStatus->id, $progress->quran_status_id);
        $this->assertSame('55.00', (string) $progress->completion_percentage);
        $this->assertSame($department->id, $progress->quran_department_id);
        $this->assertSame('Lesson 5', $progress->current_lesson);

        $this->assertSame(1, QuranProgress::query()->where('employee_id', $employee->id)->count());
        $this->assertSame(1, QuranProgressHistory::query()->where('progress_id', $progress->id)->count());
    }

    // ── Skip ───────────────────────────────────────────────────────────────

    public function test_skipping_leaves_member_added_with_no_admission_row_and_pending_badge(): void
    {
        $user = $this->admin();
        ['class' => $class, 'employee' => $employee] = $this->buildClass($user);

        $this->actingAs($user)->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id]);

        $member = $this->member($class, $employee);
        $this->assertDatabaseMissing('quran_class_admissions', ['quran_class_member_id' => $member->id]);

        $this->actingAs($user)
            ->get(route('quran-classes.members.index', $class))
            ->assertOk()
            ->assertSee(__('quran_classes.admission_pending'))
            ->assertSee(__('quran_classes.fill_admission_form'));
    }

    public function test_admission_badge_flips_to_complete_after_filling_it_in_later(): void
    {
        $user = $this->admin();
        ['class' => $class, 'employee' => $employee] = $this->buildClass($user);
        $department = $this->department($user);
        $this->activeStatus($user);

        $this->actingAs($user)->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id]);
        $this->actingAs($user)->post(route('quran-classes.members.admission.store', [$class, $employee]), $this->admissionPayload($department));

        $this->actingAs($user)
            ->get(route('quran-classes.members.index', $class))
            ->assertOk()
            ->assertSee(__('quran_classes.admission_complete'))
            ->assertDontSee(__('quran_classes.admission_pending'));
    }

    // ── Authorization ────────────────────────────────────────────────────

    public function test_store_is_forbidden_without_quran_progress_permission(): void
    {
        $user = $this->createUserWithCompany(['quran.class.view', 'quran.class.create', 'quran.class.update']);
        ['class' => $class, 'employee' => $employee] = $this->buildClass($user);
        $department = $this->department($user);

        $this->actingAs($user)->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id]);

        $this->actingAs($user)
            ->post(route('quran-classes.members.admission.store', [$class, $employee]), $this->admissionPayload($department))
            ->assertForbidden();

        $this->assertDatabaseCount('quran_class_admissions', 0);
    }

    public function test_store_permission_check_falls_back_to_update_when_progress_already_exists(): void
    {
        $user = $this->createUserWithCompany([
            'quran.class.view', 'quran.class.create', 'quran.class.update', 'quran.progress.update',
        ]);
        ['class' => $class, 'employee' => $employee] = $this->buildClass($user);
        $department = $this->department($user);

        QuranProgress::factory()->create([
            'company_id' => $user->company_id,
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($user)->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id]);

        $this->actingAs($user)
            ->post(route('quran-classes.members.admission.store', [$class, $employee]), $this->admissionPayload($department))
            ->assertRedirect(route('quran-classes.members.index', $class));

        $this->assertDatabaseCount('quran_class_admissions', 1);
    }

    // ── Company isolation ────────────────────────────────────────────────

    public function test_cannot_access_another_companys_class_admission_form(): void
    {
        $user = $this->admin();
        $otherUser = $this->admin();
        ['class' => $otherClass, 'employee' => $otherEmployee] = $this->buildClass($otherUser);

        $this->actingAs($otherUser)->post(route('quran-classes.members.store', $otherClass), ['employee_id' => $otherEmployee->id]);

        $this->actingAs($user)
            ->get(route('quran-classes.members.admission.create', [$otherClass, $otherEmployee]))
            ->assertNotFound();
    }
}
