<?php

/**
 * ══════════════════════════════════════════════════════════════
 *  API Routes — FitNez Laravel
 * ══════════════════════════════════════════════════════════════
 *
 * Halaman Publik & Private (poin e):
 * - PUBLIC: landing page info, FAQ, tracking, register, login, verify-otp
 * - PROTECTED: semua fitur aplikasi (perlu auth + OTP verified)
 * ══════════════════════════════════════════════════════════════
 */

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ════════════════════════════════════════
//  PUBLIC ROUTES (tanpa auth)
//  Halaman publik: info aplikasi, FAQ, tracking pengunjung
// ════════════════════════════════════════

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

Route::get('/faq', [FaqController::class, 'index']);

// ── Tracking pengunjung landing page (poin e.2) ──
// Data hanya dicatat jika consent diberikan
Route::prefix('tracking')->group(function () {
    Route::post('/visit', [TrackingController::class, 'recordVisit']);
    Route::post('/time', [TrackingController::class, 'updateTime']);
});


// ════════════════════════════════════════
//  PROTECTED ROUTES (perlu login + OTP)
//  Halaman private: fitur aplikasi
// ════════════════════════════════════════

Route::middleware(['auth.fitnez'])->group(function () {

    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Profile
    Route::put('/users/profile', [UserController::class, 'updateProfile']);
    Route::post('/users/upgrade-trainer', [UserController::class, 'upgradeTrainer']);
    Route::post('/users/switch-role', [UserController::class, 'switchRole']);

    // Users CRUD (admin/trainer)
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::post('/users', [UserController::class, 'store']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

    // Schedule
    Route::get('/schedule', [ScheduleController::class, 'index']);

    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::post('/schedule', [ScheduleController::class, 'store']);
        Route::put('/schedule/{id}', [ScheduleController::class, 'update']);
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::delete('/schedule/{id}', [ScheduleController::class, 'destroy']);
    });

    // Personal schedule
    Route::get('/schedule/my', [ScheduleController::class, 'mySchedules']);
    Route::post('/schedule/my', [ScheduleController::class, 'createMySchedule']);
    Route::delete('/schedule/my/{id}', [ScheduleController::class, 'deleteMySchedule']);

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'checkIn']);

    // Auth Logs (admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/logs', [LogController::class, 'index']);
        Route::delete('/logs', [LogController::class, 'clear']);

        // Tracking stats (admin only)
        Route::get('/tracking/stats', [TrackingController::class, 'stats']);
    });

    // Long Polling
    Route::get('/notifications/poll', [NotificationController::class, 'poll']);
});
