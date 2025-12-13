<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create notification queue table
        Schema::create('notification_queue', function (Blueprint $table) {
            $table->id();
            $table->string('roll_number');
            $table->date('punch_date');
            $table->time('punch_time');
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamps();
            
            // Unique constraint to prevent duplicate queue entries
            $table->unique(['roll_number', 'punch_date', 'punch_time'], 'unique_punch_notification');
            $table->index(['processed', 'queued_at']);
        });

        // Create MySQL trigger that fires when a new punch is inserted
        // This trigger will automatically add entries to the notification queue
        // Note: Only create trigger if punch_logs table exists (from EasyTimePro parallel database)
        // If table doesn't exist yet, trigger will be created later via: php artisan trigger:setup
        $tableExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        if (!empty($tableExists)) {
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS punch_logs_after_insert
                AFTER INSERT ON punch_logs
                FOR EACH ROW
                INSERT INTO notification_queue (roll_number, punch_date, punch_time, queued_at, created_at, updated_at)
                VALUES (NEW.employee_id, NEW.punch_date, NEW.punch_time, NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ");
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS punch_logs_after_insert');
        Schema::dropIfExists('notification_queue');
    }
};

