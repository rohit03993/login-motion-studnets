<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\WhatsAppLog;
use App\Services\AisensyService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessNotificationQueue extends Command
{
    protected $signature = 'queue:process-notifications {--continuous : Run continuously} {--interval=1 : Check interval in seconds when continuous}';
    protected $description = 'Process notification queue and send WhatsApp messages immediately';

    private int $exitThresholdMinutes = 2; // min gap to accept next state
    private int $bounceWindowSeconds = 10;  // ignore duplicates within 10s

    public function handle(AisensyService $aisensy): int
    {
        $continuous = $this->option('continuous');
        $interval = (int) $this->option('interval');

        if ($continuous) {
            $this->info("Starting continuous notification queue processor (checking every {$interval} second(s))...");
            $this->info("Press Ctrl+C to stop\n");

            while (true) {
                $this->processQueue($aisensy);
                sleep($interval);
            }
        } else {
            return $this->processQueue($aisensy);
        }

        return Command::SUCCESS;
    }

    private function processQueue(AisensyService $aisensy): int
    {
        // Get unprocessed queue items
        $queueItems = DB::table('notification_queue')
            ->where('processed', false)
            ->orderBy('queued_at', 'asc')
            ->limit(50) // Process in batches
            ->get();

        if ($queueItems->isEmpty()) {
            return Command::SUCCESS;
        }

        $this->info("Processing " . $queueItems->count() . " queued punch(es)...");

        $sentCount = 0;

        foreach ($queueItems as $item) {
            try {
                // Mark as processing
                DB::table('notification_queue')
                    ->where('id', $item->id)
                    ->update(['processed' => true, 'processed_at' => now()]);

                // Get student info
                $student = Student::where('roll_number', $item->roll_number)->first();
                $normalizedPhone = $this->normalizeIndianPhone($student->parent_phone ?? null);
                
                if (!$student || !$student->alerts_enabled || empty($normalizedPhone)) {
                    continue;
                }

                // Get all punches for this student on this date to compute state
                $punches = DB::table('punch_logs')
                    ->where('employee_id', $item->roll_number)
                    ->where('punch_date', $item->punch_date)
                    ->orderBy('punch_time', 'asc')
                    ->get();

                if ($punches->isEmpty()) {
                    continue;
                }

                // Compute IN/OUT state using the same logic
                $state = $this->computeStateForPunch($punches, $item->punch_time, $item->punch_date);

                // Check if already sent
                $exists = WhatsAppLog::where('roll_number', $item->roll_number)
                    ->where('punch_date', $item->punch_date)
                    ->where('punch_time', $item->punch_time)
                    ->where('state', $state)
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Send WhatsApp message
                $safeName = $student->name ?: (string) $item->roll_number;
                $messageVars = [
                    (string) $safeName,
                    (string) $item->roll_number,
                    (string) $item->punch_time,
                    (string) $item->punch_date,
                ];

                $tpl = $state === 'IN'
                    ? \App\Models\Setting::get('aisensy_template_in', config('services.aisensy.template_in'))
                    : \App\Models\Setting::get('aisensy_template_out', config('services.aisensy.template_out'));

                $resp = $aisensy->send($normalizedPhone, $messageVars, $tpl);

                WhatsAppLog::create([
                    'student_id' => $student->roll_number,
                    'roll_number' => $item->roll_number,
                    'state' => $state,
                    'punch_date' => $item->punch_date,
                    'punch_time' => $item->punch_time,
                    'sent_at' => now(),
                    'status' => $resp['status'] ?? null,
                    'error' => $resp['error'] ?? null,
                ]);

                if (($resp['status'] ?? null) === 'success') {
                    $sentCount++;
                    $this->info("[" . now()->format('H:i:s') . "] Sent {$state} alert to {$normalizedPhone} for {$student->name} ({$item->roll_number}) at {$item->punch_time}");
                } else {
                    $this->warn("[" . now()->format('H:i:s') . "] Failed to send {$state} alert: " . ($resp['error'] ?? 'Unknown error'));
                }
            } catch (\Throwable $e) {
                $this->error("Error processing queue item {$item->id}: " . $e->getMessage());
            }
        }

        if ($sentCount > 0) {
            $this->info("Successfully sent {$sentCount} message(s)");
        }

        return Command::SUCCESS;
    }

    private function computeStateForPunch($punches, string $targetTime, string $punchDate): string
    {
        $acceptedCount = 0;
        $lastAcceptedTime = null;

        foreach ($punches as $p) {
            $fullTime = $p->punch_time;
            if (strlen($fullTime) === 5) {
                $fullTime .= ':00';
            }
            $current = Carbon::parse($punchDate . ' ' . $fullTime);

            $isTarget = ($p->punch_time === $targetTime);

            if ($lastAcceptedTime === null) {
                $acceptedCount = 1;
                if ($isTarget) {
                    return 'IN';
                }
                $lastAcceptedTime = $current;
                continue;
            }

            $secondsDiff = abs($current->diffInSeconds($lastAcceptedTime));
            $minutesDiff = abs($current->diffInMinutes($lastAcceptedTime));

            if ($secondsDiff < $this->bounceWindowSeconds) {
                if ($isTarget) {
                    return ($acceptedCount % 2 === 1) ? 'IN' : 'OUT';
                }
                continue;
            }

            if ($minutesDiff < $this->exitThresholdMinutes) {
                if ($isTarget) {
                    return ($acceptedCount % 2 === 1) ? 'IN' : 'OUT';
                }
                continue;
            }

            $acceptedCount++;
            $state = ($acceptedCount % 2 === 1) ? 'IN' : 'OUT';

            if ($isTarget) {
                return $state;
            }

            $lastAcceptedTime = $current;
        }

        return 'IN';
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

