<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentsListController;
use App\Http\Controllers\ManualAttendanceController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [AttendanceController::class, 'index']);
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/export', [AttendanceController::class, 'export']);
    Route::get('/attendance/check-updates', [AttendanceController::class, 'checkUpdates'])->name('attendance.check-updates');
    Route::get('/students/{roll}', [AttendanceController::class, 'student'])->name('students.show');
    Route::post('/students/{roll}', [StudentController::class, 'update'])->name('students.update');
    Route::post('/students/{roll}/contact', [StudentController::class, 'updateContact'])->name('students.updateContact');

    Route::get('/settings', [SettingsController::class, 'edit']);
    Route::post('/settings', [SettingsController::class, 'update']);

    // Students List (All authenticated users)
    Route::get('/students', [StudentsListController::class, 'index'])->name('students.index');

    // Manual Attendance (All authenticated users)
    Route::prefix('manual-attendance')->name('manual-attendance.')->group(function () {
        Route::get('/', [ManualAttendanceController::class, 'index'])->name('index');
        Route::post('/mark-present', [ManualAttendanceController::class, 'markPresent'])->name('mark-present');
        Route::post('/mark-out', [ManualAttendanceController::class, 'markOut'])->name('mark-out');
    });

    // User Management (Super Admin Only)
    Route::prefix('users')->name('users.')->middleware('superadmin')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::match(['put', 'patch'], '/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Profile Management (All authenticated users)
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/change-password', [ProfileController::class, 'showChangePassword'])->name('change-password');
        Route::post('/change-password', [ProfileController::class, 'updatePassword']);
    });
});
