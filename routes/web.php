<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
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

// ── Authenticated Routes ─────────────────────────────────────────

Route::middleware(['auth', 'company.active', 'user.active', 'set.locale'])->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('change-password', [ChangePasswordController::class, 'showChangePasswordForm'])->name('password.change.form');
    Route::post('change-password', [ChangePasswordController::class, 'changePassword'])->name('password.change');

    Route::get('dashboard', fn () => view('dashboard'))->name('dashboard');
});
