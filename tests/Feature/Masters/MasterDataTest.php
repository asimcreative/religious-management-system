<?php

namespace Tests\Feature\Masters;

use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Language;
use App\Models\QuranDepartment;
use App\Models\User;
use Tests\TestCase;

/**
 * Master Data — CRUD for all 7 master entities with company isolation.
 *
 * Branches / Departments / Designations / Languages / AttendanceReasons /
 * QuranDepartments / QuranStatuses
 */
class MasterDataTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────

    private function branchAdmin(): User
    {
        return $this->createUserWithCompany(['branch.manage']);
    }

    private function deptAdmin(): User
    {
        return $this->createUserWithCompany(['department.manage']);
    }

    private function desigAdmin(): User
    {
        return $this->createUserWithCompany(['designation.manage']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // BRANCHES
    // ═══════════════════════════════════════════════════════════════════════

    public function test_branch_index_requires_auth(): void
    {
        $this->get(route('masters.branches.index'))->assertRedirect(route('login'));
    }

    public function test_branch_index_requires_permission(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user)->get(route('masters.branches.index'))->assertForbidden();
    }

    public function test_branch_index_returns_ok(): void
    {
        $user = $this->branchAdmin();
        $this->actingAs($user)->get(route('masters.branches.index'))->assertOk();
    }

    public function test_store_branch_creates_record(): void
    {
        $user = $this->branchAdmin();

        $this->actingAs($user)
            ->post(route('masters.branches.store'), [
                'branch_name' => 'North Branch',
                'status' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'branch_name' => 'North Branch',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_store_branch_requires_branch_name(): void
    {
        $user = $this->branchAdmin();

        $this->actingAs($user)
            ->post(route('masters.branches.store'), ['status' => 1])
            ->assertSessionHasErrors('branch_name');
    }

    public function test_branch_company_isolation(): void
    {
        $userA = $this->branchAdmin();
        Branch::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        Branch::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, Branch::count());
    }

    public function test_update_branch(): void
    {
        $user = $this->branchAdmin();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->put(route('masters.branches.update', $branch), [
                'branch_name' => 'Updated Branch',
                'status' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'branch_name' => 'Updated Branch']);
    }

    public function test_delete_soft_deletes_branch(): void
    {
        $user = $this->branchAdmin();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->delete(route('masters.branches.destroy', $branch))
            ->assertRedirect();

        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
    }

    public function test_restore_branch(): void
    {
        $user = $this->branchAdmin();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $branch->delete();

        $this->actingAs($user)
            ->post(route('masters.branches.restore', $branch->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted('branches', ['id' => $branch->id]);
    }

    public function test_cannot_access_other_companys_branch(): void
    {
        $userA = $this->branchAdmin();
        $companyB = Company::factory()->create();
        $branchB = Branch::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->get(route('masters.branches.edit', $branchB))
            ->assertNotFound();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DEPARTMENTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_department_store_creates_record(): void
    {
        $user = $this->deptAdmin();

        $this->actingAs($user)
            ->post(route('masters.departments.store'), [
                'department_name' => 'Administration',
                'status' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', [
            'department_name' => 'Administration',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_department_company_isolation(): void
    {
        $userA = $this->deptAdmin();
        Department::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        Department::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, Department::count());
    }

    public function test_soft_delete_and_restore_department(): void
    {
        $user = $this->deptAdmin();
        $dept = Department::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)->delete(route('masters.departments.destroy', $dept));
        $this->assertSoftDeleted('departments', ['id' => $dept->id]);

        $this->actingAs($user)->post(route('masters.departments.restore', $dept->id));
        $this->assertNotSoftDeleted('departments', ['id' => $dept->id]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DESIGNATIONS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_designation_store_creates_record(): void
    {
        $user = $this->desigAdmin();

        $this->actingAs($user)
            ->post(route('masters.designations.store'), [
                'designation_name' => 'Manager',
                'status' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('designations', [
            'designation_name' => 'Manager',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_designation_company_isolation(): void
    {
        $userA = $this->desigAdmin();
        Designation::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        Designation::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, Designation::count());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ATTENDANCE REASONS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_attendance_reason_store_creates_record(): void
    {
        $user = $this->createUserWithCompany(['attendance_reason.manage']);

        $this->actingAs($user)
            ->post(route('masters.attendance-reasons.store'), [
                'reason_name' => 'Sick Leave',
                'reason_name_ur' => 'بیمار',
                'status' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_reasons', [
            'reason_name' => 'Sick Leave',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_attendance_reason_company_isolation(): void
    {
        $userA = $this->createUserWithCompany(['attendance_reason.manage']);
        AttendanceReason::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        AttendanceReason::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, AttendanceReason::count());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // QURAN DEPARTMENTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_quran_department_store_creates_record(): void
    {
        $user = $this->createUserWithCompany(['quran_department.manage']);

        $this->actingAs($user)
            ->post(route('masters.quran-departments.store'), [
                'department_name' => 'Hifz',
                'display_order' => 1,
                'status' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quran_departments', [
            'department_name' => 'Hifz',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_quran_department_company_isolation(): void
    {
        $userA = $this->createUserWithCompany(['quran_department.manage']);
        QuranDepartment::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        QuranDepartment::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, QuranDepartment::count());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LANGUAGES
    // ═══════════════════════════════════════════════════════════════════════

    public function test_language_store_creates_record(): void
    {
        $user = $this->createUserWithCompany(['language.manage']);

        $this->actingAs($user)
            ->post(route('masters.languages.store'), [
                'language_name' => 'Arabic',
                'native_name' => 'Arabic',
                'locale' => 'ar',
                'direction' => 'rtl',
                'status' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('languages', [
            'locale' => 'ar',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_language_company_isolation(): void
    {
        $userA = $this->createUserWithCompany(['language.manage']);
        Language::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        Language::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, Language::count());
    }
}
