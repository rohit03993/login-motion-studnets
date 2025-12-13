<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupDatabaseTrigger extends Command
{
    protected $signature = 'trigger:setup';
    protected $description = 'Setup database trigger for automatic punch notifications';

    public function handle(): int
    {
        $this->info("Setting up database trigger...");

        // Drop existing trigger if it exists
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS punch_logs_after_insert');
            $this->info("Dropped existing trigger (if any)");
        } catch (\Exception $e) {
            $this->warn("Could not drop existing trigger: " . $e->getMessage());
        }

        // Create the trigger
        try {
            DB::unprepared("
                CREATE TRIGGER punch_logs_after_insert
                AFTER INSERT ON punch_logs
                FOR EACH ROW
                INSERT INTO notification_queue (roll_number, punch_date, punch_time, queued_at, created_at, updated_at)
                VALUES (NEW.employee_id, NEW.punch_date, NEW.punch_time, NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ");
            $this->info("✅ Database trigger created successfully!");
            $this->info("   Trigger: punch_logs_after_insert");
            $this->info("   Action: Inserts into notification_queue when punch_logs receives new data");
        } catch (\Exception $e) {
            $this->error("❌ Failed to create trigger: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Verify trigger exists
        try {
            $triggers = DB::select("SHOW TRIGGERS LIKE 'punch_logs%'");
            if (!empty($triggers)) {
                $this->info("\n✅ Trigger verified:");
                foreach ($triggers as $trigger) {
                    $this->line("   - " . ($trigger->Trigger ?? $trigger->trigger_name ?? 'Unknown'));
                }
            } else {
                $this->warn("⚠️  Trigger not found in database");
            }
        } catch (\Exception $e) {
            $this->warn("Could not verify trigger: " . $e->getMessage());
        }

        $this->newLine();
        $this->info("Next steps:");
        $this->line("1. Start the queue processor: .\\process-queue.ps1");
        $this->line("2. Make a test punch");
        $this->line("3. Watch the queue processor output - messages should send immediately!");

        return Command::SUCCESS;
    }
}

