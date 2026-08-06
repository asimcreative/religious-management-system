<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmployeeController;
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
use App\Http\Controllers\Web\SalahAttendanceController;
use App\Http\Controllers\Web\TeacherController;
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
Route::middleware(['auth', 'company.active', 'user.active'])->group(function () {
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
        Route::get('quran-progress', [ReportController::class, 'quranProgress'])->name('quran-progress');
        Route::get('salah-attendance', [ReportController::class, 'salahAttendance'])->name('salah-attendance');
        Route::get('dashboard', [ReportController::class, 'dashboard'])->name('dashboard');

        // Excel Exports
        Route::get('export/employees', [ReportController::class, 'exportEmployees'])->name('export.employees');
        Route::get('export/teachers', [ReportController::class, 'exportTeachers'])->name('export.teachers');
        Route::get('export/quran-attendance', [ReportController::class, 'exportQuranAttendance'])->name('export.quran-attendance');
        Route::get('export/salah-attendance', [ReportController::class, 'exportSalahAttendance'])->name('export.salah-attendance');
    });

    // ── Notifications ────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('{id}/mark-read', [NotificationController::class, 'markRead'])->name('mark-read');
        Route::post('mark-all-read', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
        Route::delete('{id}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
    });

    // ── Master Data ─────────────────────────────────────────────
    Route::prefix('masters')->name('masters.')->group(function () {
        Route::resource('branches', BranchController::class)->except('show');
        Route::post('branches/{branch}/restore', [BranchController::class, 'restore'])->name('branches.restore')->withTrashed();

        Route::resource('departments', DepartmentController::class)->except('show');
        Route::post('departments/{department}/restore', [DepartmentController::class, 'restore'])->name('departments.restore')->withTrashed();

        Route::resource('designations', DesignationController::class)->except('show');
        Route::post('designations/{designation}/restore', [DesignationController::class, 'restore'])->name('designations.restore')->withTrashed();

        Route::resource('attendance-reasons', AttendanceReasonController::class)->except('show');
        Route::post('attendance-reasons/{attendance_reason}/restore', [AttendanceReasonController::class, 'restore'])->name('attendance-reasons.restore')->withTrashed();

        Route::resource('quran-departments', QuranDepartmentController::class)->except('show');
        Route::post('quran-departments/{quran_department}/restore', [QuranDepartmentController::class, 'restore'])->name('quran-departments.restore')->withTrashed();

        Route::resource('quran-statuses', QuranStatusController::class)->except('show');
        Route::post('quran-statuses/{quran_status}/restore', [QuranStatusController::class, 'restore'])->name('quran-statuses.restore')->withTrashed();

        Route::resource('languages', LanguageController::class)->except('show');
        Route::post('languages/{language}/restore', [LanguageController::class, 'restore'])->name('languages.restore')->withTrashed();
    });
});
