<?php

namespace Tests\Feature;

use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\Prayer;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use App\Models\User;
use App\Services\DashboardService;
use Tests\TestCase;

/**
 * Dashboard attendance trend — daily rate over the last two weeks.
 */
class DashboardTrendTest extends TestCase
{
    private function admin(): User
    {
        return $this->createUserWithCompany([
            'quran.attendance.view',
            'salah.attendance.view',
        ]);
    }

    /** @return array<string, mixed> */
    private function trendFor(User $user): array
    {
        $this->actingAs($user);

        return app(DashboardService::class)->attendanceTrend();
    }

    public function test_trend_returns_one_row_per_day_including_empty_days(): void
    {
        $trend = $this->trendFor($this->admin());

        $this->assertCount(DashboardService::TREND_DAYS, $trend['days']);
        $this->assertTrue($trend['has_quran']);
        $this->assertTrue($trend['has_salah']);

        // A day with no records reports a null rate rather than a misleading 0%.
        $this->assertNull($trend['days'][0]['quran']['rate']);
        $this->assertSame(0, $trend['days'][0]['quran']['total']);
    }

    public function test_trend_computes_the_daily_rate_for_each_module(): void
    {
        $user = $this->admin();
        $companyId = $user->company_id;

        $branch = Branch::factory()->create(['company_id' => $companyId]);
        $employee = Employee::factory()->create(['company_id' => $companyId, 'branch_id' => $branch->id]);
        $teacher = Teacher::factory()->create(['company_id' => $companyId, 'employee_id' => $employee->id]);
        $class = QuranClass::factory()->create([
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
        ]);
        $jamaat = Jamaat::factory()->create([
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'leader_id' => $employee->id,
        ]);
        $prayer = Prayer::factory()->create();
        $reason = AttendanceReason::factory()->create(['company_id' => $companyId]);

        $today = now()->toDateString();

        // Attendance is unique per (date, class, employee), so each record
        // needs its own member — as it would in production.
        $members = Employee::factory()->count(3)->create([
            'company_id' => $companyId,
            'branch_id' => $branch->id,
        ]);

        // 3 Quran records today, 2 of them present → 66.7%
        foreach ($members as $index => $member) {
            QuranAttendance::factory()->create([
                'company_id' => $companyId,
                'class_id' => $class->id,
                'employee_id' => $member->id,
                'teacher_id' => $teacher->id,
                'attendance_date' => $today,
                'attendance_reason_id' => $index === 2 ? $reason->id : null,
            ]);
        }

        // 2 Salah records today, both present → 100%
        foreach ($members->take(2) as $member) {
            SalahAttendance::factory()->create([
                'company_id' => $companyId,
                'jamaat_id' => $jamaat->id,
                'employee_id' => $member->id,
                'prayer_id' => $prayer->id,
                'attendance_date' => $today,
                'attendance_reason_id' => null,
            ]);
        }

        $trend = $this->trendFor($user);
        $latest = $trend['days'][count($trend['days']) - 1];

        $this->assertSame($today, $latest['date']);
        $this->assertSame(3, $latest['quran']['total']);
        $this->assertSame(2, $latest['quran']['present']);
        $this->assertSame(66.7, $latest['quran']['rate']);
        $this->assertSame(2, $latest['salah']['total']);
        $this->assertSame(100.0, $latest['salah']['rate']);
    }

    public function test_trend_hides_modules_the_user_cannot_view(): void
    {
        $trend = $this->trendFor($this->createUserWithCompany(['quran.attendance.view']));

        $this->assertTrue($trend['has_quran']);
        $this->assertFalse($trend['has_salah']);
    }

    public function test_dashboard_renders_the_trend(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.trend_title'));
    }
}
