<?php

namespace App\Providers;

use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatTaleem;
use App\Models\Language;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranDepartment;
use App\Models\QuranProgress;
use App\Models\QuranProgressHistory;
use App\Models\QuranStatus;
use App\Models\QuranTeacherAttendance;
use App\Models\SalahAttendance;
use App\Models\Setting;
use App\Models\Teacher;
use App\Observers\BusinessAuditObserver;
use App\Observers\DashboardCacheObserver;
use App\Support\Impersonation;
use App\View\Composers\AppShellComposer;
use App\View\Composers\NotificationComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Throw an exception in non-production environments whenever a lazy
        // load is detected, forcing eager-loading via with() / load().
        // This prevents N+1 query bugs from slipping into production.
        Model::preventLazyLoading(! app()->isProduction());

        // Prevent silently discarding fills on non-fillable attributes.
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        // A platform account viewing a tenant may look, not touch. This covers
        // the policy abilities the views ask for — @can('create', Branch::class)
        // and friends — so the write controls simply are not rendered. Raw
        // permission strings are refused a layer earlier, in User::
        // hasPermissionTo(), because Spatie answers those before this callback
        // is reached. Returning null leaves every other decision untouched.
        Gate::before(static function ($user, string $ability): ?bool {
            return Impersonation::isReadOnly() && Impersonation::isWriteAbility($ability)
                ? false
                : null;
        });

        // Share unread notification count with the main layout via View Composer.
        View::composer('layouts.app', NotificationComposer::class);

        // Share the signed-in user's company name with the application shell.
        View::composer('layouts.app', AppShellComposer::class);

        Employee::observe(DashboardCacheObserver::class);
        Teacher::observe(DashboardCacheObserver::class);
        QuranClass::observe(DashboardCacheObserver::class);
        Jamaat::observe(DashboardCacheObserver::class);
        QuranProgress::observe(DashboardCacheObserver::class);
        QuranAttendance::observe(DashboardCacheObserver::class);
        SalahAttendance::observe(DashboardCacheObserver::class);

        foreach ([
            AttendanceReason::class,
            Branch::class,
            Department::class,
            Designation::class,
            Employee::class,
            Jamaat::class,
            JamaatTaleem::class,
            Language::class,
            QuranAttendance::class,
            QuranClass::class,
            QuranDepartment::class,
            QuranProgress::class,
            QuranProgressHistory::class,
            QuranStatus::class,
            QuranTeacherAttendance::class,
            SalahAttendance::class,
            Setting::class,
            Teacher::class,
        ] as $model) {
            $model::observe(BusinessAuditObserver::class);
        }
    }
}
