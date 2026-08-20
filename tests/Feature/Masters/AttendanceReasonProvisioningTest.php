<?php

namespace Tests\Feature\Masters;

use App\Enums\AttendanceReasonType;
use App\Enums\Status;
use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\Prayer;
use App\Models\SalahAttendance;
use App\Models\User;
use App\Services\CompanyProvisioningService;
use Database\Seeders\AttendanceReasonSeeder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * A company must be able to record an absence from the day it is created.
 *
 * Presence is stored as attendance_reason_id = NULL and every other status is a
 * row in attendance_reasons, so a company with no reasons has attendance sheets
 * that offer "Present" and nothing else — recording perfect attendance for
 * everyone while looking like they work. That is what happened on production:
 * the table was empty for the only tenant and 627 attendance rows were written
 * with a NULL reason because no other value could be chosen.
 */
class AttendanceReasonProvisioningTest extends TestCase
{
    /** A Super Admin in the SYSTEM company, holding the company.* permissions. */
    private function platformAccount(): User
    {
        $user = $this->createSuperAdmin();

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);
        $role = Role::findByName('Super Admin', 'web');

        foreach (['company.view', 'company.create'] as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $user;
    }

    /** @return array<string, mixed> */
    private function companyPayload(array $overrides = []): array
    {
        return array_merge([
            'company_code' => 'NEWCO',
            'company_name' => 'New Organisation',
            'email' => 'admin@newco.test',
            'timezone' => 'Asia/Karachi',
            'status' => 1,
        ], $overrides);
    }

    private function reasonsFor(int $companyId): Collection
    {
        return AttendanceReason::withoutGlobalScope('company')
            ->withTrashed()
            ->forCompany($companyId)
            ->get();
    }

    // ── Provisioning at company creation ───────────────────────────────────

    public function test_creating_a_company_provisions_the_default_attendance_reasons_for_both_types(): void
    {
        $platform = $this->platformAccount();

        $this->actingAs($platform)
            ->post(route('companies.store'), $this->companyPayload())
            ->assertRedirect(route('companies.index'));

        $company = Company::where('company_code', 'NEWCO')->firstOrFail();
        $salahDefaults = config('master-data.attendance_reasons.salah');
        $quranDefaults = config('master-data.attendance_reasons.quran');

        $this->assertCount(count($salahDefaults) + count($quranDefaults), $this->reasonsFor($company->id));

        foreach ([AttendanceReasonType::Salah->value => $salahDefaults, AttendanceReasonType::Quran->value => $quranDefaults] as $type => $defaults) {
            foreach ($defaults as $default) {
                $this->assertDatabaseHas('attendance_reasons', [
                    'company_id' => $company->id,
                    'type' => $type,
                    'reason_name' => $default['reason_name'],
                    'counts_as_absent' => $default['counts_as_absent'],
                    'counts_as_leave' => $default['counts_as_leave'],
                    'status' => Status::Active->value,
                ]);
            }
        }
    }

    public function test_the_defaults_include_a_reason_that_counts_as_absent(): void
    {
        // Every report built on counts_as_absent reads zero without one, which
        // renders as flawless attendance rather than as missing configuration.
        foreach (['salah', 'quran'] as $type) {
            $this->assertContains(
                true,
                array_column(config('master-data.attendance_reasons.'.$type), 'counts_as_absent'),
            );
        }
    }

    public function test_the_defaults_do_not_include_present(): void
    {
        // Presence is the absence of a reason. A Present row would be a second
        // way to say the same thing and would split every attendance figure.
        foreach (['salah', 'quran'] as $type) {
            $this->assertNotContains(
                'Present',
                array_column(config('master-data.attendance_reasons.'.$type), 'reason_name'),
            );
        }
    }

    public function test_a_failure_while_provisioning_does_not_leave_a_half_built_company(): void
    {
        $platform = $this->platformAccount();

        // attendance_reasons.reason_name is NOT NULL, so this default cannot be written.
        config(['master-data.attendance_reasons' => [
            'salah' => [['reason_name' => null, 'color' => null, 'icon' => null]],
            'quran' => [['reason_name' => null, 'color' => null, 'icon' => null]],
        ]]);

        try {
            $this->actingAs($platform)->post(route('companies.store'), $this->companyPayload());
        } catch (\Throwable) {
            // The insert is meant to fail; what matters is what survives it.
        }

        $this->assertDatabaseMissing('companies', ['company_code' => 'NEWCO']);
    }

    // ── The platform account holds no business data ────────────────────────

    public function test_the_platform_company_is_never_provisioned(): void
    {
        $platform = Company::factory()->create(['company_code' => Company::PLATFORM_CODE]);

        $created = app(CompanyProvisioningService::class)->provision($platform);

        $this->assertSame(0, $created);
        $this->assertCount(0, $this->reasonsFor($platform->id));
    }

    // ── Re-running provisioning is safe ────────────────────────────────────

    private function defaultReasonCount(): int
    {
        return count(config('master-data.attendance_reasons.salah')) + count(config('master-data.attendance_reasons.quran'));
    }

    public function test_provisioning_twice_does_not_duplicate_the_defaults(): void
    {
        $company = Company::factory()->create();
        $service = app(CompanyProvisioningService::class);

        $service->provision($company);
        $second = $service->provision($company);

        $this->assertSame(0, $second);
        $this->assertCount($this->defaultReasonCount(), $this->reasonsFor($company->id));
    }

    /**
     * Salah and Quran are checked independently: a company that already holds
     * one type but not the other must still be handed the missing type's
     * defaults, not skipped entirely.
     */
    public function test_provisioning_leaves_an_already_configured_type_alone_but_fills_in_the_missing_one(): void
    {
        $company = Company::factory()->create();
        AttendanceReason::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'type' => AttendanceReasonType::Salah,
            'reason_name' => 'Ghair Haazir',
            'counts_as_absent' => true,
            'status' => Status::Active,
        ]);

        $created = app(CompanyProvisioningService::class)->provision($company);

        $this->assertSame(count(config('master-data.attendance_reasons.quran')), $created);
        $reasons = $this->reasonsFor($company->id);
        $this->assertSame(['Ghair Haazir'], $reasons->where('type', AttendanceReasonType::Salah)->pluck('reason_name')->all());
        $this->assertCount(count(config('master-data.attendance_reasons.quran')), $reasons->where('type', AttendanceReasonType::Quran));
    }

    public function test_provisioning_does_not_resurrect_a_deleted_default(): void
    {
        $company = Company::factory()->create();
        $service = app(CompanyProvisioningService::class);
        $service->provision($company);

        $this->reasonsFor($company->id)->each->delete();

        $service->provision($company);

        $this->assertCount(
            0,
            AttendanceReason::withoutGlobalScope('company')->forCompany($company->id)->get(),
            'A deletion is a decision; provisioning must not overrule it.',
        );
    }

    public function test_provisioning_does_not_reactivate_a_deactivated_default(): void
    {
        $company = Company::factory()->create();
        $service = app(CompanyProvisioningService::class);
        $service->provision($company);

        $this->reasonsFor($company->id)->each->update(['status' => Status::Inactive]);

        $service->provision($company);

        $this->assertCount(0, $this->reasonsFor($company->id)->where('status', Status::Active));
    }

    // ── The seeder that backfills existing installs ────────────────────────

    public function test_the_seeder_provisions_every_tenant_and_skips_the_platform_company(): void
    {
        $platform = Company::factory()->create(['company_code' => Company::PLATFORM_CODE]);
        $first = Company::factory()->create();
        $second = Company::factory()->create();

        $this->seed(AttendanceReasonSeeder::class);

        $expected = $this->defaultReasonCount();
        $this->assertCount($expected, $this->reasonsFor($first->id));
        $this->assertCount($expected, $this->reasonsFor($second->id));
        $this->assertCount(0, $this->reasonsFor($platform->id));
    }

    public function test_the_seeder_is_safe_to_run_twice(): void
    {
        $company = Company::factory()->create();

        $this->seed(AttendanceReasonSeeder::class);
        $this->seed(AttendanceReasonSeeder::class);

        $this->assertCount($this->defaultReasonCount(), $this->reasonsFor($company->id));
    }

    // ── What the person marking attendance sees ────────────────────────────

    public function test_the_salah_sheet_says_so_when_the_company_has_no_reasons(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.create', 'attendance_reason.manage']);

        $this->actingAs($user)
            ->get(route('salah-attendance.create'))
            ->assertOk()
            ->assertSee(__('masters.no_attendance_reasons_title'))
            ->assertSee(route('masters.salah-attendance-reasons.index'), false);
    }

    public function test_the_salah_sheet_points_a_user_without_the_permission_at_an_administrator(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.create']);

        $this->actingAs($user)
            ->get(route('salah-attendance.create'))
            ->assertOk()
            ->assertSee(__('masters.no_attendance_reasons_ask_admin'))
            ->assertDontSee(route('masters.salah-attendance-reasons.index'), false);
    }

    public function test_the_salah_sheet_stays_quiet_once_the_company_has_reasons(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.create']);
        AttendanceReason::factory()->salah()->create([
            'company_id' => $user->company_id,
            'status' => Status::Active,
        ]);

        $this->actingAs($user)
            ->get(route('salah-attendance.create'))
            ->assertOk()
            ->assertDontSee(__('masters.no_attendance_reasons_title'));
    }

    public function test_the_quran_sheet_says_so_when_the_company_has_no_reasons(): void
    {
        $user = $this->createUserWithCompany(['quran.attendance.create', 'attendance_reason.manage']);

        $this->actingAs($user)
            ->get(route('quran-attendance.create'))
            ->assertOk()
            ->assertSee(__('masters.no_attendance_reasons_title'))
            ->assertSee(route('masters.quran-attendance-reasons.index'), false);
    }

    /**
     * A company can be configured on one side and not the other — the two
     * lists are independent, so having Salah reasons must not silence the
     * Quran sheet's own banner (and vice versa, covered above by the
     * "no reasons" case starting from a blank company).
     */
    public function test_the_quran_sheet_still_speaks_up_when_only_salah_is_configured(): void
    {
        $user = $this->createUserWithCompany(['quran.attendance.create', 'salah.attendance.create']);
        AttendanceReason::factory()->salah()->create([
            'company_id' => $user->company_id,
            'status' => Status::Active,
        ]);

        $this->actingAs($user)
            ->get(route('quran-attendance.create'))
            ->assertOk()
            ->assertSee(__('masters.no_attendance_reasons_title'));
    }

    public function test_the_quran_sheet_stays_quiet_once_the_company_has_quran_reasons(): void
    {
        $user = $this->createUserWithCompany(['quran.attendance.create']);
        AttendanceReason::factory()->quran()->create([
            'company_id' => $user->company_id,
            'status' => Status::Active,
        ]);

        $this->actingAs($user)
            ->get(route('quran-attendance.create'))
            ->assertOk()
            ->assertDontSee(__('masters.no_attendance_reasons_title'));
    }

    // ── The journey the production bug made impossible ─────────────────────

    public function test_an_absence_can_be_recorded_once_a_company_is_provisioned(): void
    {
        $user = $this->createUserWithCompany([
            'salah.attendance.create', 'salah.attendance.view',
        ]);
        $company = Company::findOrFail($user->company_id);

        app(CompanyProvisioningService::class)->provision($company);

        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $leader = Employee::factory()->create(['company_id' => $company->id]);
        $jamaat = Jamaat::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'leader_id' => $leader->id,
        ]);
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $jamaat->members()->attach($employee->id, [
            'is_active' => true,
            'joined_at' => now('Asia/Karachi')->toDateString(),
        ]);

        $prayer = Prayer::factory()->create();
        $absent = AttendanceReason::withoutGlobalScope('company')
            ->forCompany($company->id)
            ->ofType(AttendanceReasonType::Salah)
            ->where('counts_as_absent', true)
            ->firstOrFail();
        $date = now('Asia/Karachi')->toDateString();

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), [
                'jamaat_id' => $jamaat->id,
                'date' => $date,
                'attendance' => [$employee->id => [$prayer->id => $absent->id]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('salah_attendance', [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'prayer_id' => $prayer->id,
            'attendance_reason_id' => $absent->id,
        ]);

        $record = SalahAttendance::withoutGlobalScope('company')
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $this->assertSame($date, $record->attendance_date->toDateString());
        $this->assertFalse($record->isPresent());
    }
}
