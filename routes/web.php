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

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
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
Route::get('/db-test', function () {
    try {
        \DB::connection()->getPdo();
        return 'DB OK';
    } catch (\Throwable $e) {
        return 'DB FAIL: ' . $e->getMessage();
    }
});

Route::get('/test-inout', function () {
    $roll = '1';
    $date = '2025-12-12';
    
    // Get all punches for this roll on this date
    $punches = DB::table('punch_logs')
        ->where('employee_id', $roll)
        ->where('punch_date', $date)
        ->orderBy('punch_time', 'asc')
        ->get(['punch_time']);
    
    $result = [];
    $acceptedCount = 0;
    $lastAcceptedTime = null;
    $exitThresholdMinutes = 10;
    $bounceWindowSeconds = 10;
    
    foreach ($punches as $p) {
        $currentTimeStr = trim($p->punch_time);
        $fullCurrentTime = $p->punch_time;
        if (strlen($fullCurrentTime) === 5) {
            $fullCurrentTime .= ':00';
        }
        
        $current = \Carbon\Carbon::parse($date . ' ' . $fullCurrentTime);
        
        if ($lastAcceptedTime === null) {
            $acceptedCount = 1;
            $state = 'IN';
            $result[] = [
                'time' => $p->punch_time,
                'state' => $state,
                'accepted_count' => $acceptedCount,
                'note' => 'First punch'
            ];
            $lastAcceptedTime = $current;
            continue;
        }
        
        $secondsDiff = abs($current->diffInSeconds($lastAcceptedTime));
        $minutesDiff = abs($current->diffInMinutes($lastAcceptedTime));
        
        if ($secondsDiff < $bounceWindowSeconds) {
            $result[] = [
                'time' => $p->punch_time,
                'state' => 'IGNORED',
                'accepted_count' => $acceptedCount,
                'note' => "Bounce: {$secondsDiff}s gap"
            ];
            continue;
        }
        
        if ($minutesDiff < $exitThresholdMinutes) {
            $result[] = [
                'time' => $p->punch_time,
                'state' => 'IGNORED',
                'accepted_count' => $acceptedCount,
                'note' => "Gap too small: {$minutesDiff} min"
            ];
            continue;
        }
        
        $acceptedCount++;
        $state = ($acceptedCount % 2 === 1) ? 'IN' : 'OUT';
        $result[] = [
            'time' => $p->punch_time,
            'state' => $state,
            'accepted_count' => $acceptedCount,
            'note' => "Gap: {$minutesDiff} min"
        ];
        $lastAcceptedTime = $current;
    }
    
    return response()->json([
        'roll' => $roll,
        'date' => $date,
        'punches' => $result
    ], 200, [], JSON_PRETTY_PRINT);
});
