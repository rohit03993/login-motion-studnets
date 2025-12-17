<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ManualAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    private int $exitThresholdMinutes = 2; // flip state after 2 minutes gap
    private int $bounceWindowSeconds = 10; // ignore duplicates within 10 seconds
    
    // Minimum attendance date - only show attendance from this date onwards
    private const MIN_ATTENDANCE_DATE = '2025-12-15';

    public function index(Request $request)
    {
        // Check if punch_logs table exists (created by EasyTimePro parallel DB)
        $tableExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        if (empty($tableExists)) {
            // Table doesn't exist yet - show setup message
            $roll = $request->query('roll');
            $name = $request->query('name');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            
            // Create empty paginator to match expected type
            $emptyPaginator = new LengthAwarePaginator(
                collect([]),
                0,
                50,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            return view('attendance.index', [
                'rows' => $emptyPaginator,
                'groupedRows' => collect([]),
                'studentPairs' => [],
                'filters' => [
                    'roll' => $roll,
                    'name' => $name,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'todayStats' => [
                    'total' => 0,
                    'in' => 0,
                    'out' => 0,
                ],
                'durationTotals' => [
                    'students' => ['hours' => 0, 'minutes' => 0],
                    'employees' => ['hours' => 0, 'minutes' => 0],
                ],
                'setup_required' => true,
            ]);
        }
        
        $roll = $request->query('roll');
        $name = $request->query('name');
        $today = Carbon::today()->format('Y-m-d');
        $date = $request->query('date');
        
        // Single-date filter: default today, clamp to today (no future) and min date
        if (!$date) {
            $date = $today;
        }
        if ($date > $today) {
            $date = $today;
        }
        $minDate = self::MIN_ATTENDANCE_DATE;
        if ($date < $minDate) {
            $date = $minDate;
        }
        
        // Use single date for from/to
        $dateFrom = $date;
        $dateTo = $date;
        
        // Enforce maximum date range of 30 days for performance
        if ($dateFrom && $dateTo) {
            $fromDate = Carbon::parse($dateFrom);
            $toDate = Carbon::parse($dateTo);
            $daysDiff = $fromDate->diffInDays($toDate);
            
            if ($daysDiff > 30) {
                // Limit to last 30 days from the end date
                $dateFrom = $toDate->copy()->subDays(30)->format('Y-m-d');
                // Ensure it doesn't go before minimum date
                if ($dateFrom < $minDate) {
                    $dateFrom = $minDate;
                }
            }
        }

        // Unified punches: machine + manual
        $machine = DB::table('punch_logs')
            ->selectRaw("employee_id, punch_date, punch_time, device_name, area_name, punch_state_char, verify_type_char, 0 as is_manual")
            ->where('punch_date', '>=', $minDate); // Always enforce minimum date

        $manualExists = $this->manualTableExists();
        if ($manualExists) {
            $manual = DB::table('manual_attendances')
                ->selectRaw("roll_number as employee_id, punch_date, punch_time, 'Manual' as device_name, 'Manual' as area_name, CASE WHEN state='OUT' THEN 'O' ELSE 'I' END as punch_state_char, 'M' as verify_type_char, 1 as is_manual")
                ->where('punch_date', '>=', $minDate); // Always enforce minimum date
        }

        // Apply additional date filters if provided
        if ($dateFrom && $dateFrom > $minDate) {
            $machine->where('punch_date', '>=', $dateFrom);
            if ($manualExists) {
                $manual->where('punch_date', '>=', $dateFrom);
            }
        }
        if ($dateTo) {
            $machine->where('punch_date', '<=', $dateTo);
            if ($manualExists) {
                $manual->where('punch_date', '<=', $dateTo);
            }
        }

        $query = DB::query();
        if ($manualExists) {
            $union = $machine->unionAll($manual);
            $query->fromSub($union, 'p');
        } else {
            $query->fromSub($machine, 'p');
        }

        $query
            ->leftJoin('students as s', 's.roll_number', '=', 'p.employee_id')
            ->leftJoin('whatsapp_logs as w', function($join) {
                $join->on('w.roll_number', '=', 'p.employee_id')
                     ->on('w.punch_date', '=', 'p.punch_date')
                     ->on('w.punch_time', '=', 'p.punch_time');
            })
            ->select(
                'p.employee_id',
                'p.punch_date',
                'p.punch_time',
                'p.device_name',
                'p.area_name',
                'p.punch_state_char',
                'p.verify_type_char',
                's.name as student_name',
                's.class_course',
                's.parent_phone',
                'w.status as whatsapp_status',
                'w.error as whatsapp_error',
                'w.sent_at as whatsapp_sent_at',
                'w.state as whatsapp_state'
            )
            ->orderBy('p.punch_date', 'desc')
            ->orderBy('p.punch_time', 'desc');

        // Always enforce minimum date
        $query->where('p.punch_date', '>=', self::MIN_ATTENDANCE_DATE);
        
        if ($roll) {
            $query->where('p.employee_id', $roll);
        }
        if ($name) {
            $query->where(function ($q) use ($name) {
                $q->where('s.name', 'like', "%{$name}%")
                  ->orWhere('p.employee_id', 'like', "%{$name}%");
            });
        }
        if ($dateFrom && $dateFrom > self::MIN_ATTENDANCE_DATE) {
            $query->where('p.punch_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('p.punch_date', '<=', $dateTo);
        }

        // Optimize pagination - limit to reasonable page size
        // Default to 10 per page; allow override up to 100
        $perPage = min((int) $request->query('per_page', 10), 100);
        $rows = $query->paginate($perPage)->appends($request->query());

        // Compute IN/OUT states for each row and match WhatsApp status
        $rows->getCollection()->transform(function ($row) {
            $row->computed_state = $this->computeStateForPunch(
                (string) $row->employee_id, 
                (string) $row->punch_date, 
                (string) $row->punch_time
            );
            
            // Fetch WhatsApp log status for this specific punch and computed state
            $whatsappLog = DB::table('whatsapp_logs')
                ->where('roll_number', $row->employee_id)
                ->where('punch_date', $row->punch_date)
                ->where('punch_time', $row->punch_time)
                ->where('state', $row->computed_state)
                ->first();
            
            if ($whatsappLog) {
                $row->whatsapp_status_display = $whatsappLog->status;
                $row->whatsapp_error = $whatsappLog->error;
            } else {
                $row->whatsapp_status_display = null;
                $row->whatsapp_error = null;
            }
            
            return $row;
        });

        // Group rows by employee_id for accordion display
        $groupedRows = $rows->getCollection()->groupBy('employee_id');
        
        // Get WhatsApp logs for all students in the filtered range
        $whatsappQuery = DB::table('whatsapp_logs')
            ->where('punch_date', '>=', self::MIN_ATTENDANCE_DATE); // Always enforce minimum date
        if ($dateFrom && $dateFrom > self::MIN_ATTENDANCE_DATE) {
            $whatsappQuery->where('punch_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $whatsappQuery->where('punch_date', '<=', $dateTo);
        }
        $allWhatsappLogs = $whatsappQuery->get();
        
        // Create WhatsApp map: key = "roll|date|time|state" => log
        // Use the SAME logic as student profile page (which works correctly)
        $whatsappMap = [];
        foreach ($allWhatsappLogs as $log) {
            // Ensure roll_number is a string (match student page logic)
            $roll = (string) $log->roll_number;
            $normalizedTime = $this->normalizeTime($log->punch_time);
            // Ensure punch_date is a string (match student page logic)
            $punchDate = is_string($log->punch_date) ? $log->punch_date : (string) $log->punch_date;
            
            // Use roll_number|date|time|state format (same as student page but with roll)
            $baseKey = $roll . '|' . $punchDate . '|';
            $state = $log->state;
            
            // Key 1: Normalized H:i:s format
            $key1 = $baseKey . $normalizedTime . '|' . $state;
            $whatsappMap[$key1] = $log;
            
            // Key 2: H:i format (without seconds)
            if (strlen($normalizedTime) === 8) {
                $timeWithoutSeconds = substr($normalizedTime, 0, 5);
                $key2 = $baseKey . $timeWithoutSeconds . '|' . $state;
                $whatsappMap[$key2] = $log;
            }
            
            // Key 3: Original time format
            $key3 = $baseKey . $log->punch_time . '|' . $state;
            $whatsappMap[$key3] = $log;
        }
        
        // Compute IN/OUT pairs for each student (lazy loaded - only when accordion opens)
        // For performance, we'll compute this on-demand via AJAX or compute only for visible students
        // For now, compute only for first 20 students to avoid memory issues
        $studentPairs = [];
        $processedCount = 0;
        $maxStudentsToProcess = 20; // Limit initial processing for performance
        
        foreach ($groupedRows as $rollNumber => $studentPunches) {
            // Only process first N students to avoid memory/timeout issues
            if ($processedCount >= $maxStudentsToProcess) {
                break;
            }
            
            // Convert rollNumber to string to match database (employee_id might be int, but roll_number in whatsapp_logs is string)
            $rollStr = (string) $rollNumber;
            
            // Unified punches (machine + manual)
            $mergedPunches = $this->getUnifiedPunches((string) $rollNumber, $dateFrom, $dateTo);
            
            // Compute IN/OUT pairs using the same logic (NO CACHE - same as student profile page)
            [$daily, $raw] = $this->computeInOut($mergedPunches);
            
            // Attach WhatsApp status to pairs - USE EXACT SAME LOGIC AS STUDENT PROFILE PAGE
            // Query WhatsApp logs for this specific student (EXACT same as student profile page)
            // IMPORTANT: Use string version to match database (whatsapp_logs.roll_number is string)
            $whatsappQuery = DB::table('whatsapp_logs')
                ->where('roll_number', $rollStr)
                ->where('punch_date', '>=', self::MIN_ATTENDANCE_DATE); // Always enforce minimum date
            
            if ($dateFrom && $dateFrom > self::MIN_ATTENDANCE_DATE) {
                $whatsappQuery->where('punch_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $whatsappQuery->where('punch_date', '<=', $dateTo);
            }
            
            $studentWhatsappLogs = $whatsappQuery->get();
            
            // DEBUG: Log WhatsApp logs found
            if ($rollStr == '1' || $rollStr == '7055599920') {
                \Log::info("Dashboard DEBUG: Roll {$rollStr}, Found {$studentWhatsappLogs->count()} WhatsApp logs. Date range: {$dateFrom} to {$dateTo}");
                foreach ($studentWhatsappLogs->take(3) as $log) {
                    \Log::info("Dashboard DEBUG: Log - date: {$log->punch_date}, time: {$log->punch_time}, state: {$log->state}, status: {$log->status}");
                }
            }
            
            // Create WhatsApp map for this student - COPY EXACT CODE FROM STUDENT PROFILE PAGE
            $studentWhatsappMap = [];
            foreach ($studentWhatsappLogs as $log) {
                $normalizedTime = $this->normalizeTime($log->punch_time);
                $baseKey = $log->punch_date . '|';
                $state = $log->state;
                
                // Key 1: Normalized H:i:s format
                $key1 = $baseKey . $normalizedTime . '|' . $state;
                $studentWhatsappMap[$key1] = $log;
                
                // Key 2: H:i format (without seconds)
                if (strlen($normalizedTime) === 8) {
                    $timeWithoutSeconds = substr($normalizedTime, 0, 5);
                    $key2 = $baseKey . $timeWithoutSeconds . '|' . $state;
                    $studentWhatsappMap[$key2] = $log;
                }
                
                // Key 3: Original time format
                $key3 = $baseKey . $log->punch_time . '|' . $state;
                $studentWhatsappMap[$key3] = $log;
            }
            
            // DEBUG: Log map keys for first student
            if (($rollStr == '1' || $rollStr == '7055599920') && !empty($studentWhatsappMap)) {
                $sampleKeys = array_slice(array_keys($studentWhatsappMap), 0, 5);
                \Log::info("Dashboard DEBUG: Sample map keys for roll {$rollStr}: " . implode(', ', $sampleKeys));
            }
            
            // Attach WhatsApp status - COPY EXACT CODE FROM STUDENT PROFILE PAGE
            // CRITICAL FIX: Unset references after loop to prevent data mixing between students
            foreach ($daily as &$dayData) {
                foreach ($dayData['pairs'] as &$pair) {
                    // Check for IN WhatsApp status
                    if ($pair['in']) {
                        $baseKey = $dayData['date'] . '|';
                        $normalizedInTime = $this->normalizeTime($pair['in']);
                        
                        // Try 1: Normalized H:i:s format
                        $inKey1 = $baseKey . $normalizedInTime . '|IN';
                        $pair['whatsapp_in'] = $studentWhatsappMap[$inKey1] ?? null;
                        
                        // Try 2: H:i format (without seconds)
                        if (!$pair['whatsapp_in'] && strlen($normalizedInTime) === 8) {
                            $timeWithoutSeconds = substr($normalizedInTime, 0, 5);
                            $inKey2 = $baseKey . $timeWithoutSeconds . '|IN';
                            $pair['whatsapp_in'] = $studentWhatsappMap[$inKey2] ?? null;
                        }
                        
                        // Try 3: Original time format
                        if (!$pair['whatsapp_in']) {
                            $inKey3 = $baseKey . $pair['in'] . '|IN';
                            $pair['whatsapp_in'] = $studentWhatsappMap[$inKey3] ?? null;
                        }
                        
                        // DEBUG: Log if not found
                        if (!$pair['whatsapp_in'] && ($rollStr == '1' || $rollStr == '7055599920')) {
                            $sampleKeys = array_slice(array_keys($studentWhatsappMap), 0, 5);
                            \Log::info("Dashboard DEBUG: WhatsApp IN NOT FOUND for roll {$rollStr}, date: {$dayData['date']}, time: {$pair['in']}. Keys tried: {$inKey1}, {$inKey2}, {$inKey3}. Sample map keys: " . implode(', ', $sampleKeys));
                        } elseif ($pair['whatsapp_in'] && ($rollStr == '1' || $rollStr == '7055599920')) {
                            \Log::info("Dashboard DEBUG: WhatsApp IN FOUND for roll {$rollStr}, date: {$dayData['date']}, time: {$pair['in']}, status: {$pair['whatsapp_in']->status}");
                        }
                    }
                    
                    // Check for OUT WhatsApp status
                    if ($pair['out']) {
                        $baseKey = $dayData['date'] . '|';
                        $normalizedOutTime = $this->normalizeTime($pair['out']);
                        
                        // Try 1: Normalized H:i:s format
                        $outKey1 = $baseKey . $normalizedOutTime . '|OUT';
                        $pair['whatsapp_out'] = $studentWhatsappMap[$outKey1] ?? null;
                        
                        // Try 2: H:i format (without seconds)
                        if (!$pair['whatsapp_out'] && strlen($normalizedOutTime) === 8) {
                            $timeWithoutSeconds = substr($normalizedOutTime, 0, 5);
                            $outKey2 = $baseKey . $timeWithoutSeconds . '|OUT';
                            $pair['whatsapp_out'] = $studentWhatsappMap[$outKey2] ?? null;
                        }
                        
                        // Try 3: Original time format
                        if (!$pair['whatsapp_out']) {
                            $outKey3 = $baseKey . $pair['out'] . '|OUT';
                            $pair['whatsapp_out'] = $studentWhatsappMap[$outKey3] ?? null;
                        }
                    }
                }
                unset($pair); // CRITICAL: Unset reference to prevent data mixing
            }
            unset($dayData); // CRITICAL: Unset reference to prevent data mixing
            
            $studentPairs[$rollNumber] = $daily;
            $processedCount++;
        }
        
        // Calculate statistics based on actual accepted punches (matching what's displayed)
        // This ensures stats match the table display by using the same logic as pairs computation
        $totalPunches = 0;
        $inCount = 0;
        $outCount = 0;
        $durationByRoll = [];
        
        // Get all unique students in the filtered results
        $allStudentRolls = $groupedRows->keys()->toArray();
        
        // Calculate stats from computed pairs for each student
        foreach ($allStudentRolls as $rollNumber) {
            // Unified punches (machine + manual)
            $mergedPunches = $this->getUnifiedPunches((string) $rollNumber, $dateFrom, $dateTo);
            
            // Compute IN/OUT pairs using the same logic (NO CACHE - real-time data)
            [$daily, $raw] = $this->computeInOut($mergedPunches);
            
            // Count accepted punches from pairs and sum duration for this roll
            $rollSeconds = 0;
            foreach ($daily as $dayData) {
                foreach ($dayData['pairs'] as $pair) {
                    if ($pair['in']) {
                        $inCount++;
                        $totalPunches++;
                    }
                    if ($pair['out']) {
                        $outCount++;
                        $totalPunches++;
                    }

                    if ($pair['in'] && $pair['out']) {
                        $inTime = Carbon::parse($dayData['date'] . ' ' . $pair['in']);
                        $outTime = Carbon::parse($dayData['date'] . ' ' . $pair['out']);
                        $rollSeconds += $inTime->diffInSeconds($outTime);
                    }
                }
            }

            $durationByRoll[$rollNumber] = [
                'total_seconds' => $rollSeconds,
                'hours' => (int) floor($rollSeconds / 3600),
                'minutes' => (int) floor(($rollSeconds % 3600) / 60),
            ];
        }

        // Calculate total duration for students and employees
        $durationTotals = $this->calculateTotalDurations($dateFrom, $dateTo, $roll, $name);

        return view('attendance.index', [
            'rows' => $rows,
            'groupedRows' => $groupedRows,
            'studentPairs' => $studentPairs,
            'filters' => [
                'roll' => $roll,
                'name' => $name,
                'date' => $date,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'todayStats' => [
                'total' => $totalPunches,
                'in' => $inCount,
                'out' => $outCount,
            ],
            'durationTotals' => $durationTotals,
            'durationByRoll' => $durationByRoll,
        ]);
    }

    public function student(Request $request, string $roll)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $courses = \App\Models\Course::with('batches')->orderBy('name')->get();
        
        // Enforce minimum attendance date (2025-12-15)
        $minDate = self::MIN_ATTENDANCE_DATE;
        if ($dateFrom && $dateFrom < $minDate) {
            $dateFrom = $minDate;
        }
        if (!$dateFrom) {
            $dateFrom = $minDate;
        }
        
        // Default to today's date if no end date is provided
        $today = Carbon::today()->format('Y-m-d');
        if (!$dateTo) {
            $dateTo = $today;
        }
        
        // Ensure date range doesn't go before minimum date
        if ($dateTo < $minDate) {
            $dateTo = $minDate;
        }

        $student = Student::where('roll_number', $roll)->first();
        // If no mapping yet, create an in-memory placeholder so the page still renders blanks.
        if (!$student) {
            $student = new Student([
                'roll_number' => $roll,
                'name' => null,
                'father_name' => null,
                'class_course' => null,
                'batch' => null,
                'parent_phone' => null,
                'alerts_enabled' => true,
            ]);
        }

        // Unified punches (machine + manual) for this student
        $mergedPunches = $this->getUnifiedPunches($roll, $dateFrom, $dateTo);

        [$daily, $raw] = $this->computeInOut($mergedPunches);
        
        // Get dates that have attendance records
        $datesWithAttendance = collect($daily)->pluck('date')->toArray();
        
        // Add absent dates (dates that have passed OR today, but have no attendance)
        $today = Carbon::today()->format('Y-m-d');
        $dateFromObj = Carbon::parse($dateFrom);
        $dateToObj = Carbon::parse($dateTo);
        
        // Generate all dates in the range
        $allDatesInRange = [];
        $currentDate = $dateFromObj->copy();
        while ($currentDate->lte($dateToObj)) {
            $dateStr = $currentDate->format('Y-m-d');
            // Mark as absent if date has passed (including today) and has no attendance
            // This means: if date <= today and no attendance record exists, mark as absent
            if ($dateStr <= $today && !in_array($dateStr, $datesWithAttendance)) {
                $allDatesInRange[] = $dateStr;
            }
            $currentDate->addDay();
        }
        
        // Add absent dates to daily array
        foreach ($allDatesInRange as $absentDate) {
            $daily[] = [
                'date' => $absentDate,
                'pairs' => [],
                'is_absent' => true, // Flag to indicate this is an absent date
            ];
        }
        
        // Calculate total duration for this student
        $totalDurationSeconds = 0;
        foreach ($daily as $dayData) {
            if (isset($dayData['is_absent']) && $dayData['is_absent']) {
                continue; // Skip absent dates in duration calculation
            }
            foreach ($dayData['pairs'] as $pair) {
                if ($pair['in'] && $pair['out']) {
                    $inTime = Carbon::parse($dayData['date'] . ' ' . $pair['in']);
                    $outTime = Carbon::parse($dayData['date'] . ' ' . $pair['out']);
                    $duration = $inTime->diffInSeconds($outTime);
                    $totalDurationSeconds += $duration;
                }
            }
        }
        $totalDurationHours = floor($totalDurationSeconds / 3600);
        $totalDurationMinutes = floor(($totalDurationSeconds % 3600) / 60);

        // Create a map of notes by date+time for quick lookup
        $notesMap = [];
        foreach ($raw as $rawItem) {
            $key = $rawItem['date'] . '|' . $rawItem['time'];
            $notesMap[$key] = $rawItem['note'] ?? null;
        }

        // Convert raw punches to proper format for display, including notes
        $rawPunches = $mergedPunches->map(function ($p) use ($notesMap) {
            $key = $p->punch_date . '|' . $p->punch_time;
            return (object) [
                'punch_date' => $p->punch_date,
                'punch_time' => $p->punch_time,
                'note' => $notesMap[$key] ?? null,
            ];
        });

        // Get WhatsApp logs for this student and create a lookup map
        $whatsappQuery = DB::table('whatsapp_logs')
            ->where('roll_number', $roll)
            ->where('punch_date', '>=', self::MIN_ATTENDANCE_DATE); // Always enforce minimum date

        if ($dateFrom && $dateFrom > self::MIN_ATTENDANCE_DATE) {
            $whatsappQuery->where('punch_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $whatsappQuery->where('punch_date', '<=', $dateTo);
        }

        $whatsappLogs = $whatsappQuery->get();
        
        // Create a map for quick lookup: key = "date|time|state" => whatsapp log
        // Store multiple time format variations to ensure matching
        $whatsappMap = [];
        foreach ($whatsappLogs as $log) {
            $normalizedTime = $this->normalizeTime($log->punch_time);
            $baseKey = $log->punch_date . '|';
            $state = $log->state;
            
            // Key 1: Normalized H:i:s format
            $key1 = $baseKey . $normalizedTime . '|' . $state;
            $whatsappMap[$key1] = $log;
            
            // Key 2: H:i format (without seconds)
            if (strlen($normalizedTime) === 8) {
                $timeWithoutSeconds = substr($normalizedTime, 0, 5);
                $key2 = $baseKey . $timeWithoutSeconds . '|' . $state;
                $whatsappMap[$key2] = $log;
            }
            
            // Key 3: Original time format
            $key3 = $baseKey . $log->punch_time . '|' . $state;
            $whatsappMap[$key3] = $log;
        }

        // Attach WhatsApp status to daily pairs
        foreach ($daily as &$dayData) {
            foreach ($dayData['pairs'] as &$pair) {
                // Check for IN WhatsApp status
                if ($pair['in']) {
                    $baseKey = $dayData['date'] . '|';
                    $normalizedInTime = $this->normalizeTime($pair['in']);
                    
                    // Try 1: Normalized H:i:s format
                    $inKey1 = $baseKey . $normalizedInTime . '|IN';
                    $pair['whatsapp_in'] = $whatsappMap[$inKey1] ?? null;
                    
                    // Try 2: H:i format (without seconds)
                    if (!$pair['whatsapp_in'] && strlen($normalizedInTime) === 8) {
                        $timeWithoutSeconds = substr($normalizedInTime, 0, 5);
                        $inKey2 = $baseKey . $timeWithoutSeconds . '|IN';
                        $pair['whatsapp_in'] = $whatsappMap[$inKey2] ?? null;
                    }
                    
                    // Try 3: Original time format
                    if (!$pair['whatsapp_in']) {
                        $inKey3 = $baseKey . $pair['in'] . '|IN';
                        $pair['whatsapp_in'] = $whatsappMap[$inKey3] ?? null;
                    }
                }
                
                // Check for OUT WhatsApp status
                if ($pair['out']) {
                    $baseKey = $dayData['date'] . '|';
                    $normalizedOutTime = $this->normalizeTime($pair['out']);
                    
                    // Try 1: Normalized H:i:s format
                    $outKey1 = $baseKey . $normalizedOutTime . '|OUT';
                    $pair['whatsapp_out'] = $whatsappMap[$outKey1] ?? null;
                    
                    // Try 2: H:i format (without seconds)
                    if (!$pair['whatsapp_out'] && strlen($normalizedOutTime) === 8) {
                        $timeWithoutSeconds = substr($normalizedOutTime, 0, 5);
                        $outKey2 = $baseKey . $timeWithoutSeconds . '|OUT';
                        $pair['whatsapp_out'] = $whatsappMap[$outKey2] ?? null;
                    }
                    
                    // Try 3: Original time format
                    if (!$pair['whatsapp_out']) {
                        $outKey3 = $baseKey . $pair['out'] . '|OUT';
                        $pair['whatsapp_out'] = $whatsappMap[$outKey3] ?? null;
                    }
                }
            }
        }

        return view('attendance.student', [
            'student' => $student,
            'roll' => $roll,
            'daily' => $daily,
            'raw' => $rawPunches,
            'whatsappLogs' => $whatsappLogs,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'totalDuration' => [
                'hours' => $totalDurationHours,
                'minutes' => $totalDurationMinutes,
            ],
            'courses' => $courses,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $roll = $request->query('roll');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        // Enforce minimum attendance date (2025-12-15)
        $minDate = self::MIN_ATTENDANCE_DATE;
        if ($dateFrom && $dateFrom < $minDate) {
            $dateFrom = $minDate;
        }
        if (!$dateFrom) {
            $dateFrom = $minDate;
        }
        
        // Default to today's date if no end date is provided
        $today = Carbon::today()->format('Y-m-d');
        if (!$dateTo) {
            $dateTo = $today;
        }
        
        // Ensure date range doesn't go before minimum date
        if ($dateTo < $minDate) {
            $dateTo = $minDate;
        }

        $query = DB::table('punch_logs as p')
            ->leftJoin('students as s', 's.roll_number', '=', 'p.employee_id')
            ->select(
                'p.employee_id',
                's.name as student_name',
                's.class_course',
                'p.punch_date',
                'p.punch_time',
                'p.device_name',
                'p.area_name',
                'p.punch_state_char',
                'p.verify_type_char'
            )
            ->where('p.punch_date', '>=', $minDate) // Always enforce minimum date
            ->orderBy('p.punch_date', 'desc')
            ->orderBy('p.punch_time', 'desc');

        if ($roll) {
            $query->where('p.employee_id', $roll);
        }
        if ($dateFrom && $dateFrom > $minDate) {
            $query->where('p.punch_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('p.punch_date', '<=', $dateTo);
        }

        $rows = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="punches.csv"',
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['roll_number', 'student_name', 'class_course', 'punch_date', 'punch_time', 'device_name', 'area_name', 'punch_state_char', 'verify_type_char']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->employee_id,
                    $r->student_name,
                    $r->class_course,
                    $r->punch_date,
                    $r->punch_time,
                    $r->device_name,
                    $r->area_name,
                    $r->punch_state_char,
                    $r->verify_type_char,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Merge automatic punches with manual attendance marks
     * 
     * @param \Illuminate\Support\Collection $automaticPunches From punch_logs
     * @param string $rollNumber Student roll number
     * @param string|null $dateFrom Optional date range start
     * @param string|null $dateTo Optional date range end
     * @return \Illuminate\Support\Collection Merged punches in unified format
     */
    private function mergePunchesWithManual($automaticPunches, string $rollNumber, ?string $dateFrom = null, ?string $dateTo = null): \Illuminate\Support\Collection
    {
        // Check if manual_attendances table exists before querying
        if (!$this->manualTableExists()) {
            // If table doesn't exist, just return automatic punches formatted
            return $automaticPunches->map(function($punch) {
                return (object) [
                    'punch_date' => $punch->punch_date,
                    'punch_time' => $punch->punch_time,
                    'is_manual' => false,
                    'state' => null,
                ];
            });
        }
        
        // Get manual attendance marks
        $manualQuery = ManualAttendance::where('roll_number', $rollNumber);
        
        if ($dateFrom) {
            $manualQuery->where('punch_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $manualQuery->where('punch_date', '<=', $dateTo);
        }
        
        $manualMarks = $manualQuery->orderBy('punch_date')
            ->orderBy('punch_time')
            ->get();
        
        // Convert manual marks to same format as automatic punches
        $manualPunches = $manualMarks->map(function($mark) {
            return (object) [
                'punch_date' => $mark->punch_date->format('Y-m-d'),
                'punch_time' => $mark->punch_time,
                'is_manual' => true,
                'state' => $mark->state,
            ];
        });
        
        // Convert automatic punches to include is_manual flag
        $automaticPunchesFormatted = $automaticPunches->map(function($punch) {
            return (object) [
                'punch_date' => $punch->punch_date,
                'punch_time' => $punch->punch_time,
                'is_manual' => false,
                'state' => null, // Will be computed
            ];
        });
        
        // Merge both collections
        $merged = $automaticPunchesFormatted->concat($manualPunches);
        
        // Sort by date, then time
        return $merged->sortBy(function($punch) {
            return $punch->punch_date . ' ' . $punch->punch_time;
        })->values();
    }

    /**
     * Unified punches (machine + manual) for a student within an optional date range
     */
    private function getUnifiedPunches(string $rollNumber, ?string $dateFrom = null, ?string $dateTo = null): \Illuminate\Support\Collection
    {
        $minDate = self::MIN_ATTENDANCE_DATE;
        
        // Enforce minimum date
        if ($dateFrom && $dateFrom < $minDate) {
            $dateFrom = $minDate;
        }
        if (!$dateFrom) {
            $dateFrom = $minDate;
        }
        
        $machine = DB::table('punch_logs')
            ->selectRaw("employee_id, punch_date, punch_time, 0 as is_manual, null as state")
            ->where('employee_id', $rollNumber)
            ->where('punch_date', '>=', $minDate); // Always enforce minimum date

        $manualExists = $this->manualTableExists();
        if ($manualExists) {
            $manual = DB::table('manual_attendances')
                ->selectRaw("roll_number as employee_id, punch_date, punch_time, 1 as is_manual, state")
                ->where('roll_number', $rollNumber)
                ->where('punch_date', '>=', $minDate); // Always enforce minimum date
        }

        if ($dateFrom && $dateFrom > $minDate) {
            $machine->where('punch_date', '>=', $dateFrom);
            if ($manualExists) {
                $manual->where('punch_date', '>=', $dateFrom);
            }
        }
        if ($dateTo) {
            $machine->where('punch_date', '<=', $dateTo);
            if ($manualExists) {
                $manual->where('punch_date', '<=', $dateTo);
            }
        }

        if ($manualExists) {
            $union = $machine->unionAll($manual);
            return DB::query()
                ->fromSub($union, 'u')
                ->orderBy('punch_date')
                ->orderBy('punch_time')
                ->orderByDesc('is_manual') // prefer manual when times match
                ->get();
        }

        return $machine
            ->orderBy('punch_date')
            ->orderBy('punch_time')
            ->orderByDesc('is_manual') // prefer manual when times match
            ->get();
    }

    /**
     * Check if manual_attendances table exists
     */
    private function manualTableExists(): bool
    {
        $res = DB::select("SHOW TABLES LIKE 'manual_attendances'");
        return !empty($res);
    }

    /**
     * Compute in/out pairs per day with thresholds.
     * Uses the same logic as computeStateForPunch but groups into IN/OUT pairs.
     * Now supports both automatic and manual punches.
     *
     * @param \Illuminate\Support\Collection $punches sorted asc by date/time (can include manual marks)
     * @return array [$daily, $raw]
     */
    private function computeInOut($punches): array
    {
        $daily = [];
        $raw = [];

        // Group by date
        $byDate = [];
        foreach ($punches as $p) {
            $byDate[$p->punch_date][] = $p;
        }

        foreach ($byDate as $date => $list) {
                // sort by time ascending; if same time, prioritize manual punches
                usort($list, function ($a, $b) {
                    $cmp = strcmp($a->punch_time, $b->punch_time);
                    if ($cmp === 0) {
                        return ($a->is_manual ?? false) === ($b->is_manual ?? false)
                            ? 0
                            : (($a->is_manual ?? false) ? -1 : 1);
                    }
                    return $cmp;
                });

            $acceptedCount = 0;
            $lastAcceptedTime = null;
            $entries = [];
            $currentPairIndex = -1;

            foreach ($list as $p) {
                // Normalize time for parsing
                $fullTime = $p->punch_time;
                if (strlen($fullTime) === 5) {
                    $fullTime .= ':00';
                }
                $current = Carbon::parse($date . ' ' . $fullTime);

                // First punch is always IN
                if ($lastAcceptedTime === null) {
                    $acceptedCount = 1;
                    $entries[] = [
                        'in' => $p->punch_time,
                        'out' => null,
                        'is_manual_in' => (bool) ($p->is_manual ?? false),
                        'is_manual_out' => null,
                    ];
                    $currentPairIndex = count($entries) - 1;
                    $lastAcceptedTime = $current;
                    continue;
                }

                // Calculate time differences (ensure positive values)
                $secondsDiff = abs($current->diffInSeconds($lastAcceptedTime));
                $minutesDiff = abs($current->diffInMinutes($lastAcceptedTime));

                // Bounce: within 10 seconds - ignore
                if ($secondsDiff < $this->bounceWindowSeconds) {
                    $raw[] = ['date' => $date, 'time' => $p->punch_time, 'note' => 'duplicate-skipped'];
                    continue;
                }

                // Gap too small: less than 10 minutes - ignore
                if ($minutesDiff < $this->exitThresholdMinutes) {
                    $raw[] = ['date' => $date, 'time' => $p->punch_time, 'note' => 'ignored-gap-too-small'];
                    continue;
                }

                // Valid punch - increment counter and determine state
                $acceptedCount++;
                $state = ($acceptedCount % 2 === 1) ? 'IN' : 'OUT';

                if ($state === 'IN') {
                    // New IN punch - start a new pair
                    $entries[] = [
                        'in' => $p->punch_time,
                        'out' => null,
                        'is_manual_in' => (bool) ($p->is_manual ?? false),
                        'is_manual_out' => null,
                    ];
                    $currentPairIndex = count($entries) - 1;
                } else {
                    // OUT punch - close the current pair
                    if ($currentPairIndex >= 0 && isset($entries[$currentPairIndex])) {
                        $entries[$currentPairIndex]['out'] = $p->punch_time;
                        $entries[$currentPairIndex]['is_manual_out'] = (bool) ($p->is_manual ?? false);
                    }
                }

                $lastAcceptedTime = $current;
            }

            // Auto-add OUT at 7 PM for incomplete pairs (has IN but no OUT)
            $today = Carbon::today()->format('Y-m-d');
            $currentDate = Carbon::parse($date);
            $isPastDate = $currentDate->format('Y-m-d') < $today;
            $isToday = $currentDate->format('Y-m-d') === $today;
            $isPast7PM = Carbon::now()->hour >= 19; // 7 PM = 19:00

            foreach ($entries as &$entry) {
                if ($entry['in'] && !$entry['out']) {
                    // Auto-add OUT at 7 PM if:
                    // 1. Date has passed (past date), OR
                    // 2. Today and it's past 7 PM
                    if ($isPastDate || ($isToday && $isPast7PM)) {
                        $entry['out'] = '19:00:00';
                        $entry['is_manual_out'] = $entry['is_manual_out'] ?? false;
                        $entry['is_auto_out'] = true; // Flag to indicate auto-generated OUT
                    }
                }
            }
            unset($entry); // Unset reference

            $daily[] = [
                'date' => $date,
                'pairs' => $entries,
            ];
        }

        return [$daily, $raw];
    }

    /**
     * Compute IN/OUT state for a single punch by analyzing all punches for that student on that date.
     * This uses the same logic as computeInOut but returns the state for a specific punch.
     *
     * Logic:
     * - First punch of the day = IN (always)
     * - Second punch (if >= 10 min after first) = OUT
     * - Third punch (if >= 10 min after second) = IN
     * - And so on, alternating
     * - Punches within 10 seconds = ignored (bounce)
     * - Punches within 10 minutes (but > 10 seconds) = ignored (gap too small)
     *
     * @param string $roll
     * @param string $punchDate
     * @param string $punchTime
     * @return string 'IN' or 'OUT'
     */
    private function computeStateForPunch(string $roll, string $punchDate, string $punchTime): string
    {
        // Unified punches (machine + manual) for the date
        $punches = $this->getUnifiedPunches($roll, $punchDate, $punchDate);

        if ($punches->isEmpty()) {
            return 'IN';
        }

        // Normalize target time - handle both H:i:s and H:i formats
        $targetTimeNormalized = trim($punchTime);
        // Remove seconds if present for comparison (we'll compare at minute level)
        if (strlen($targetTimeNormalized) === 8) { // H:i:s format
            $targetTimeNormalized = substr($targetTimeNormalized, 0, 5); // Get H:i part
        }
        
        $acceptedCount = 0;
        $lastAcceptedTime = null;

        foreach ($punches as $p) {
            // Normalize current punch time
            $currentTimeStr = trim($p->punch_time);
            // Remove seconds if present
            if (strlen($currentTimeStr) === 8) { // H:i:s format
                $currentTimeStr = substr($currentTimeStr, 0, 5); // Get H:i part
            }
            
            // Parse full datetime for calculations
            $fullCurrentTime = $p->punch_time;
            if (strlen($fullCurrentTime) === 5) {
                $fullCurrentTime .= ':00';
            }
            $current = Carbon::parse($punchDate . ' ' . $fullCurrentTime);
            
            // Compare normalized times (H:i format)
            $isTarget = ($currentTimeStr === $targetTimeNormalized);

            // First punch is always IN
            if ($lastAcceptedTime === null) {
                $acceptedCount = 1;
                if ($isTarget) {
                    return 'IN';
                }
                $lastAcceptedTime = $current;
                continue;
            }

            // Calculate time differences (ensure positive values)
            // $current should always be >= $lastAcceptedTime since we're iterating in ascending order
            $secondsDiff = abs($current->diffInSeconds($lastAcceptedTime));
            $minutesDiff = abs($current->diffInMinutes($lastAcceptedTime));

            // Bounce: within 10 seconds - ignore
            if ($secondsDiff < $this->bounceWindowSeconds) {
                if ($isTarget) {
                    // Return last state for bounces
                    return ($acceptedCount % 2 === 1) ? 'IN' : 'OUT';
                }
                continue;
            }

            // Gap too small: less than 10 minutes - ignore
            if ($minutesDiff < $this->exitThresholdMinutes) {
                if ($isTarget) {
                    // Return last state for small gaps
                    return ($acceptedCount % 2 === 1) ? 'IN' : 'OUT';
                }
                continue;
            }

            // Valid punch - increment counter and determine state
            $acceptedCount++;
            $state = ($acceptedCount % 2 === 1) ? 'IN' : 'OUT';

            if ($isTarget) {
                return $state;
            }

            $lastAcceptedTime = $current;
        }

        // Should not reach here, but default to IN
        return 'IN';
    }

    /**
     * Normalize time format for consistent matching
     * Converts "H:i:s" or "H:i" to "H:i:s" format for comparison
     */
    private function normalizeTime($time): string
    {
        if (empty($time)) {
            return '';
        }
        
        // Remove any whitespace
        $time = trim((string) $time);
        
        // If it's already in H:i:s format, return as is
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        
        // If it's in H:i format, add :00 for seconds
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $parts = explode(':', $time);
            return sprintf('%02d:%02d:00', (int) $parts[0], (int) $parts[1]);
        }
        
        // Try to parse and format
        try {
            $carbon = Carbon::parse($time);
            return $carbon->format('H:i:s');
        } catch (\Exception $e) {
            // If parsing fails, return original
            return $time;
        }
    }

    /**
     * Check for new punches since last check (for auto-refresh)
     * Lightweight endpoint that only checks if new data exists
     */
    public function checkUpdates(Request $request)
    {
        $lastCheck = $request->query('last_check'); // timestamp
        $dateFrom = $request->query('date_from', Carbon::today()->format('Y-m-d'));
        $dateTo = $request->query('date_to', Carbon::today()->format('Y-m-d'));
        
        // Enforce minimum attendance date
        $minDate = self::MIN_ATTENDANCE_DATE;
        if ($dateFrom < $minDate) {
            $dateFrom = $minDate;
        }
        if ($dateTo < $minDate) {
            $dateTo = $minDate;
        }
        
        // Get total count for the date range (current state)
        $currentTotal = DB::table('punch_logs')
            ->where('punch_date', '>=', max($dateFrom, $minDate))
            ->where('punch_date', '<=', $dateTo)
            ->count();
        
        // Get the latest punch timestamp in the date range
        $latestPunch = DB::table('punch_logs')
            ->where('punch_date', '>=', max($dateFrom, $minDate))
            ->where('punch_date', '<=', $dateTo)
            ->selectRaw('UNIX_TIMESTAMP(CONCAT(punch_date, " ", punch_time)) as punch_timestamp, CONCAT(punch_date, " ", punch_time) as punch_datetime')
            ->orderBy('punch_date', 'desc')
            ->orderBy('punch_time', 'desc')
            ->first();
        
        $latestTimestamp = $latestPunch ? $latestPunch->punch_timestamp : time();
        
        // Check if there are new punches since last check
        $newPunchesCount = 0;
        $hasUpdates = false;
        
        if ($lastCheck && is_numeric($lastCheck)) {
            // Count new punches since last check using timestamp comparison
            $lastCheckTime = Carbon::createFromTimestamp($lastCheck);
            
            // Use a more reliable comparison - check if latest timestamp is newer
            if ($latestTimestamp > $lastCheck) {
                // Count records newer than last check
                $newPunchesCount = DB::table('punch_logs')
                    ->where('punch_date', '>=', max($dateFrom, self::MIN_ATTENDANCE_DATE))
                    ->where('punch_date', '<=', $dateTo)
                    ->where(DB::raw('UNIX_TIMESTAMP(CONCAT(punch_date, " ", punch_time))'), '>', $lastCheck)
                    ->count();
                
                $hasUpdates = $newPunchesCount > 0;
            }
        } else {
            // First check - just return current timestamp, no updates
            $hasUpdates = false;
        }
        
        return response()->json([
            'has_updates' => $hasUpdates,
            'new_punches_count' => $newPunchesCount,
            'latest_timestamp' => $latestTimestamp,
            'current_total' => $currentTotal,
            'timestamp' => time(),
        ]);
    }

    /**
     * Calculate total duration for students and employees separately
     * Returns total time in seconds for each category
     */
    private function calculateTotalDurations(?string $dateFrom, ?string $dateTo, ?string $roll = null, ?string $name = null): array
    {
        $minDate = self::MIN_ATTENDANCE_DATE;
        
        // Enforce minimum date
        if ($dateFrom && $dateFrom < $minDate) {
            $dateFrom = $minDate;
        }
        if (!$dateFrom) {
            $dateFrom = $minDate;
        }
        
        // Get all unique employee IDs in the filtered range
        $machineIds = DB::table('punch_logs')
            ->selectRaw('DISTINCT CAST(employee_id AS CHAR) as roll_number')
            ->where('punch_date', '>=', $minDate);
        
        if ($dateFrom && $dateFrom > $minDate) {
            $machineIds->where('punch_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $machineIds->where('punch_date', '<=', $dateTo);
        }
        
        $manualIds = null;
        if ($this->manualTableExists()) {
            $manualIds = DB::table('manual_attendances')
                ->selectRaw('DISTINCT CAST(roll_number AS CHAR) as roll_number')
                ->where('punch_date', '>=', $minDate);
            
            if ($dateFrom && $dateFrom > $minDate) {
                $manualIds->where('punch_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $manualIds->where('punch_date', '<=', $dateTo);
            }
        }
        
        $idsQuery = $manualIds ? $machineIds->union($manualIds) : $machineIds;
        $allRolls = collect(DB::select($idsQuery->toSql(), $idsQuery->getBindings()))
            ->pluck('roll_number')
            ->unique()
            ->toArray();
        
        // Apply filters if provided
        if ($roll) {
            $allRolls = array_filter($allRolls, function($r) use ($roll) {
                return strpos((string)$r, (string)$roll) !== false;
            });
        }
        
        // Get student roll numbers (those with class_course)
        $studentRolls = DB::table('students')
            ->whereNotNull('class_course')
            ->where('class_course', '!=', '')
            ->pluck('roll_number')
            ->map(function($r) { return (string)$r; })
            ->toArray();
        
        // Separate students and employees
        $students = [];
        $employees = [];
        
        foreach ($allRolls as $rollNumber) {
            $rollStr = (string) $rollNumber;
            if (in_array($rollStr, $studentRolls)) {
                $students[] = $rollStr;
            } else {
                $employees[] = $rollStr;
            }
        }
        
        // Apply name filter if provided
        if ($name) {
            $filteredStudents = DB::table('students')
                ->where('name', 'like', "%{$name}%")
                ->whereIn('roll_number', $students)
                ->pluck('roll_number')
                ->map(function($r) { return (string)$r; })
                ->toArray();
            $students = array_intersect($students, $filteredStudents);
            
            // For employees, check if name matches roll number
            $employees = array_filter($employees, function($r) use ($name) {
                return strpos($r, $name) !== false;
            });
        }
        
        // Calculate total duration for students
        $studentTotalSeconds = 0;
        foreach ($students as $rollNumber) {
            $mergedPunches = $this->getUnifiedPunches($rollNumber, $dateFrom, $dateTo);
            [$daily, $raw] = $this->computeInOut($mergedPunches);
            
            foreach ($daily as $dayData) {
                foreach ($dayData['pairs'] as $pair) {
                    if ($pair['in'] && $pair['out']) {
                        $inTime = Carbon::parse($dayData['date'] . ' ' . $pair['in']);
                        $outTime = Carbon::parse($dayData['date'] . ' ' . $pair['out']);
                        $duration = $inTime->diffInSeconds($outTime);
                        $studentTotalSeconds += $duration;
                    }
                }
            }
        }
        
        // Calculate total duration for employees
        $employeeTotalSeconds = 0;
        foreach ($employees as $rollNumber) {
            $mergedPunches = $this->getUnifiedPunches($rollNumber, $dateFrom, $dateTo);
            [$daily, $raw] = $this->computeInOut($mergedPunches);
            
            foreach ($daily as $dayData) {
                foreach ($dayData['pairs'] as $pair) {
                    if ($pair['in'] && $pair['out']) {
                        $inTime = Carbon::parse($dayData['date'] . ' ' . $pair['in']);
                        $outTime = Carbon::parse($dayData['date'] . ' ' . $pair['out']);
                        $duration = $inTime->diffInSeconds($outTime);
                        $employeeTotalSeconds += $duration;
                    }
                }
            }
        }
        
        return [
            'students' => [
                'total_seconds' => $studentTotalSeconds,
                'hours' => floor($studentTotalSeconds / 3600),
                'minutes' => floor(($studentTotalSeconds % 3600) / 60),
            ],
            'employees' => [
                'total_seconds' => $employeeTotalSeconds,
                'hours' => floor($employeeTotalSeconds / 3600),
                'minutes' => floor(($employeeTotalSeconds % 3600) / 60),
            ],
        ];
    }

}

