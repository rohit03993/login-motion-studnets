<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\WhatsAppLog;
use App\Services\AisensyService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendPunchWhatsappForceIn extends Command
{
    protected $signature = 'punch:notify-force-in {--date= : Punch date in YYYY-MM-DD (default: today)}';
    protected $description = 'Force-send IN WhatsApp alerts for the specified date (first punch per student)';

    public function handle(AisensyService $aisensy): int
    {
        $date = $this->option('date') ?: Carbon::today()->format('Y-m-d');

        // Get all punches for the date, ordered
        $punches = DB::table('punch_logs')
            ->where('punch_date', $date)
            ->orderBy('employee_id')
            ->orderBy('punch_time')
            ->get();

        if ($punches->isEmpty()) {
            $this->info("No punches found for {$date}");
            return Command::SUCCESS;
        }

        // Take first punch per student as IN
        $firstPunchPerStudent = [];
        foreach ($punches as $p) {
            if (!isset($firstPunchPerStudent[$p->employee_id])) {
                $firstPunchPerStudent[$p->employee_id] = $p;
            }
        }

        $sent = 0;
        $skipped = 0;

        foreach ($firstPunchPerStudent as $roll => $p) {
            $student = Student::where('roll_number', $roll)->first();
            $phone = $student->parent_phone ?? null;
            if (empty($phone)) {
                $this->warn("Skip roll {$roll}: no parent_phone");
                $skipped++;
                continue;
            }

            // Template params must be strings and non-null
            $safeName = $student->name ?: (string) $roll;
            $messageVars = [
                (string) $safeName,
                (string) $roll,
                (string) $p->punch_time,
                (string) $p->punch_date,
            ];

            $tpl = \App\Models\Setting::get('aisensy_template_in', config('services.aisensy.template_in'));
            $resp = $aisensy->send($phone, $messageVars, $tpl);

            WhatsAppLog::create([
                'student_id' => $student->roll_number, // using roll_number as reference
                'roll_number' => $roll,
                'state' => 'IN',
                'punch_date' => $p->punch_date,
                'punch_time' => $p->punch_time,
                'sent_at' => now(),
                'status' => $resp['status'] ?? null,
                'error' => $resp['error'] ?? null,
            ]);

            if (($resp['status'] ?? null) === 'success') {
                $sent++;
                $this->info("Sent IN to {$phone} for roll {$roll} at {$p->punch_time}");
            } else {
                $this->warn("Failed IN to {$phone} for roll {$roll}: " . ($resp['error'] ?? 'Unknown error'));
            }
        }

        $this->info("Force IN: sent {$sent}, skipped {$skipped}");
        return Command::SUCCESS;
    }
}

