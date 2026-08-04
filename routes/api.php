<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\EmployeeApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\QuranApiController;
use App\Http\Controllers\Api\SalahApiController;
use App\Http\Controllers\Api\TeacherApiController;
use Illuminate\Support\Facades\Route;

// ── Public Routes (Guest) ─────────────────────────────────────────

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Auth — rate limited to 5 login attempts per minute
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    });

    // ── Authenticated Routes ──────────────────────────────────────

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

        // Auth
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('profile', [AuthController::class, 'profile'])->name('auth.profile');
        Route::put('profile', [AuthController::class, 'updateProfile'])->name('auth.profile.update');
        Route::put('change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');
        Route::get('me/unread-notifications-count', [AuthController::class, 'unreadNotificationsCount'])->name('auth.notifications.count');

        // Dashboard
        Route::get('dashboard', [DashboardApiController::class, 'index'])->name('dashboard');

        // Employees
        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/', [EmployeeApiController::class, 'index'])->name('index');
            Route::get('{id}', [EmployeeApiController::class, 'show'])->name('show');
        });

        // Teachers
        Route::prefix('teachers')->name('teachers.')->group(function () {
            Route::get('/', [TeacherApiController::class, 'index'])->name('index');
            Route::get('{id}', [TeacherApiController::class, 'show'])->name('show');
        });

        // Quran Module
        Route::prefix('quran')->name('quran.')->group(function () {
            Route::get('classes', [QuranApiController::class, 'classes'])->name('classes.index');
            Route::get('classes/{id}', [QuranApiController::class, 'showClass'])->name('classes.show');
            Route::get('attendance', [QuranApiController::class, 'attendance'])->name('attendance.index');
        });

        // Salah Module
        Route::prefix('salah')->name('salah.')->group(function () {
            Route::get('jamaats', [SalahApiController::class, 'jamaats'])->name('jamaats.index');
            Route::get('jamaats/{id}', [SalahApiController::class, 'showJamaat'])->name('jamaats.show');
            Route::get('attendance', [SalahApiController::class, 'attendance'])->name('attendance.index');
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationApiController::class, 'index'])->name('index');
            Route::post('{id}/read', [NotificationApiController::class, 'markRead'])->name('mark-read');
            Route::post('read-all', [NotificationApiController::class, 'markAllRead'])->name('mark-all-read');
            Route::delete('{id}', [NotificationApiController::class, 'destroy'])->name('destroy');
        });

    });

});
