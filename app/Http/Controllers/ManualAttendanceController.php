<?php

namespace App\Http\Controllers;

use App\Models\ManualAttendance;
use App\Models\Course;
use App\Models\Student;
use App\Models\WhatsAppLog;
use App\Services\AisensyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManualAttendanceController extends Controller
{
    // Minimum attendance date - only show attendance from this date onwards
    private const MIN_ATTENDANCE_DATE = '2025-12-15';
    private ?bool $punchLogsHasIsManual = null;
    
    /**
     * Show manual attendance marking page
     * Displays present/absent students for selected batch and date
     */
    public function index(Request $request)
    {
        $classCourse = $request->query('class', 'ALL');
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $rollFilter = $request->query('roll');
        $nameFilter = $request->query('name');
        $user = auth()->user();
        $isSuper = $user->isSuperAdmin();
        
        // Enforce minimum attendance date
        if ($date < self::MIN_ATTENDANCE_DATE) {
            $date = self::MIN_ATTENDANCE_DATE;
        }
        
        // Get class options from courses and any existing student data (union)
        $courseClasses = Course::orderBy('name')->pluck('name')->toArray();
        $studentClasses = Student::whereNotNull('class_course')
            ->where('class_course', '!=', '')
            ->whereNull('discontinued_at') // Exclude discontinued
            ->distinct()
            ->orderBy('class_course')
            ->pluck('class_course')
            ->toArray();
        $classes = collect($courseClasses)
            ->merge($studentClasses)
            ->unique()
            ->values()
            ->toArray();
        if (empty($classes)) {
            $classes = ['Default Program'];
        }
        array_unshift($classes, 'ALL');
        if (!$isSuper) {
            $allowed = \App\Models\UserClassPermission::where('user_id', $user->id)
                ->pluck('class_name')->unique()->values()->toArray();
            $classes = array_values(array_intersect($classes, array_merge(['ALL'], $allowed)));
        }
        if (!is_array($classes)) {
            $classes = [$classes];
        }
        
        // Check if there are students with no class (excluding discontinued)
        $hasNoClass = Student::where(function($q) {
            $q->whereNull('class_course')->orWhere('class_course', '');
        })
        ->whereNull('discontinued_at') // Exclude discontinued
        ->exists();
        
        $presentStudents = collect([]);
        $absentStudents = collect([]);
        
        if ($classCourse && $date) {
            // Step 1: Get active (non-discontinued) students
            $query = Student::query(); // Soft deletes automatically exclude deleted records
            
            if ($classCourse === 'ALL') {
                // no filter
            } elseif ($classCourse === '__no_class__') {
                $query->where(function($q) {
                    $q->whereNull('class_course')->orWhere('class_course', '');
                });
            } else {
                $query->where('class_course', $classCourse);
            }
            if (!$isSuper) {
                $query->whereIn('class_course', $allowed ?? []);
            }
            
            // Exclude discontinued students completely from manual attendance
            // Only active (non-discontinued) students should appear
            $query->whereNull('discontinued_at');
            
            // Apply roll and name filters
            if ($rollFilter) {
                $query->where('roll_number', 'like', "%{$rollFilter}%");
            }
            if ($nameFilter) {
                $query->where(function($q) use ($nameFilter) {
                    $q->where('name', 'like', "%{$nameFilter}%")
                      ->orWhere('father_name', 'like', "%{$nameFilter}%");
                });
            }
            
            $allStudents = $query->orderBy('roll_number')->get();
            
            // Process only active students (discontinued are completely excluded)
            foreach ($allStudents as $student) {
                $hasInMark = $this->hasInMark($student->roll_number, $date);
                
                if ($hasInMark) {
                    // Get IN time (from automatic or manual)
                    $inTime = $this->getInTime($student->roll_number, $date);
                    $isManual = $this->isManualIn($student->roll_number, $date);
                    $hasOut = $this->hasOutMark($student->roll_number, $date);
                    $outTime = $hasOut ? $this->getOutTime($student->roll_number, $date) : null;
                    $isManualOut = $hasOut ? $this->isManualOut($student->roll_number, $date) : false;
                    
                    // Get WhatsApp status for IN
                    $whatsappIn = $this->getWhatsAppStatus($student->roll_number, $date, $inTime, 'IN');
                    $whatsappOut = $outTime ? $this->getWhatsAppStatus($student->roll_number, $date, $outTime, 'OUT') : null;
                    
                    $presentStudents->push([
                        'student' => $student,
                        'in_time' => $inTime,
                        'out_time' => $outTime,
                        'is_manual' => $isManual,
                        'is_manual_out' => $isManualOut,
                        'has_out' => $hasOut,
                        'whatsapp_in' => $whatsappIn,
                        'whatsapp_out' => $whatsappOut,
                    ]);
                } else {
                    // Show in absent list
                    $absentStudents->push([
                        'student' => $student,
                    ]);
                }
            }
        }
        
        return view('manual-attendance.index', compact(
            'classCourse',
            'date',
            'classes',
            'hasNoClass',
            'presentStudents',
            'absentStudents'
        ));
    }
    
    /**
     * Mark student as present (IN) manually
     */
    public function markPresent(Request $request, AisensyService $aisensy)
    {
        $request->validate([
            'roll_number' => 'required|string',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ]);
        
        $rollNumber = $request->input('roll_number');
        $date = $request->input('date');
        // Get time from input (HH:MM format) and convert to HH:MM:SS
        $timeInput = $request->input('time');
        $time = $timeInput . ':00'; // Add seconds to match database format

        // Prevent manual attendance marking for discontinued students
        // Existing attendance records remain intact, but new marks are blocked
        $student = Student::withTrashed()->where('roll_number', $rollNumber)->first();
        if ($student && ($student->trashed() || $student->discontinued_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot mark attendance for discontinued student. Historical records are preserved.',
            ], 403);
        }

        // Ensure OUT time is not before IN time
        $firstIn = $this->getInTime($rollNumber, $date);
        if ($firstIn) {
            $inTs = Carbon::parse($date . ' ' . $firstIn);
            $outTs = Carbon::parse($date . ' ' . $time);
            if ($outTs->lt($inTs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'OUT time cannot be earlier than IN time (' . $firstIn . ').',
                ], 400);
            }
        }
        
        // Check if student already has IN mark for this date
        if ($this->hasInMark($rollNumber, $date)) {
            return response()->json([
                'success' => false,
                'message' => 'Student already marked as present for this date.',
            ], 400);
        }

        // Insert into punch_logs so live attendance shows manual IN
        $this->insertManualPunch($rollNumber, $date, $time, 'IN');
        
        // Create manual IN mark
        $manualAttendance = ManualAttendance::create([
            'roll_number' => $rollNumber,
            'punch_date' => $date,
            'punch_time' => $time,
            'state' => 'IN',
            'marked_by' => auth()->id(),
            'is_manual' => true,
            'notes' => 'Manually marked present',
        ]);
        
        // Get student for WhatsApp (already fetched above for discontinued check)
        
        // Send WhatsApp if student has phone and alerts enabled
        $whatsappResults = $this->sendWhatsAppToStudent($student, $rollNumber, $date, $time, 'IN', $aisensy);
        
        $message = 'Student marked as present successfully.';
        if ($whatsappResults['sent_count'] > 0) {
            $message .= ' WhatsApp sent to ' . $whatsappResults['sent_count'] . ' number(s).';
        } elseif ($whatsappResults['error']) {
            $message .= ' WhatsApp not sent: ' . $whatsappResults['error'];
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'whatsapp_sent' => $whatsappResults['sent_count'] > 0,
            'whatsapp_error' => $whatsappResults['error'],
        ]);
    }
    
    /**
     * Send WhatsApp to student based on whatsapp_send_to setting
     * Returns array with 'sent_count', 'error', and 'results'
     */
    private function sendWhatsAppToStudent($student, string $rollNumber, string $date, string $time, string $state, AisensyService $aisensy): array
    {
        if (!$student) {
            return ['sent_count' => 0, 'error' => 'Student not found', 'results' => []];
        }

        if (!$student->alerts_enabled) {
            return ['sent_count' => 0, 'error' => 'Alerts disabled for this student', 'results' => []];
        }

        // Get phone numbers based on whatsapp_send_to setting
        $phones = $student->getWhatsAppPhones();
        
        if (empty($phones)) {
            return ['sent_count' => 0, 'error' => 'No phone number found', 'results' => []];
        }

        $safeName = $student->name ?: (string) $rollNumber;
        $messageVars = [
            (string) $safeName,
            (string) $rollNumber,
            (string) $time,
            (string) $date,
        ];

        // Use manual-specific templates for manual attendance
        $tpl = $state === 'IN'
            ? \App\Models\Setting::get('aisensy_template_manual_in', config('services.aisensy.template_manual_in'))
            : \App\Models\Setting::get('aisensy_template_manual_out', config('services.aisensy.template_manual_out'));

        $sentCount = 0;
        $results = [];
        $errors = [];

        foreach ($phones as $phone) {
            \Log::info('Sending WhatsApp for manual attendance', [
                'roll_number' => $rollNumber,
                'phone' => $phone,
                'state' => $state,
                'template' => $tpl,
                'message_vars' => $messageVars,
            ]);

            $resp = $aisensy->send($phone, $messageVars, $tpl);

            \Log::info('WhatsApp response for manual attendance', [
                'roll_number' => $rollNumber,
                'phone' => $phone,
                'state' => $state,
                'response' => $resp,
            ]);

            // Log WhatsApp for each phone
            WhatsAppLog::create([
                'student_id' => $student->roll_number,
                'roll_number' => $rollNumber,
                'state' => $state,
                'punch_date' => $date,
                'punch_time' => $time,
                'sent_at' => now(),
                'status' => $resp['status'] ?? null,
                'error' => $resp['error'] ?? null,
            ]);

            $isSuccess = ($resp['status'] ?? null) === 'success';
            if ($isSuccess) {
                $sentCount++;
            } else {
                $errors[] = $phone . ': ' . ($resp['error'] ?? 'Unknown error');
            }

            $results[] = [
                'phone' => $phone,
                'success' => $isSuccess,
                'error' => $resp['error'] ?? null,
            ];
        }

        $errorMessage = !empty($errors) ? implode('; ', $errors) : null;

        return [
            'sent_count' => $sentCount,
            'error' => $errorMessage,
            'results' => $results,
        ];
    }
    
    /**
     * Mark student as OUT manually
     */
    public function markOut(Request $request, AisensyService $aisensy)
    {
        $request->validate([
            'roll_number' => 'required|string',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ]);
        
        $rollNumber = $request->input('roll_number');
        $date = $request->input('date');
        // Get time from input (HH:MM format) and convert to HH:MM:SS
        $timeInput = $request->input('time');
        $time = $timeInput . ':00'; // Add seconds to match database format
        
        // Prevent manual attendance marking for discontinued students
        // Existing attendance records remain intact, but new marks are blocked
        $student = Student::withTrashed()->where('roll_number', $rollNumber)->first();
        if ($student && ($student->trashed() || $student->discontinued_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot mark attendance for discontinued student. Historical records are preserved.',
            ], 403);
        }
        
        // Check if student has IN mark for this date
        if (!$this->hasInMark($rollNumber, $date)) {
            return response()->json([
                'success' => false,
                'message' => 'Student must be marked as present (IN) first.',
            ], 400);
        }
        
        // Check if already has OUT mark
        if ($this->hasOutMark($rollNumber, $date)) {
            return response()->json([
                'success' => false,
                'message' => 'Student already marked as OUT for this date.',
            ], 400);
        }
        
        // Create manual OUT mark
        ManualAttendance::create([
            'roll_number' => $rollNumber,
            'punch_date' => $date,
            'punch_time' => $time,
            'state' => 'OUT',
            'marked_by' => auth()->id(),
            'is_manual' => true,
            'notes' => 'Manually marked out',
        ]);

        // Insert into punch_logs so live attendance shows manual OUT
        $this->insertManualPunch($rollNumber, $date, $time, 'OUT');

        // Send WhatsApp for manual OUT (student already fetched above for discontinued check)
        $whatsappResults = $this->sendWhatsAppToStudent($student, $rollNumber, $date, $time, 'OUT', $aisensy);

        $message = 'Student marked as OUT successfully.';
        if ($whatsappResults['sent_count'] > 0) {
            $message .= ' WhatsApp sent to ' . $whatsappResults['sent_count'] . ' number(s).';
        } elseif ($whatsappResults['error']) {
            $message .= ' WhatsApp not sent: ' . $whatsappResults['error'];
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'whatsapp_sent' => $whatsappResults['sent_count'] > 0,
            'whatsapp_error' => $whatsappResults['error'],
        ]);
    }
    
    /**
     * Check if student has IN mark (automatic or manual) for a date
     */
    private function hasInMark(string $rollNumber, string $date): bool
    {
        // Check manual attendance
        $hasManualIn = ManualAttendance::where('roll_number', $rollNumber)
            ->where('punch_date', $date)
            ->where('state', 'IN')
            ->exists();
        
        if ($hasManualIn) {
            return true;
        }
        
        // Check automatic punches - need to compute if first punch is IN
        $punchLogsExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        if (empty($punchLogsExists)) {
            return false;
        }
        
        $punches = DB::table('punch_logs')
            ->where('employee_id', $rollNumber)
            ->where('punch_date', $date)
            ->orderBy('punch_time', 'asc')
            ->get(['punch_time']);
        
        // If has any punches, first one is always IN
        return $punches->isNotEmpty();
    }
    
    /**
     * Get IN time (from automatic or manual)
     */
    private function getInTime(string $rollNumber, string $date): ?string
    {
        // Check manual attendance first
        $manualIn = ManualAttendance::where('roll_number', $rollNumber)
            ->where('punch_date', $date)
            ->where('state', 'IN')
            ->orderBy('punch_time', 'asc')
            ->first();
        
        if ($manualIn) {
            return $manualIn->punch_time;
        }
        
        // Check automatic punches
        $punchLogsExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        if (empty($punchLogsExists)) {
            return null;
        }
        
        $firstPunch = DB::table('punch_logs')
            ->where('employee_id', $rollNumber)
            ->where('punch_date', $date)
            ->orderBy('punch_time', 'asc')
            ->first(['punch_time']);
        
        return $firstPunch ? $firstPunch->punch_time : null;
    }
    
    /**
     * Check if IN mark is manual
     */
    private function isManualIn(string $rollNumber, string $date): bool
    {
        return ManualAttendance::where('roll_number', $rollNumber)
            ->where('punch_date', $date)
            ->where('state', 'IN')
            ->exists();
    }

    /**
     * Check if OUT mark is manual
     */
    private function isManualOut(string $rollNumber, string $date): bool
    {
        return ManualAttendance::where('roll_number', $rollNumber)
            ->where('punch_date', $date)
            ->where('state', 'OUT')
            ->exists();
    }
    
    /**
     * Get OUT time (from automatic or manual)
     */
    private function getOutTime(string $rollNumber, string $date): ?string
    {
        // Check manual attendance first
        $manualOut = ManualAttendance::where('roll_number', $rollNumber)
            ->where('punch_date', $date)
            ->where('state', 'OUT')
            ->orderBy('punch_time', 'asc')
            ->first();
        
        if ($manualOut) {
            return $manualOut->punch_time;
        }
        
        // Check automatic punches - need to compute pairs to find OUT
        $punchLogsExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        if (empty($punchLogsExists)) {
            return null;
        }
        
        // Get all punches and compute pairs
        $punches = DB::table('punch_logs')
            ->where('employee_id', $rollNumber)
            ->where('punch_date', $date)
            ->orderBy('punch_time', 'asc')
            ->get(['punch_time']);
        
        if ($punches->count() < 2) {
            return null;
        }
        
        // Simple: return second punch if exists (simplified - full logic in computeInOut)
        return $punches[1]->punch_time ?? null;
    }
    
    /**
     * Get WhatsApp status for a specific punch
     */
    private function getWhatsAppStatus(string $rollNumber, string $date, string $time, string $state): ?array
    {
        $log = WhatsAppLog::where('roll_number', $rollNumber)
            ->where('punch_date', $date)
            ->where('punch_time', $time)
            ->where('state', $state)
            ->first();
        
        if (!$log) {
            return null;
        }
        
        return [
            'status' => $log->status,
            'error' => $log->error,
            'sent_at' => $log->sent_at,
        ];
    }
    
    /**
     * Check if student has OUT mark (automatic or manual) for a date
     */
    private function hasOutMark(string $rollNumber, string $date): bool
    {
        // Check manual attendance
        $hasManualOut = ManualAttendance::where('roll_number', $rollNumber)
            ->where('punch_date', $date)
            ->where('state', 'OUT')
            ->exists();
        
        if ($hasManualOut) {
            return true;
        }
        
        // Check automatic punches - need to compute if there's a valid OUT
        $punchLogsExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        if (empty($punchLogsExists)) {
            return false;
        }
        
        // Get all punches and compute pairs to see if there's an OUT
        $punches = DB::table('punch_logs')
            ->where('employee_id', $rollNumber)
            ->where('punch_date', $date)
            ->orderBy('punch_time', 'asc')
            ->get(['punch_time']);
        
        if ($punches->count() < 2) {
            return false;
        }
        
        // Simple check: if has 2+ punches with proper gap, likely has OUT
        // Full computation would be done in computeInOut method
        return true; // Simplified - full logic in computeInOut
    }
    
    /**
     * Normalize Indian phone number to +91XXXXXXXXXX format
     * Matches AisensyService normalization logic exactly
     */
    private function normalizeIndianPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }
        
        // Remove all non-digit characters
        $digits = preg_replace('/\D+/', '', $phone);
        
        // 10-digit local number
        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }
        
        // 12-digit with leading 91
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+' . $digits;
        }
        
        // Already in +91 format (12 digits after stripping)
        if (str_starts_with($phone, '+91') && strlen($digits) === 12) {
            return $phone; // Already correct format
        }
        
        return null;
    }

    /**
     * Insert a manual punch into punch_logs so live attendance stays in sync.
     */
    private function insertManualPunch(string $rollNumber, string $date, string $time, string $state): void
    {
        $punchLogsExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        if (empty($punchLogsExists)) {
            return;
        }

        // Avoid duplicate insert
        $exists = DB::table('punch_logs')
            ->where('employee_id', $rollNumber)
            ->where('punch_date', $date)
            ->where('punch_time', $time)
            ->exists();

        if ($exists) {
            return;
        }

        $punchStateChar = $state === 'OUT' ? 'O' : 'I';

        $payload = [
            'employee_id' => $rollNumber,
            'punch_date' => $date,
            'punch_time' => $time,
            'device_name' => 'Manual',
            'area_name' => 'Manual',
            'punch_state_char' => $punchStateChar,
            'verify_type_char' => 'M', // Manual
        ];

        // Only include is_manual if the column exists (some external DBs may not have it)
        if ($this->punchLogsHasIsManual()) {
            $payload['is_manual'] = 1;
        }

        DB::table('punch_logs')->insert($payload);
    }

    /**
     * Check and cache whether punch_logs has an is_manual column.
     */
    private function punchLogsHasIsManual(): bool
    {
        if ($this->punchLogsHasIsManual === null) {
            $this->punchLogsHasIsManual = Schema::hasColumn('punch_logs', 'is_manual');
        }
        return $this->punchLogsHasIsManual;
    }
}
