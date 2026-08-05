<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\QuranAttendanceService;
use App\Services\SalahAttendanceService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceSettingsTest extends TestCase
{
    public function test_zero_day_backdate_setting_disallows_yesterdays_attendance_for_both_modules(): void
    {
        $user = $this->createUserWithCompany();
        $companyId = (int) $user->company_id;

        $this->actingAs($user);

        Setting::create([
            'company_id' => $companyId,
            'key' => 'max_backdated_attendance_days',
            'value' => '0',
        ]);

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $quranAttendance = app(QuranAttendanceService::class);
        $salahAttendance = app(SalahAttendanceService::class);

        $this->assertTrue($quranAttendance->isDateAllowed($today, $companyId));
        $this->assertFalse($quranAttendance->isDateAllowed($yesterday, $companyId));
        $this->assertTrue($salahAttendance->isDateAllowed($today, $companyId));
        $this->assertFalse($salahAttendance->isDateAllowed($yesterday, $companyId));
    }

    public function test_attendance_services_enforce_the_backdate_rule_when_called_directly(): void
    {
        $user = $this->createUserWithCompany();
        $companyId = (int) $user->company_id;

        $this->actingAs($user);
        Setting::create([
            'company_id' => $companyId,
            'key' => 'max_backdated_attendance_days',
            'value' => '0',
        ]);

        $yesterday = now()->subDay()->toDateString();

        try {
            app(QuranAttendanceService::class)->saveAttendance(999, $yesterday, $companyId, $user, []);
            $this->fail('Quran attendance accepted a disallowed backdate.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('date', $exception->errors());
        }

        try {
            app(SalahAttendanceService::class)->saveAttendance(999, 999, $yesterday, $companyId, $user, []);
            $this->fail('Salah attendance accepted a disallowed backdate.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('date', $exception->errors());
        }
    }
}
