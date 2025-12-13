<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentController;

Route::get('/', [AttendanceController::class, 'index']);
Route::get('/attendance', [AttendanceController::class, 'index']);
Route::get('/attendance/export', [AttendanceController::class, 'export']);
Route::get('/attendance/check-updates', [AttendanceController::class, 'checkUpdates'])->name('attendance.check-updates');
Route::get('/students/{roll}', [AttendanceController::class, 'student'])->name('students.show');
Route::post('/students/{roll}', [StudentController::class, 'update'])->name('students.update');
Route::post('/students/{roll}/contact', [StudentController::class, 'updateContact'])->name('students.updateContact');

Route::get('/settings', [SettingsController::class, 'edit']);
Route::post('/settings', [SettingsController::class, 'update']);
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
