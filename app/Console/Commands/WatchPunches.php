<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\WhatsAppLog;
use App\Services\AisensyService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WatchPunches extends Command
{
    protected $signature = 'punch:watch {--interval=10 : Check interval in seconds}';
    protected $description = 'Continuously watch for new punches and send WhatsApp alerts in real-time';

    private int $exitThresholdMinutes = 2; // min gap to accept next state
    private int $bounceWindowSeconds = 10;  // ignore duplicates within 10s

    public function handle(AisensyService $aisensy): int
    {
        $interval = (int) $this->option('interval');
        $this->info("Starting punch watcher (checking every {$interval} seconds)...");
        $this->info("Press Ctrl+C to stop\n");

        while (true) {
            try {
                $this->processNewPunches($aisensy);
            } catch (\Throwable $e) {
                $this->error("Error: " . $e->getMessage());
            }

            sleep($interval);
        }

        return Command::SUCCESS;
    }

    private function processNewPunches(AisensyService $aisensy): void
    {
        // Get all punches from today that don't have a corresponding whatsapp_log entry
        // We'll check if the punch+state combination exists in whatsapp_logs
        $today = Carbon::today()->format('Y-m-d');
        
        // Get all punches from today
        $punches = DB::table('punch_logs')
            ->where('punch_date', '>=', $today)
            ->orderBy('employee_id')
            ->orderBy('punch_date')
            ->orderBy('punch_time')
            ->get();

        if ($punches->isEmpty()) {
            return;
        }

        // Group by employee and date
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
                    $fullTime = $p->punch_time;
                    if (strlen($fullTime) === 5) {
                        $fullTime .= ':00';
                    }
                    $current = Carbon::parse($p->punch_date . ' ' . $fullTime);

                    if ($lastTime === null) {
                        $acceptedCount = 1;
                        $state = 'IN';
                    } else {
                        $secondsDiff = abs($current->diffInSeconds($lastTime));
                        $minutesDiff = abs($current->diffInMinutes($lastTime));

                        if ($secondsDiff < $this->bounceWindowSeconds) {
                            continue;
                        }

                        if ($minutesDiff < $this->exitThresholdMinutes) {
                            continue;
                        }

                        $acceptedCount++;
                        $state = ($acceptedCount % 2 === 1) ? 'IN' : 'OUT';
                    }

                    // Check if this punch+state combination already has a log entry
                    $exists = WhatsAppLog::where('roll_number', $roll)
                        ->where('punch_date', $p->punch_date)
                        ->where('punch_time', $p->punch_time)
                        ->where('state', $state)
                        ->exists();
                    
                    if ($exists) {
                        $lastTime = $current;
                        continue;
                    }

                    // This is a new punch that needs processing
                    $safeName = $student->name ?: (string) $roll;
                    $messageVars = [
                        (string) $safeName,
                        (string) $roll,
                        (string) $p->punch_time,
                        (string) $p->punch_date,
                    ];

                    $tpl = $state === 'IN'
                        ? \App\Models\Setting::get('aisensy_template_in', config('services.aisensy.template_in'))
                        : \App\Models\Setting::get('aisensy_template_out', config('services.aisensy.template_out'));

                    $resp = $aisensy->send($normalizedPhone, $messageVars, $tpl);

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
                        $this->info("[" . now()->format('H:i:s') . "] Sent {$state} alert to {$normalizedPhone} for {$student->name} ({$roll}) at {$p->punch_time}");
                    } else {
                        $this->warn("[" . now()->format('H:i:s') . "] Failed to send {$state} alert to {$normalizedPhone} for {$student->name} ({$roll}): " . ($resp['error'] ?? 'Unknown error'));
                    }

                    $lastTime = $current;
                }
            }
        }

        if ($sentCount > 0) {
            $this->info("Processed {$sentCount} new punch(es)");
        }
    }

    private function normalizeIndianPhone(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+' . $digits;
        }

        if (strlen($digits) === 13 && str_starts_with($digits, '091')) {
            return '+' . substr($digits, 1);
        }

        return null;
    }
}

