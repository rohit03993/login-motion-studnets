<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\Setting;
use App\Services\AisensyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyWhatsappSetup extends Command
{
    protected $signature = 'whatsapp:verify';
    protected $description = 'Verify WhatsApp notification setup is correct';

    public function handle(): int
    {
        $this->info("=== WhatsApp Notification Setup Verification ===\n");

        // Check 1: Aisensy API Configuration
        $this->info("1. Checking Aisensy API Configuration...");
        $apiKey = env('AISENSY_API_KEY');
        $apiUrl = Setting::get('aisensy_url', env('AISENSY_URL'));
        $templateIn = Setting::get('aisensy_template_in', config('services.aisensy.template_in'));
        $templateOut = Setting::get('aisensy_template_out', config('services.aisensy.template_out'));

        if (empty($apiKey)) {
            $this->error("   ❌ AISENSY_API_KEY not found in .env");
        } else {
            $this->info("   ✅ API Key configured");
        }

        if (empty($apiUrl)) {
            $this->error("   ❌ Aisensy URL not configured");
        } else {
            $this->info("   ✅ API URL: {$apiUrl}");
        }

        if (empty($templateIn)) {
            $this->error("   ❌ IN template name not configured");
        } else {
            $this->info("   ✅ IN Template: {$templateIn}");
        }

        if (empty($templateOut)) {
            $this->warn("   ⚠️  OUT template name not configured (OUT messages will fail)");
        } else {
            $this->info("   ✅ OUT Template: {$templateOut}");
        }

        $this->newLine();

        // Check 2: Students with phone numbers
        $this->info("2. Checking Students Configuration...");
        $studentsWithPhone = Student::whereNotNull('parent_phone')
            ->where('parent_phone', '!=', '')
            ->where('alerts_enabled', true)
            ->count();
        
        $studentsWithoutPhone = Student::where(function($q) {
            $q->whereNull('parent_phone')
              ->orWhere('parent_phone', '')
              ->orWhere('alerts_enabled', false);
        })->count();

        $this->info("   ✅ Students ready for alerts: {$studentsWithPhone}");
        if ($studentsWithoutPhone > 0) {
            $this->warn("   ⚠️  Students without phone/alerts disabled: {$studentsWithoutPhone}");
        }

        // Show sample students
        $sampleStudents = Student::whereNotNull('parent_phone')
            ->where('parent_phone', '!=', '')
            ->where('alerts_enabled', true)
            ->limit(3)
            ->get(['roll_number', 'name', 'parent_phone']);

        if ($sampleStudents->isNotEmpty()) {
            $this->info("   Sample students ready:");
            foreach ($sampleStudents as $s) {
                $this->line("      - {$s->roll_number}: {$s->name} → {$s->parent_phone}");
            }
        }

        $this->newLine();

        // Check 3: Recent punches
        $this->info("3. Checking Recent Punches...");
        $recentPunches = DB::table('punch_logs')
            ->where('punch_date', '>=', now()->subDays(1)->format('Y-m-d'))
            ->count();
        
        $this->info("   ✅ Punches in last 24 hours: {$recentPunches}");

        $todayPunches = DB::table('punch_logs')
            ->where('punch_date', now()->format('Y-m-d'))
            ->count();
        
        $this->info("   ✅ Punches today: {$todayPunches}");

        $this->newLine();

        // Check 4: Scheduler Status
        $this->info("4. Scheduler Status...");
        $this->warn("   ⚠️  IMPORTANT: The scheduler must be running for automatic messages!");
        $this->line("   To start scheduler, run: .\\run-scheduler.ps1");
        $this->line("   Or manually: php artisan schedule:run (every minute)");

        $this->newLine();

        // Check 5: Test message capability
        $this->info("5. Testing Message Sending Capability...");
        $testStudent = Student::whereNotNull('parent_phone')
            ->where('parent_phone', '!=', '')
            ->where('alerts_enabled', true)
            ->first();

        if ($testStudent) {
            $aisensy = new AisensyService();
            $normalizedPhone = $this->normalizePhone($testStudent->parent_phone);
            if ($normalizedPhone) {
                $this->info("   ✅ Can send test message to: {$normalizedPhone}");
                $this->line("      (Student: {$testStudent->name}, Roll: {$testStudent->roll_number})");
            } else {
                $this->error("   ❌ Cannot normalize phone: {$testStudent->parent_phone}");
            }
        } else {
            $this->error("   ❌ No students with valid phone numbers found");
        }

        $this->newLine();
        $this->info("=== Verification Complete ===");
        $this->newLine();
        $this->info("Next Steps:");
        $this->line("1. Ensure scheduler is running: .\\run-scheduler.ps1");
        $this->line("2. Make a test punch");
        $this->line("3. Wait 1-2 minutes");
        $this->line("4. Check scheduler output for message status");

        return Command::SUCCESS;
    }

    private function normalizePhone(?string $raw): ?string
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

