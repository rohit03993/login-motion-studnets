<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds critical indexes to punch_logs table for performance optimization
     * with 250+ students and growing data volume.
     */
    public function up(): void
    {
        // Only add indexes if punch_logs table exists (from EasyTimePro parallel database)
        // If table doesn't exist yet, indexes will be created later via: php artisan trigger:setup
        $tableExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        if (empty($tableExists)) {
            return; // Table doesn't exist yet, skip index creation
        }
        
        // Check if indexes already exist before creating
        $indexes = DB::select("SHOW INDEXES FROM punch_logs");
        $existingIndexes = array_column($indexes, 'Key_name');
        
        // Composite index for date range queries (most common)
        if (!in_array('idx_employee_date_time', $existingIndexes)) {
            DB::statement('CREATE INDEX idx_employee_date_time ON punch_logs (employee_id, punch_date, punch_time)');
        }
        
        // Index for date filtering (dashboard queries)
        if (!in_array('idx_punch_date', $existingIndexes)) {
            DB::statement('CREATE INDEX idx_punch_date ON punch_logs (punch_date)');
        }
        
        // Index for employee_id lookups
        if (!in_array('idx_employee_id', $existingIndexes)) {
            DB::statement('CREATE INDEX idx_employee_id ON punch_logs (employee_id)');
        }
        
        // Composite index for date + time sorting (common in queries)
        if (!in_array('idx_date_time_desc', $existingIndexes)) {
            DB::statement('CREATE INDEX idx_date_time_desc ON punch_logs (punch_date DESC, punch_time DESC)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_employee_date_time ON punch_logs');
        DB::statement('DROP INDEX IF EXISTS idx_punch_date ON punch_logs');
        DB::statement('DROP INDEX IF EXISTS idx_employee_id ON punch_logs');
        DB::statement('DROP INDEX IF EXISTS idx_date_time_desc ON punch_logs');
    }
};

