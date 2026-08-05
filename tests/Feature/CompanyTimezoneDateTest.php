<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatMember;
use App\Models\Prayer;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranClassMember;
use App\Models\SalahAttendance;
use App\Models\Setting;
use App\Services\DashboardService;
use App\Services\JamaatMemberService;
use App\Services\QuranAttendanceService;
use App\Services\QuranClassMemberService;
use App\Services\SalahAttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyTimezoneDateTest extends TestCase
{
    public function test_attendance_validation_and_dashboard_use_the_company_local_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04 21:30:00', 'UTC'));

        try {
            $user = $this->createUserWithCompany([
                'quran.attendance.view',
                'salah.attendance.view',
            ]);
            $companyId = (int) $user->company_id;
            $user->company->update(['timezone' => 'Asia/Karachi']);
            $this->actingAs($user);

            Setting::create([
                'company_id' => $companyId,
                'key' => 'max_backdated_attendance_days',
                'value' => '0',
            ]);

            $quranAttendance = app(QuranAttendanceService::class);
            $salahAttendance = app(SalahAttendanceService::class);

            $this->assertTrue($quranAttendance->isDateAllowed('2026-08-05', $companyId));
            $this->assertFalse($quranAttendance->isDateAllowed('2026-08-04', $companyId));
            $this->assertTrue($salahAttendance->isDateAllowed('2026-08-05', $companyId));
            $this->assertFalse($salahAttendance->isDateAllowed('2026-08-04', $companyId));

            $employee = Employee::factory()->create(['company_id' => $companyId]);
            $class = QuranClass::factory()->create(['company_id' => $companyId]);
            $jamaat = Jamaat::factory()->create(['company_id' => $companyId]);
            $prayer = Prayer::factory()->create();

            DB::table('quran_attendance')->insert([
                'company_id' => $companyId,
                'attendance_date' => '2026-08-05',
                'class_id' => $class->id,
                'employee_id' => $employee->id,
            ]);
            DB::table('salah_attendance')->insert([
                'company_id' => $companyId,
                'attendance_date' => '2026-08-05',
                'jamaat_id' => $jamaat->id,
                'prayer_id' => $prayer->id,
                'employee_id' => $employee->id,
            ]);

            Cache::flush();
            $dashboard = app(DashboardService::class);

            $this->assertSame(1, $dashboard->todayQuranAttendance()['total']);
            $this->assertSame(1, $dashboard->todaySalahAttendance()['total']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_memberships_and_attendance_forms_use_the_company_local_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04 21:30:00', 'UTC'));

        try {
            $user = $this->createUserWithCompany([
                'quran.attendance.create',
                'salah.attendance.create',
            ]);
            $companyId = (int) $user->company_id;
            $user->company->update(['timezone' => 'Asia/Karachi']);
            $this->actingAs($user);

            $class = QuranClass::factory()->create(['company_id' => $companyId]);
            $classMember = Employee::factory()->create(['company_id' => $companyId]);
            $jamaat = Jamaat::factory()->create(['company_id' => $companyId]);
            $jamaatMember = Employee::factory()->create(['company_id' => $companyId]);
            $prayer = Prayer::factory()->create();

            $quranMembers = app(QuranClassMemberService::class);
            $jamaatMembers = app(JamaatMemberService::class);

            $quranMembers->addMember($class->id, $classMember->id);
            $jamaatMembers->addMember($jamaat->id, $jamaatMember->id);

            $this->assertSame(
                '2026-08-05',
                QuranClassMember::query()
                    ->where('class_id', $class->id)
                    ->where('employee_id', $classMember->id)
                    ->firstOrFail()
                    ->joined_at
                    ->toDateString()
            );
            $this->assertSame(
                '2026-08-05',
                JamaatMember::query()
                    ->where('jamaat_id', $jamaat->id)
                    ->where('employee_id', $jamaatMember->id)
                    ->firstOrFail()
                    ->joined_at
                    ->toDateString()
            );

            $this->get(route('quran-attendance.create'))
                ->assertOk()
                ->assertViewHas('selectedDate', '2026-08-05');
            $this->get(route('salah-attendance.create'))
                ->assertOk()
                ->assertViewHas('selectedDate', '2026-08-05');

            $this->post(route('quran-attendance.store'), [
                'class_id' => $class->id,
                'date' => '2026-08-05',
                'attendance' => [$classMember->id => null],
            ])->assertRedirect();
            $this->post(route('salah-attendance.store'), [
                'jamaat_id' => $jamaat->id,
                'prayer_id' => $prayer->id,
                'date' => '2026-08-05',
                'attendance' => [$jamaatMember->id => null],
            ])->assertRedirect();

            $this->assertSame(
                '2026-08-05',
                QuranAttendance::query()
                    ->where('class_id', $class->id)
                    ->where('employee_id', $classMember->id)
                    ->firstOrFail()
                    ->attendance_date
                    ->toDateString()
            );
            $this->assertSame(
                '2026-08-05',
                SalahAttendance::query()
                    ->where('jamaat_id', $jamaat->id)
                    ->where('prayer_id', $prayer->id)
                    ->where('employee_id', $jamaatMember->id)
                    ->firstOrFail()
                    ->attendance_date
                    ->toDateString()
            );

            $quranMembers->removeMember($class->id, $classMember->id);
            $jamaatMembers->removeMember($jamaat->id, $jamaatMember->id);

            $this->assertSame(
                '2026-08-05',
                QuranClassMember::query()
                    ->where('class_id', $class->id)
                    ->where('employee_id', $classMember->id)
                    ->firstOrFail()
                    ->left_at
                    ->toDateString()
            );
            $this->assertSame(
                '2026-08-05',
                JamaatMember::query()
                    ->where('jamaat_id', $jamaat->id)
                    ->where('employee_id', $jamaatMember->id)
                    ->firstOrFail()
                    ->left_at
                    ->toDateString()
            );
        } finally {
            Carbon::setTestNow();
        }
    }
}
