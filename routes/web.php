<?php

use App\Enums\AttendanceReasonType;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Web\AnalyticsController;
use App\Http\Controllers\Web\BulkActionController;
use App\Http\Controllers\Web\CompanyController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DataTransferController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\ImpersonationController;
use App\Http\Controllers\Web\JamaatController;
use App\Http\Controllers\Web\JamaatMemberController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\Masters\AttendanceReasonController;
use App\Http\Controllers\Web\Masters\BranchController;
use App\Http\Controllers\Web\Masters\DepartmentController;
use App\Http\Controllers\Web\Masters\DesignationController;
use App\Http\Controllers\Web\Masters\LanguageController;
use App\Http\Controllers\Web\Masters\QuranDepartmentController;
use App\Http\Controllers\Web\Masters\QuranStatusController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\QuranAttendanceController;
use App\Http\Controllers\Web\QuranClassController;
use App\Http\Controllers\Web\QuranClassMemberController;
use App\Http\Controllers\Web\QuranProgressController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\SalahAttendanceController;
use App\Http\Controllers\Web\SavedFilterController;
use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\TeacherController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

// ── Guest Routes ─────────────────────────────────────────────────

Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));

    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);

    Route::get('forgot-password', [ForgotPasswordController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// ── Display language ─────────────────────────────────────────────
// Outside both the guest and auth groups: the language switcher is offered on
// the sign-in screens as well as inside the application.

Route::post('locale', LocaleController::class)->name('locale.update');

// ── Authenticated Routes ─────────────────────────────────────────

// set.locale runs globally on the web group (see bootstrap/app.php), so it is
// not repeated here.
//
// platform.boundary keeps the platform account on the tenant register and off
// every tenant module; impersonation.readonly refuses writes while it is
// looking inside a company. Both are no-ops for ordinary tenant users.
Route::middleware([
    'auth',
    'company.active',
    'user.active',
    'platform.boundary',
    'impersonation.readonly',
])->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('change-password', [ChangePasswordController::class, 'showChangePasswordForm'])->name('password.change.form');
    Route::post('change-password', [ChangePasswordController::class, 'changePassword'])->name('password.change');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // ── Employees ─────────────────────────────────────────────
    Route::resource('employees', EmployeeController::class);
    Route::get('employees/{employee}/photo', [EmployeeController::class, 'photo'])->name('employees.photo');
    Route::post('employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore')->withTrashed();

    // ── Teachers ──────────────────────────────────────────────
    Route::resource('teachers', TeacherController::class);
    Route::post('teachers/{teacher}/restore', [TeacherController::class, 'restore'])->name('teachers.restore')->withTrashed();

    // ── Quran Classes ────────────────────────────────────────────
    Route::resource('quran-classes', QuranClassController::class);
    Route::post('quran-classes/{quran_class}/restore', [QuranClassController::class, 'restore'])->name('quran-classes.restore')->withTrashed();

    // ── Quran Class Members ──────────────────────────────────────
    Route::get('quran-classes/{quran_class}/members', [QuranClassMemberController::class, 'index'])->name('quran-classes.members.index');
    Route::post('quran-classes/{quran_class}/members', [QuranClassMemberController::class, 'store'])->name('quran-classes.members.store');
    Route::delete('quran-classes/{quran_class}/members/{employee}', [QuranClassMemberController::class, 'destroy'])->name('quran-classes.members.destroy');

    // ── Quran Attendance ─────────────────────────────────────────
    Route::get('quran-attendance', [QuranAttendanceController::class, 'index'])->name('quran-attendance.index');
    Route::get('quran-attendance/create', [QuranAttendanceController::class, 'create'])->name('quran-attendance.create');
    Route::post('quran-attendance', [QuranAttendanceController::class, 'store'])->name('quran-attendance.store');

    // ── Quran Progress ───────────────────────────────────────────
    Route::resource('quran-progress', QuranProgressController::class)->except('destroy');

    // ── Jamaats ─────────────────────────────────────────────────
    Route::resource('jamaats', JamaatController::class);
    Route::post('jamaats/{jamaat}/restore', [JamaatController::class, 'restore'])->name('jamaats.restore')->withTrashed();

    // ── Jamaat Members ──────────────────────────────────────────
    Route::get('jamaats/{jamaat}/members', [JamaatMemberController::class, 'index'])->name('jamaats.members.index');
    Route::post('jamaats/{jamaat}/members', [JamaatMemberController::class, 'store'])->name('jamaats.members.store');
    Route::delete('jamaats/{jamaat}/members/{employee}', [JamaatMemberController::class, 'destroy'])->name('jamaats.members.destroy');

    // ── Salah Attendance ────────────────────────────────────────
    Route::get('salah-attendance', [SalahAttendanceController::class, 'index'])->name('salah-attendance.index');
    Route::get('salah-attendance/create', [SalahAttendanceController::class, 'create'])->name('salah-attendance.create');
    Route::post('salah-attendance', [SalahAttendanceController::class, 'store'])->name('salah-attendance.store');

    // ── Reports ─────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('employees', [ReportController::class, 'employees'])->name('employees');
        Route::get('teachers', [ReportController::class, 'teachers'])->name('teachers');
        Route::get('quran-attendance', [ReportController::class, 'quranAttendance'])->name('quran-attendance');
        Route::get('quran-teacher-attendance', [ReportController::class, 'quranTeacherAttendance'])->name('quran-teacher-attendance');
        Route::get('quran-progress', [ReportController::class, 'quranProgress'])->name('quran-progress');
        Route::get('salah-attendance', [ReportController::class, 'salahAttendance'])->name('salah-attendance');
        Route::get('jamaat-taleem', [ReportController::class, 'jamaatTaleem'])->name('jamaat-taleem');
        Route::get('dashboard', [ReportController::class, 'dashboard'])->name('dashboard');

        // ── Analysis ────────────────────────────────────────────
        // One screen per dataset, any breakdown, any filter. {dataset} binds
        // to its definition via AnalyticsServiceProvider; an unknown key 404s.
        // Declared before the export routes so "analysis" is never mistaken
        // for one of the literal report segments above.
        Route::get('analysis', [AnalyticsController::class, 'index'])->name('analysis.index');
        Route::get('analysis/{dataset}', [AnalyticsController::class, 'show'])->name('analysis.show');
        Route::get('analysis/{dataset}/export', [AnalyticsController::class, 'export'])->name('analysis.export');

        // Excel Exports
        Route::get('export/employees', [ReportController::class, 'exportEmployees'])->name('export.employees');
        Route::get('export/teachers', [ReportController::class, 'exportTeachers'])->name('export.teachers');
        Route::get('export/quran-attendance', [ReportController::class, 'exportQuranAttendance'])->name('export.quran-attendance');
        Route::get('export/quran-teacher-attendance', [ReportController::class, 'exportQuranTeacherAttendance'])->name('export.quran-teacher-attendance');
        Route::get('export/salah-attendance', [ReportController::class, 'exportSalahAttendance'])->name('export.salah-attendance');
        Route::get('export/jamaat-taleem', [ReportController::class, 'exportJamaatTaleem'])->name('export.jamaat-taleem');
    });

    // ── Notifications ────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('{id}/mark-read', [NotificationController::class, 'markRead'])->name('mark-read');
        Route::post('mark-all-read', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
        Route::delete('{id}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
    });

    // ── Users ───────────────────────────────────
    Route::resource('users', UserController::class);

    // ── Roles ───────────────────────────────────
    Route::resource('roles', RoleController::class)->except('show');

    // ── Companies (platform account only) ──────────────
    Route::resource('companies', CompanyController::class)->except('show');

    // Signing in to a tenant to see what it sees, and signing back out of it.
    Route::post('companies/{company}/impersonate', [ImpersonationController::class, 'start'])->name('impersonate.start');
    Route::post('impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    // ── Settings ───────────────────────────────
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    // ── Import / Export ─────────────────────────────────────────
    // One set of routes serves every module. {resource} is bound to the
    // module's definition by DataTransferServiceProvider; an unknown key 404s.
    // The history routes are declared first so their literal segments are
    // never mistaken for a module key.
    Route::prefix('data')->name('data.')->group(function () {
        Route::get('imports', [DataTransferController::class, 'imports'])->name('imports.index');
        Route::get('imports/{importLog}', [DataTransferController::class, 'showImport'])->name('imports.show');
        Route::get('imports/{importLog}/status', [DataTransferController::class, 'status'])->name('imports.status');
        Route::get('imports/{importLog}/errors', [DataTransferController::class, 'errors'])->name('imports.errors');

        Route::get('exports', [DataTransferController::class, 'exports'])->name('exports.index');
        Route::get('exports/{exportLog}/download', [DataTransferController::class, 'download'])->name('exports.download');

        Route::delete('filters/{savedFilter}', [SavedFilterController::class, 'destroy'])->name('filters.destroy');

        Route::get('{resource}/sample', [DataTransferController::class, 'sample'])->name('sample');
        Route::get('{resource}/export', [DataTransferController::class, 'export'])->name('export');
        Route::post('{resource}/import/preview', [DataTransferController::class, 'preview'])->name('import.preview');
        Route::post('{resource}/import', [DataTransferController::class, 'import'])->name('import');
        Route::post('{resource}/bulk', BulkActionController::class)->name('bulk');
        Route::post('{resource}/filters', [SavedFilterController::class, 'store'])->name('filters.store');
    });

    // ── Master Data ─────────────────────────────────────────────
    Route::prefix('masters')->name('masters.')->group(function () {
        Route::resource('branches', BranchController::class)->except('show');
        Route::post('branches/{branch}/restore', [BranchController::class, 'restore'])->name('branches.restore')->withTrashed();

        Route::resource('departments', DepartmentController::class)->except('show');
        Route::post('departments/{department}/restore', [DepartmentController::class, 'restore'])->name('departments.restore')->withTrashed();

        Route::resource('designations', DesignationController::class)->except('show');
        Route::post('designations/{designation}/restore', [DesignationController::class, 'restore'])->name('designations.restore')->withTrashed();

        // One page, tab-switched by {type} (Salah/Quran/Taleem) — each list is
        // fully independent underneath, but shares one controller/route-set/
        // view-set. See docs/features/attendance-reasons/README.md.
        Route::prefix('attendance-reasons')->name('attendance-reasons.')->group(function () {
            // Zero-param names for ResourceDefinitionContract::indexRoute() — every
            // "back to list" link in the DataTransfer preview/result/saved-filter
            // views calls route($name) with no extra parameters. Distinct 3-segment
            // URIs so they can never collide with the 2-segment {type} wildcard below.
            Route::get('go/salah', fn () => redirect()->route('masters.attendance-reasons.index', ['type' => AttendanceReasonType::Salah]))->name('salah.index');
            Route::get('go/quran', fn () => redirect()->route('masters.attendance-reasons.index', ['type' => AttendanceReasonType::Quran]))->name('quran.index');
            Route::get('go/taleem', fn () => redirect()->route('masters.attendance-reasons.index', ['type' => AttendanceReasonType::Taleem]))->name('taleem.index');

            Route::get('{type}', [AttendanceReasonController::class, 'index'])->name('index');
            Route::get('{type}/create', [AttendanceReasonController::class, 'create'])->name('create');
            Route::post('{type}', [AttendanceReasonController::class, 'store'])->name('store');
            Route::get('{type}/{attendance_reason}/edit', [AttendanceReasonController::class, 'edit'])->name('edit');
            Route::put('{type}/{attendance_reason}', [AttendanceReasonController::class, 'update'])->name('update');
            Route::delete('{type}/{attendance_reason}', [AttendanceReasonController::class, 'destroy'])->name('destroy');
            Route::post('{type}/{attendance_reason}/restore', [AttendanceReasonController::class, 'restore'])->name('restore')->withTrashed();
        });

        Route::resource('quran-departments', QuranDepartmentController::class)->except('show');
        Route::post('quran-departments/{quran_department}/restore', [QuranDepartmentController::class, 'restore'])->name('quran-departments.restore')->withTrashed();

        Route::resource('quran-statuses', QuranStatusController::class)->except('show');
        Route::post('quran-statuses/{quran_status}/restore', [QuranStatusController::class, 'restore'])->name('quran-statuses.restore')->withTrashed();

        Route::resource('languages', LanguageController::class)->except('show');
        Route::post('languages/{language}/restore', [LanguageController::class, 'restore'])->name('languages.restore')->withTrashed();
    });
});
