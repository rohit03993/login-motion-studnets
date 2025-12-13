<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\WhatsAppLog;
use App\Services\AisensyService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendPunchWhatsapp extends Command
{
    protected $signature = 'punch:notify {--days=1 : How many days back to scan punches} {--minutes=5 : Process punches from last N minutes for real-time alerts}';
    protected $description = 'Send WhatsApp alerts for new punches (IN/OUT) based on even/odd logic with thresholds';

    private int $exitThresholdMinutes = 2; // min gap to accept next state
    private int $bounceWindowSeconds = 10;  // ignore duplicates within 10s

    public function handle(AisensyService $aisensy): int
    {
        $days = (int) $this->option('days');
        $minutes = (int) $this->option('minutes');
        
        // Default to 5 minutes if not specified (for scheduler)
        if ($minutes === 0 && $days === 1) {
            $minutes = 5; // Real-time mode default
        }
        
        // For real-time processing, use last N minutes; otherwise use days
        if ($minutes > 0) {
            $cutoffDateTime = Carbon::now()->subMinutes($minutes);
            $cutoffDate = $cutoffDateTime->format('Y-m-d');
            $cutoffTime = $cutoffDateTime->format('H:i:s');
            $this->info("Processing punches from last {$minutes} minutes (since {$cutoffDate} {$cutoffTime})");
        } else {
            $cutoffDate = Carbon::today()->subDays($days)->format('Y-m-d');
            $cutoffTime = '00:00:00';
            $this->info("Processing punches from last {$days} days (since {$cutoffDate})");
        }

        // Pull recent punches (for real-time, only last N minutes)
        $query = DB::table('punch_logs')
            ->where('punch_date', '>=', $cutoffDate);
            
        // If processing by minutes (real-time mode), filter by time as well
        if ($minutes > 0) {
            $query->where(function($q) use ($cutoffDate, $cutoffTime) {
                // Punches from today after cutoff time
                $q->where('punch_date', $cutoffDate)
                  ->where('punch_time', '>=', $cutoffTime)
                  // Or punches from future dates (shouldn't happen, but safe)
                  ->orWhere('punch_date', '>', $cutoffDate);
            });
        }
        
        $punches = $query->orderBy('employee_id')
            ->orderBy('punch_date')
            ->orderBy('punch_time')
            ->get();
            
        $this->info("Found " . $punches->count() . " punches to process");

        $grouped = [];
        foreach ($punches as $p) {
            $grouped[$p->employee_id][$p->punch_date][] = $p;
        }

        $sentCount = 0;

        foreach ($grouped as $roll => $dates) {
            $student = Student::where('roll_number', $roll)->first();
            $normalizedPhone = $this->normalizeIndianPhone($student->parent_phone ?? null);
            if (!$student || !$student->alerts_enabled || empty($normalizedPhone)) {
                continue;
            }

            foreach ($dates as $date => $list) {
                usort($list, function ($a, $b) {
                    return strcmp($a->punch_time, $b->punch_time);
                });

                $acceptedCount = 0;
                $lastTime = null;

                foreach ($list as $p) {
                    // Normalize time for parsing
                    $fullTime = $p->punch_time;
                    if (strlen($fullTime) === 5) {
                        $fullTime .= ':00';
                    }
                    $current = Carbon::parse($p->punch_date . ' ' . $fullTime);

                    // First punch is always IN
                    if ($lastTime === null) {
                        $acceptedCount = 1;
                        $state = 'IN';
                    } else {
                        // Calculate time differences (ensure positive values)
                        $secondsDiff = abs($current->diffInSeconds($lastTime));
                        $minutesDiff = abs($current->diffInMinutes($lastTime));

                        // Bounce filter: within 10 seconds - ignore
                        if ($secondsDiff < $this->bounceWindowSeconds) {
                            continue;
                        }

                        // Gap filter: less than 10 minutes - ignore
                        if ($minutesDiff < $this->exitThresholdMinutes) {
                            continue;
                        }

                        // Valid punch - increment counter and determine state
                        $acceptedCount++;
                        $state = ($acceptedCount % 2 === 1) ? 'IN' : 'OUT';
                    }

                    // Avoid duplicates already sent
                    $exists = WhatsAppLog::where('roll_number', $roll)
                        ->where('punch_date', $p->punch_date)
                        ->where('punch_time', $p->punch_time)
                        ->where('state', $state)
                        ->exists();
                    if ($exists) {
                        $lastTime = $current;
                        continue;
                    }

                    // Prepare template parameters
                    // Format expected by Aisensy templates:
                    // [Student Name, Roll Number, Punch Time, Punch Date]
                    // Template params must be strings and non-null: [Name, Roll, Time, Date]
                    $safeName = $student->name ?: (string) $roll;
                    $messageVars = [
                        (string) $safeName,
                        (string) $roll,
                        (string) $p->punch_time,
                        (string) $p->punch_date,
                    ];

                    // Choose template based on state
                    $tpl = $state === 'IN'
                        ? \App\Models\Setting::get('aisensy_template_in', config('services.aisensy.template_in'))
                        : \App\Models\Setting::get('aisensy_template_out', config('services.aisensy.template_out'));

                    // Send WhatsApp message
                    $resp = $aisensy->send($normalizedPhone, $messageVars, $tpl);

                    // Log the attempt
                    // Note: student_id is a foreign key to students.roll_number (string)
                    WhatsAppLog::create([
                        'student_id' => $student->roll_number,
                        'roll_number' => $roll,
                        'state' => $state,
                        'punch_date' => $p->punch_date,
                        'punch_time' => $p->punch_time,
                        'sent_at' => now(),
                        'status' => $resp['status'] ?? null,
                        'error' => $resp['error'] ?? null,
                    ]);

                    if (($resp['status'] ?? null) === 'success') {
                        $sentCount++;
                        $this->info("Sent {$state} alert to {$normalizedPhone} for {$student->name} ({$roll}) at {$p->punch_time}");
                    } else {
                        $this->warn("Failed to send {$state} alert to {$normalizedPhone} for {$student->name} ({$roll}): " . ($resp['error'] ?? 'Unknown error'));
                    }

                    $lastTime = $current;
                }
            }
        }

        $this->info("WhatsApp alerts sent: {$sentCount}");
        return Command::SUCCESS;
    }

    /**
     * Normalize Indian mobile numbers to +91XXXXXXXXXX.
     * Accepts common inputs: 10-digit, 12-digit starting with 91, or +91 formats.
     * Returns null if it cannot be normalized.
     */
    private function normalizeIndianPhone(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        // 10-digit local number
        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }

        // 12-digit with leading 91
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+' . $digits;
        }

        // 13-digit with leading +91 (captured as 0 + 12 digits after stripping non-digits)
        if (strlen($digits) === 13 && str_starts_with($digits, '091')) {
            return '+' . substr($digits, 1);
        }

        return null;
    }
}

