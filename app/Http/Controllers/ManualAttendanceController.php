<?php

namespace App\Http\Controllers;

use App\Models\ManualAttendance;
use App\Models\Student;
use App\Models\WhatsAppLog;
use App\Services\AisensyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualAttendanceController extends Controller
{
    // Minimum attendance date - only show attendance from this date onwards
    private const MIN_ATTENDANCE_DATE = '2025-12-15';
    
    /**
     * Show manual attendance marking page
     * Displays present/absent students for selected batch and date
     */
    public function index(Request $request)
    {
        $batch = $request->query('batch');
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        
        // Enforce minimum attendance date
        if ($date < self::MIN_ATTENDANCE_DATE) {
            $date = self::MIN_ATTENDANCE_DATE;
        }
        
        // Get all unique batches for filter dropdown
        $batches = Student::whereNotNull('batch')
            ->where('batch', '!=', '')
            ->distinct()
            ->orderBy('batch')
            ->pluck('batch')
            ->toArray();
        
        // Check if there are students with no batch
        $hasNoBatch = Student::where(function($q) {
            $q->whereNull('batch')->orWhere('batch', '');
        })->exists();
        
        $presentStudents = collect([]);
        $absentStudents = collect([]);
        
        if ($batch && $date) {
            // Get all students in the batch
            $query = Student::query();
            
            if ($batch === '__no_batch__') {
                $query->where(function($q) {
                    $q->whereNull('batch')->orWhere('batch', '');
                });
            } else {
                $query->where('batch', $batch);
            }
            
            $allStudents = $query->orderBy('roll_number')->get();
            
            // For each student, check if they have an IN mark (automatic or manual) for this date
            foreach ($allStudents as $student) {
                $hasInMark = $this->hasInMark($student->roll_number, $date);
                
                if ($hasInMark) {
                    // Get IN time (from automatic or manual)
                    $inTime = $this->getInTime($student->roll_number, $date);
                    $isManual = $this->isManualIn($student->roll_number, $date);
                    $hasOut = $this->hasOutMark($student->roll_number, $date);
                    $outTime = $hasOut ? $this->getOutTime($student->roll_number, $date) : null;
                    
                    // Get WhatsApp status for IN
                    $whatsappIn = $this->getWhatsAppStatus($student->roll_number, $date, $inTime, 'IN');
                    $whatsappOut = $outTime ? $this->getWhatsAppStatus($student->roll_number, $date, $outTime, 'OUT') : null;
                    
                    $presentStudents->push([
                        'student' => $student,
                        'in_time' => $inTime,
                        'out_time' => $outTime,
                        'is_manual' => $isManual,
                        'has_out' => $hasOut,
                        'whatsapp_in' => $whatsappIn,
                        'whatsapp_out' => $whatsappOut,
                    ]);
                } else {
                    $absentStudents->push([
                        'student' => $student,
                    ]);
                }
            }
        }
        
        return view('manual-attendance.index', compact(
            'batch',
            'date',
            'batches',
            'hasNoBatch',
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
        
        // Get student for WhatsApp
        $student = Student::where('roll_number', $rollNumber)->first();
        
        // Send WhatsApp if student has phone and alerts enabled
        $whatsappSent = false;
        $whatsappError = null;
        
        if (!$student) {
            $whatsappError = 'Student not found';
        } elseif (!$student->alerts_enabled) {
            $whatsappError = 'Alerts disabled for this student';
        } elseif (empty($student->parent_phone)) {
            $whatsappError = 'No parent phone number found';
        } else {
            $normalizedPhone = $this->normalizeIndianPhone($student->parent_phone);
            if (!$normalizedPhone) {
                $whatsappError = 'Invalid phone number format: ' . $student->parent_phone;
            } else {
                $safeName = $student->name ?: (string) $rollNumber;
                $messageVars = [
                    (string) $safeName,
                    (string) $rollNumber,
                    (string) $time,
                    (string) $date,
                ];
                
                $tpl = \App\Models\Setting::get('aisensy_template_in', config('services.aisensy.template_in'));
                
                \Log::info('Sending WhatsApp for manual attendance', [
                    'roll_number' => $rollNumber,
                    'phone' => $normalizedPhone,
                    'template' => $tpl,
                    'message_vars' => $messageVars,
                ]);
                
                $resp = $aisensy->send($normalizedPhone, $messageVars, $tpl);
                
                \Log::info('WhatsApp response for manual attendance', [
                    'roll_number' => $rollNumber,
                    'response' => $resp,
                ]);
                
                // Log WhatsApp
                WhatsAppLog::create([
                    'student_id' => $student->roll_number,
                    'roll_number' => $rollNumber,
                    'state' => 'IN',
                    'punch_date' => $date,
                    'punch_time' => $time,
                    'sent_at' => now(),
                    'status' => $resp['status'] ?? null,
                    'error' => $resp['error'] ?? null,
                ]);
                
                $whatsappSent = ($resp['status'] ?? null) === 'success';
                if (!$whatsappSent) {
                    $whatsappError = $resp['error'] ?? 'Unknown error';
                }
            }
        }
        
        $message = 'Student marked as present successfully.';
        if ($whatsappSent) {
            $message .= ' WhatsApp sent successfully.';
        } elseif ($whatsappError) {
            $message .= ' WhatsApp not sent: ' . $whatsappError;
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'whatsapp_sent' => $whatsappSent,
            'whatsapp_error' => $whatsappError,
        ]);
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

        // Send WhatsApp for manual OUT
        $whatsappSent = false;
        $whatsappError = null;

        $student = Student::where('roll_number', $rollNumber)->first();
        if (!$student) {
            $whatsappError = 'Student not found';
        } elseif (!$student->alerts_enabled) {
            $whatsappError = 'Alerts disabled for this student';
        } elseif (empty($student->parent_phone)) {
            $whatsappError = 'No parent phone number found';
        } else {
            $normalizedPhone = $this->normalizeIndianPhone($student->parent_phone);
            if (!$normalizedPhone) {
                $whatsappError = 'Invalid phone number format: ' . $student->parent_phone;
            } else {
                $safeName = $student->name ?: (string) $rollNumber;
                $messageVars = [
                    (string) $safeName,
                    (string) $rollNumber,
                    (string) $time,
                    (string) $date,
                ];

                $tpl = \App\Models\Setting::get('aisensy_template_out', config('services.aisensy.template_out'));

                \Log::info('Sending WhatsApp OUT for manual attendance', [
                    'roll_number' => $rollNumber,
                    'phone' => $normalizedPhone,
                    'template' => $tpl,
                    'message_vars' => $messageVars,
                ]);

                $resp = $aisensy->send($normalizedPhone, $messageVars, $tpl);

                \Log::info('WhatsApp OUT response for manual attendance', [
                    'roll_number' => $rollNumber,
                    'response' => $resp,
                ]);

                WhatsAppLog::create([
                    'student_id' => $student->roll_number,
                    'roll_number' => $rollNumber,
                    'state' => 'OUT',
                    'punch_date' => $date,
                    'punch_time' => $time,
                    'sent_at' => now(),
                    'status' => $resp['status'] ?? null,
                    'error' => $resp['error'] ?? null,
                ]);

                $whatsappSent = ($resp['status'] ?? null) === 'success';
                if (!$whatsappSent) {
                    $whatsappError = $resp['error'] ?? 'Unknown error';
                }
            }
        }

        $message = 'Student marked as OUT successfully.';
        if ($whatsappSent) {
            $message .= ' WhatsApp sent successfully.';
        } elseif ($whatsappError) {
            $message .= ' WhatsApp not sent: ' . $whatsappError;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'whatsapp_sent' => $whatsappSent,
            'whatsapp_error' => $whatsappError,
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

        DB::table('punch_logs')->insert([
            'employee_id' => $rollNumber,
            'punch_date' => $date,
            'punch_time' => $time,
            'device_name' => 'Manual',
            'area_name' => 'Manual',
            'punch_state_char' => $punchStateChar,
            'verify_type_char' => 'M', // Manual
        ]);
    }
}
