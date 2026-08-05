<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatMember;
use App\Models\Prayer;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranClassMember;
use App\Models\SalahAttendance;
use App\Models\Setting;
use App\Models\User;
use App\Services\AttendanceLockService;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceLockTest extends TestCase
{
    public function test_locked_attendance_is_read_only_and_denied_without_lock_permissions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04 19:00:00', 'UTC'));

        try {
            [$user, $class, $classMember, $jamaat, $jamaatMember, $prayer] = $this->attendanceContext([
                'quran.attendance.create',
                'salah.attendance.create',
            ]);
            $this->actingAs($user);
            $date = '2026-08-05';

            $this->get(route('quran-attendance.create', ['class_id' => $class->id, 'date' => $date]))
                ->assertOk()
                ->assertViewHas('attendanceReadOnly', true)
                ->assertViewHas('maxDate', $date);
            $this->get(route('salah-attendance.create', [
                'jamaat_id' => $jamaat->id,
                'prayer_id' => $prayer->id,
                'date' => $date,
            ]))->assertOk()->assertViewHas('attendanceReadOnly', true);

            $this->post(route('quran-attendance.store'), [
                'class_id' => $class->id,
                'date' => $date,
                'attendance' => [$classMember->id => null],
            ])->assertForbidden();
            $this->post(route('salah-attendance.store'), [
                'jamaat_id' => $jamaat->id,
                'date' => $date,
                'attendance' => [$jamaatMember->id => [$prayer->id => null]],
            ])->assertForbidden();

            $this->assertDatabaseCount('quran_attendance', 0);
            $this->assertDatabaseCount('salah_attendance', 0);
            $this->assertDatabaseCount('audit_logs', 0);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_lock_permissions_allow_overrides_and_write_immutable_audit_events(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04 19:00:00', 'UTC'));

        try {
            [$user, $class, $classMember, $jamaat, $jamaatMember, $prayer] = $this->attendanceContext([
                'quran.attendance.create',
                'quran.attendance.lock',
                'salah.attendance.create',
                'salah.attendance.lock',
            ]);
            $this->actingAs($user);
            $date = '2026-08-05';

            $this->post(route('quran-attendance.store'), [
                'class_id' => $class->id,
                'date' => $date,
                'attendance' => [$classMember->id => null],
            ])->assertRedirect();
            $this->post(route('salah-attendance.store'), [
                'jamaat_id' => $jamaat->id,
                'date' => $date,
                'attendance' => [$jamaatMember->id => [$prayer->id => null]],
            ])->assertRedirect();

            $quranLog = AuditLog::query()
                ->where('module', 'quran_attendance')
                ->where('action', 'lock_override')
                ->firstOrFail();
            $salahLog = AuditLog::query()
                ->where('module', 'salah_attendance')
                ->where('action', 'lock_override')
                ->firstOrFail();

            $quranAttendance = QuranAttendance::query()
                ->where('company_id', $user->company_id)
                ->where('class_id', $class->id)
                ->where('employee_id', $classMember->id)
                ->firstOrFail();
            $salahAttendance = SalahAttendance::query()
                ->where('company_id', $user->company_id)
                ->where('jamaat_id', $jamaat->id)
                ->where('prayer_id', $prayer->id)
                ->where('employee_id', $jamaatMember->id)
                ->firstOrFail();

            $this->assertSame($date, $quranAttendance->attendance_date->toDateString());
            $this->assertSame($date, $salahAttendance->attendance_date->toDateString());
            $this->assertSame($date, $quranLog->new_values['attendance_date']);
            $this->assertSame($class->id, $quranLog->new_values['class_id']);
            $this->assertSame('00:00', $quranLog->new_values['lock_time']);
            $this->assertSame($date, $salahLog->new_values['attendance_date']);
            $this->assertSame($jamaat->id, $salahLog->new_values['jamaat_id']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_lock_time_uses_the_company_timezone_and_strictly_falls_back_for_invalid_values(): void
    {
        $user = $this->createUserWithCompany();
        $company = Company::findOrFail($user->company_id);
        $company->update(['timezone' => 'Asia/Karachi']);
        $this->actingAs($user);

        $setting = Setting::create([
            'company_id' => $company->id,
            'key' => 'attendance_lock_time',
            'value' => '23:59',
        ]);
        $service = app(AttendanceLockService::class);

        try {
            Carbon::setTestNow(Carbon::parse('2026-08-04 18:58:00', 'UTC'));
            $this->assertFalse($service->isLocked((int) $company->id, '2026-08-04'));

            Carbon::setTestNow(Carbon::parse('2026-08-04 18:59:00', 'UTC'));
            $this->assertTrue($service->isLocked((int) $company->id, '2026-08-04'));

            $setting->update(['value' => '23:59:00']);
            $this->assertSame(AttendanceLockService::DEFAULT_LOCK_TIME, $service->lockTime((int) $company->id));
        } finally {
            Carbon::setTestNow();
        }
    }

    /** @return array{User, QuranClass, Employee, Jamaat, Employee, Prayer} */
    private function attendanceContext(array $permissions): array
    {
        $user = $this->createUserWithCompany($permissions);
        $company = Company::findOrFail($user->company_id);
        $company->update(['timezone' => 'Asia/Karachi']);
        Setting::create([
            'company_id' => $company->id,
            'key' => 'attendance_lock_time',
            'value' => '00:00',
        ]);

        $class = QuranClass::factory()->create(['company_id' => $company->id]);
        $classMember = Employee::factory()->create(['company_id' => $company->id]);
        QuranClassMember::create([
            'class_id' => $class->id,
            'employee_id' => $classMember->id,
            'is_active' => true,
            'joined_at' => '2026-08-01',
        ]);

        $jamaat = Jamaat::factory()->create(['company_id' => $company->id]);
        $jamaatMember = Employee::factory()->create(['company_id' => $company->id]);
        JamaatMember::create([
            'jamaat_id' => $jamaat->id,
            'employee_id' => $jamaatMember->id,
            'is_active' => true,
            'joined_at' => '2026-08-01',
        ]);

        return [$user, $class, $classMember, $jamaat, $jamaatMember, Prayer::factory()->create()];
    }
}
