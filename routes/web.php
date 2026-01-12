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
use App\Http\Controllers\CourseController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\DataAdminController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\UserPermissionController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [AttendanceController::class, 'index']);
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/employees/attendance', [AttendanceController::class, 'index'])->name('employees.attendance');
    Route::get('/attendance/export', [AttendanceController::class, 'export']);
    Route::get('/attendance/check-updates', [AttendanceController::class, 'checkUpdates'])->name('attendance.check-updates');

    Route::get('/settings', [SettingsController::class, 'edit'])->middleware('superadmin');
    Route::post('/settings', [SettingsController::class, 'update'])->middleware('superadmin');

    // Students List (All authenticated users)
    Route::get('/students', [StudentsListController::class, 'index'])->name('students.index');

    // Profile Conversion (Super Admin Only) - MUST come before /students/{roll} route
    Route::prefix('students')->name('students.')->middleware('superadmin')->group(function () {
        Route::post('/{roll}/convert-to-employee', [StudentController::class, 'convertToEmployee'])->name('convert-to-employee');
    });
    Route::prefix('employees')->name('employees.')->middleware('superadmin')->group(function () {
        Route::post('/{roll}/convert-to-student', [EmployeeController::class, 'convertToStudent'])->name('convert-to-student');
    });

    // Courses Management (Super Admin Only) - MUST come before /students/{roll} route
    Route::prefix('students/courses')->name('courses.')->middleware('superadmin')->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('index');
        Route::get('/create', [CourseController::class, 'create'])->name('create');
        Route::post('/', [CourseController::class, 'store'])->name('store');
        Route::get('/{course}/edit', [CourseController::class, 'edit'])->name('edit');
        Route::match(['put', 'patch'], '/{course}', [CourseController::class, 'update'])->name('update');
        Route::delete('/{course}', [CourseController::class, 'destroy'])->name('destroy');
    });

    // Batches Management disabled from menu; keep routes for legacy direct access if needed
    // Route::prefix('students/batches')->name('batches.')->middleware('superadmin')->group(function () {
    //     Route::get('/', [BatchController::class, 'index'])->name('index');
    //     Route::get('/create', [BatchController::class, 'create'])->name('create');
    //     Route::post('/', [BatchController::class, 'store'])->name('store');
    //     Route::get('/{batch}/edit', [BatchController::class, 'edit'])->name('edit');
    //     Route::match(['put', 'patch'], '/{batch}', [BatchController::class, 'update'])->name('update');
    //     Route::delete('/{batch}', [BatchController::class, 'destroy'])->name('destroy');
    // });

    // Bulk Student Assignment (Super Admin Only) - MUST come before /students/{roll} route
    Route::prefix('students')->name('students.')->middleware('superadmin')->group(function () {
        Route::post('/bulk-assign-class', [StudentsListController::class, 'bulkAssignClass'])->name('bulk-assign-class');
        Route::post('/bulk-assign-batch', [StudentsListController::class, 'bulkAssignBatch'])->name('bulk-assign-batch');
        Route::post('/import', [StudentsListController::class, 'import'])->name('import');
        Route::post('/{roll}/discontinue', [StudentController::class, 'discontinue'])->name('discontinue');
        Route::post('/{roll}/restore', [StudentController::class, 'restore'])->name('restore');
        Route::delete('/{roll}/delete-permanent', [StudentController::class, 'deletePermanent'])->name('delete-permanent');
    });

    // Employees management (Super Admin)
    Route::prefix('employees')->name('employees.')->middleware('superadmin')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::post('/{roll}/generate-login', [EmployeeController::class, 'generateLogin'])->name('generate-login');
        Route::post('/{roll}/update-permissions', [EmployeeController::class, 'updatePermissions'])->name('update-permissions');
        Route::post('/{roll}/discontinue', [EmployeeController::class, 'discontinue'])->name('discontinue');
        Route::post('/{roll}/restore', [EmployeeController::class, 'restore'])->name('restore');
    });

    // Quick-create employee from unmapped punch (all authenticated)
    Route::post('/employees/create-from-punch', [EmployeeController::class, 'createFromPunch'])->name('employees.create-from-punch');

    // Data admin (Super Admin)
    Route::prefix('admin/data')->middleware('superadmin')->name('data-admin.')->group(function () {
        Route::post('/reset-students', [DataAdminController::class, 'reset'])->name('reset-students');
        Route::post('/reset-employees', [DataAdminController::class, 'resetEmployees'])->name('reset-employees');
        Route::post('/seed-defaults', [DataAdminController::class, 'seedDefaults'])->name('seed-defaults');
        Route::post('/clear-punch-logs', [DataAdminController::class, 'clearPunchLogs'])->name('clear-punch-logs');
        Route::post('/clear-whatsapp-logs', [DataAdminController::class, 'clearWhatsappLogs'])->name('clear-whatsapp-logs');
    });

    // Quick-create student from unmapped punch (all authenticated)
    Route::post('/students/create-from-punch', [StudentController::class, 'createFromPunch'])->name('students.create-from-punch');

    // Student Profile (must come AFTER courses/batches routes to avoid route conflicts)
    Route::get('/students/{roll}', [AttendanceController::class, 'student'])->name('students.show');
    Route::post('/students/{roll}', [StudentController::class, 'update'])->name('students.update');
    Route::post('/students/{roll}/contact', [StudentController::class, 'updateContact'])->name('students.updateContact');

    // Employee Profile
    Route::get('/employees/{roll}', [AttendanceController::class, 'employee'])->name('employees.show');

    // Manual Attendance (All authenticated users)
    Route::prefix('manual-attendance')->name('manual-attendance.')->group(function () {
        Route::get('/', [ManualAttendanceController::class, 'index'])->name('index');
        Route::post('/mark-present', [ManualAttendanceController::class, 'markPresent'])->name('mark-present');
        Route::post('/mark-out', [ManualAttendanceController::class, 'markOut'])->name('mark-out');
    });

    // Employee Manual Attendance (Super Admin only)
    Route::prefix('manual-attendance/employee')->name('manual-attendance.employee.')->middleware('superadmin')->group(function () {
        Route::get('/', [ManualAttendanceController::class, 'employeeIndex'])->name('index');
        Route::post('/mark-present', [ManualAttendanceController::class, 'markEmployeePresent'])->name('mark-present');
        Route::post('/mark-out', [ManualAttendanceController::class, 'markEmployeeOut'])->name('mark-out');
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

    // Staff permissions (Super Admin)
    Route::get('/permissions', [UserPermissionController::class, 'edit'])->name('permissions.edit');
    Route::post('/permissions', [UserPermissionController::class, 'update'])->name('permissions.update');

    // Profile Management (All authenticated users)
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/change-password', [ProfileController::class, 'showChangePassword'])->name('change-password');
        Route::post('/change-password', [ProfileController::class, 'updatePassword']);
    });
});
