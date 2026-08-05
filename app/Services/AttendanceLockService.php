<?php

namespace App\Services;

use App\Helpers\TimezoneHelper;
use App\Models\Setting;
use Carbon\Carbon;

class AttendanceLockService
{
    public const DEFAULT_LOCK_TIME = '23:59';

    public function isLocked(int $companyId, string $attendanceDate): bool
    {
        $timezone = TimezoneHelper::getCompanyTimezone($companyId);
        $lockAt = Carbon::createFromFormat(
            'Y-m-d H:i',
            $attendanceDate.' '.$this->lockTime($companyId),
            $timezone,
        );

        return Carbon::now($timezone)->greaterThanOrEqualTo($lockAt);
    }

    public function lockTime(int $companyId): string
    {
        $value = Setting::query()
            ->where('company_id', $companyId)
            ->where('key', 'attendance_lock_time')
            ->value('value');

        return is_string($value) && preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $value) === 1
            ? $value
            : self::DEFAULT_LOCK_TIME;
    }
}
