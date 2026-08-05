<?php

namespace App\Helpers;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Timezone conversion helper.
 *
 * Architecture Decision #5: All dates stored in UTC.
 * Display using the authenticated user's company timezone.
 */
class TimezoneHelper
{
    /**
     * Convert a UTC datetime to the company's timezone for display.
     */
    public static function toCompanyTimezone(Carbon|string|null $datetime, ?string $timezone = null): ?Carbon
    {
        if ($datetime === null) {
            return null;
        }

        if (is_string($datetime)) {
            $datetime = Carbon::parse($datetime, 'UTC');
        }

        $tz = $timezone ?? static::getCompanyTimezone();

        return $datetime->setTimezone($tz);
    }

    /**
     * Convert a local datetime to UTC for storage.
     */
    public static function toUtc(Carbon|string|null $datetime, ?string $timezone = null): ?Carbon
    {
        if ($datetime === null) {
            return null;
        }

        $tz = $timezone ?? static::getCompanyTimezone();

        if (is_string($datetime)) {
            $datetime = Carbon::parse($datetime, $tz);
        }

        return $datetime->setTimezone('UTC');
    }

    /**
     * Format a UTC datetime for display in company timezone.
     */
    public static function formatForDisplay(Carbon|string|null $datetime, string $format = 'Y-m-d H:i:s', ?string $timezone = null): ?string
    {
        $converted = static::toCompanyTimezone($datetime, $timezone);

        return $converted?->format($format);
    }

    /**
     * Get a company's timezone, or the authenticated user's company timezone.
     * Falls back to Asia/Karachi (Pakistan) as the default.
     */
    public static function getCompanyTimezone(?int $companyId = null): string
    {
        if ($companyId !== null) {
            $timezone = Company::query()->whereKey($companyId)->value('timezone');

            return is_string($timezone) && $timezone !== '' ? $timezone : 'Asia/Karachi';
        }

        $user = Auth::user();

        if ($user instanceof User) {
            $company = $user->company;

            if ($company) {
                return $company->timezone ?? 'Asia/Karachi';
            }
        }

        return 'Asia/Karachi';
    }
}
