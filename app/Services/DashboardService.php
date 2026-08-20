<?php

namespace App\Services;

use App\Enums\Status;
use App\Helpers\TimezoneHelper;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranProgress;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Analytics\AttendanceExpression;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Cache TTL constants (in seconds).
     * Key convention: company:{company_id}:dashboard:{segment}
     */
    private const TTL_KPI = 300;       // 5 minutes — overview stats

    private const TTL_TODAY = 120;     // 2 minutes — today's live attendance

    private const TTL_SUMMARY = 600;   // 10 minutes — module summaries

    /** Days of history shown by the dashboard attendance trend. */
    public const TREND_DAYS = 14;

    /**
     * Resolve the current company_id for cache key scoping.
     */
    private function companyId(): int
    {
        $user = Auth::user();

        return $user instanceof User ? (int) $user->getAttribute('company_id') : 0;
    }

    /**
     * Build a company-scoped cache key.
     */
    private function key(string $segment): string
    {
        return 'company:'.$this->companyId().':dashboard:'.$segment;
    }

    /**
     * Forget all dashboard caches for the current company.
     * Call this after bulk data changes (imports, mass updates).
     */
    public function clearCache(?int $companyId = null): void
    {
        $id = $companyId ?? $this->companyId();

        if ($id <= 0) {
            return;
        }

        foreach (['overview', 'today_quran', 'today_salah', 'quran_summary', 'salah_summary', 'trend'] as $seg) {
            Cache::forget("company:{$id}:dashboard:{$seg}");
        }
    }

    /**
     * System administrator queries can intentionally span companies. Do not cache those
     * aggregates under a tenant key where another user could receive them.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    private function remember(string $segment, int $ttl, Closure $callback): mixed
    {
        $user = Auth::user();

        if (! $user instanceof User
            || $user->isSystemAdministrator()
            || app(RoleDataAccessService::class)->isRestricted($user)
            || ! $this->canShareSegment($user, $segment)) {
            return $callback();
        }

        return Cache::remember($this->key($segment), $ttl, $callback);
    }

    /**
     * Overview KPI cards — total and active counts for main entities.
     *
     * @return array<string, int>
     */
    public function overviewStats(): array
    {
        return $this->remember('overview', self::TTL_KPI, function () {
            $user = Auth::user();

            return [
                'total_employees' => $this->can($user, 'employee.view') ? Employee::count() : 0,
                'active_employees' => $this->can($user, 'employee.view') ? Employee::where('employment_status', Status::Active)->count() : 0,
                'total_teachers' => $this->can($user, 'teacher.view') ? Teacher::count() : 0,
                'active_teachers' => $this->can($user, 'teacher.view') ? Teacher::where('status', Status::Active)->count() : 0,
                'total_quran_classes' => $this->can($user, 'quran.class.view') ? QuranClass::count() : 0,
                'active_quran_classes' => $this->can($user, 'quran.class.view') ? QuranClass::where('status', Status::Active)->count() : 0,
                'total_jamaats' => $this->can($user, 'jamaat.view') ? Jamaat::count() : 0,
                'active_jamaats' => $this->can($user, 'jamaat.view') ? Jamaat::where('status', Status::Active)->count() : 0,
            ];
        });
    }

    /**
     * Today's quran attendance stats.
     *
     * @return array<string, int|float>
     */
    public function todayQuranAttendance(): array
    {
        return $this->remember('today_quran', self::TTL_TODAY, function () {
            $user = Auth::user();

            if (! $this->can($user, 'quran.attendance.view')) {
                return $this->emptyAttendanceSummary();
            }

            $today = Carbon::now(TimezoneHelper::getCompanyTimezone($this->companyId()))->toDateString();

            $total = QuranAttendance::where('attendance_date', $today)->where('class_held', true)->count();
            $absent = AttendanceExpression::countAbsent(
                QuranAttendance::where('attendance_date', $today)->where('class_held', true),
                'quran_attendance'
            );
            $present = $total - $absent;

            return [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Today's salah attendance stats.
     *
     * @return array<string, int|float>
     */
    public function todaySalahAttendance(): array
    {
        return $this->remember('today_salah', self::TTL_TODAY, function () {
            $user = Auth::user();

            if (! $this->can($user, 'salah.attendance.view')) {
                return $this->emptyAttendanceSummary();
            }

            $today = Carbon::now(TimezoneHelper::getCompanyTimezone($this->companyId()))->toDateString();

            $total = SalahAttendance::where('attendance_date', $today)->count();
            $absent = AttendanceExpression::countAbsent(
                SalahAttendance::where('attendance_date', $today),
                'salah_attendance'
            );
            $present = $total - $absent;

            return [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Quran module summary — progress and attendance totals.
     *
     * @return array<string, int|float>
     */
    public function quranSummary(): array
    {
        return $this->remember('quran_summary', self::TTL_SUMMARY, function () {
            $user = Auth::user();
            $canViewProgress = $this->can($user, 'quran.progress.view');
            $canViewAttendance = $this->can($user, 'quran.attendance.view');
            $totalProgress = $canViewProgress ? QuranProgress::count() : 0;
            $avgCompletion = $canViewProgress ? QuranProgress::avg('completion_percentage') ?? 0 : 0;
            $totalAttendance = $canViewAttendance ? QuranAttendance::count() : 0;

            return [
                'total_progress_records' => $totalProgress,
                'avg_completion' => round((float) $avgCompletion, 1),
                'total_attendance_records' => $totalAttendance,
            ];
        });
    }

    /**
     * Salah module summary — total attendance records.
     *
     * @return array<string, int>
     */
    public function salahSummary(): array
    {
        return $this->remember('salah_summary', self::TTL_SUMMARY, function () {
            $user = Auth::user();

            return [
                'total_attendance_records' => $this->can($user, 'salah.attendance.view')
                    ? SalahAttendance::count()
                    : 0,
            ];
        });
    }

    /**
     * Daily attendance rate for the last TREND_DAYS days, per module.
     *
     * Today's percentage alone cannot answer "are we improving?". This returns
     * one row per calendar day — including days with no records, so a gap in
     * recording is visible rather than silently skipped.
     *
     * @return array{
     *     days: list<array{date: string, quran: array{total: int, present: int, rate: float|null}, salah: array{total: int, present: int, rate: float|null}}>,
     *     has_quran: bool,
     *     has_salah: bool
     * }
     */
    public function attendanceTrend(): array
    {
        return $this->remember('trend', self::TTL_TODAY, function () {
            $user = Auth::user();
            $canQuran = $this->can($user, 'quran.attendance.view');
            $canSalah = $this->can($user, 'salah.attendance.view');

            $timezone = TimezoneHelper::getCompanyTimezone($this->companyId());
            $end = Carbon::now($timezone)->startOfDay();
            $start = $end->copy()->subDays(self::TREND_DAYS - 1);

            $empty = ['total' => 0, 'present' => 0, 'rate' => null];
            $days = [];

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $days[$cursor->toDateString()] = [
                    'date' => $cursor->toDateString(),
                    'quran' => $empty,
                    'salah' => $empty,
                ];
            }

            if ($canQuran) {
                foreach ($this->dailyAttendance(QuranAttendance::query()->where('class_held', true), $start, $end) as $date => $row) {
                    if (isset($days[$date])) {
                        $days[$date]['quran'] = $row;
                    }
                }
            }

            if ($canSalah) {
                foreach ($this->dailyAttendance(SalahAttendance::query(), $start, $end) as $date => $row) {
                    if (isset($days[$date])) {
                        $days[$date]['salah'] = $row;
                    }
                }
            }

            return [
                'days' => array_values($days),
                'has_quran' => $canQuran,
                'has_salah' => $canSalah,
            ];
        });
    }

    /**
     * Per-day totals for an attendance table, keyed by date.
     *
     * A single grouped query per module rather than one query per day.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return array<string, array{total: int, present: int, rate: float|null}>
     */
    private function dailyAttendance(Builder $query, Carbon $start, Carbon $end): array
    {
        $table = $query->getModel()->getTable();

        // Group on DATE(...) rather than the raw column: a date-cast attribute
        // is persisted as a full datetime string on SQLite, so a plain string
        // comparison would drop the final day and split one day into two
        // groups. DATE() behaves identically on MySQL and SQLite.
        $rows = AttendanceExpression::joinReasons($query, $table)
            ->whereDate("{$table}.attendance_date", '>=', $start->toDateString())
            ->whereDate("{$table}.attendance_date", '<=', $end->toDateString())
            ->selectRaw(
                "DATE({$table}.attendance_date) as day, COUNT(*) as total, SUM("
                .AttendanceExpression::absentCase($table).') as absent'
            )
            ->groupByRaw("DATE({$table}.attendance_date)")
            ->orderByRaw("DATE({$table}.attendance_date)")
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $total = (int) $row->getAttribute('total');
            $absent = (int) $row->getAttribute('absent');
            $present = $total - $absent;

            // `day` arrives as a string on MySQL and may be cast on SQLite.
            $day = $row->getAttribute('day');
            $date = $day instanceof Carbon ? $day->toDateString() : Carbon::parse((string) $day)->toDateString();

            $result[$date] = [
                'total' => $total,
                'present' => $present,
                'rate' => $total > 0 ? round(($present / $total) * 100, 1) : null,
            ];
        }

        return $result;
    }

    private function can(?User $user, string $permission): bool
    {
        return $user instanceof User && $user->can($permission);
    }

    private function canShareSegment(User $user, string $segment): bool
    {
        $permissions = match ($segment) {
            'overview' => ['employee.view', 'teacher.view', 'quran.class.view', 'jamaat.view'],
            'today_quran' => ['quran.attendance.view'],
            'today_salah', 'salah_summary' => ['salah.attendance.view'],
            'quran_summary' => ['quran.progress.view', 'quran.attendance.view'],
            'trend' => ['quran.attendance.view', 'salah.attendance.view'],
            default => [],
        };

        foreach ($permissions as $permission) {
            if (! $user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /** @return array{total: int, present: int, absent: int, percentage: float} */
    private function emptyAttendanceSummary(): array
    {
        return [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'percentage' => 0,
        ];
    }
}
