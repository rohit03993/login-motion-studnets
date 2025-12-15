<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncStudentsFromPunchLogs extends Command
{
    protected $signature = 'students:sync-from-punches';
    protected $description = 'Create student records for all unique employee_ids in punch_logs that don\'t have student records yet';

    public function handle(): int
    {
        // Check if punch_logs table exists
        $tableExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        if (empty($tableExists)) {
            $this->error('punch_logs table does not exist yet.');
            return Command::FAILURE;
        }

        // Get all unique employee_ids from punch_logs using raw SQL (same as StudentsListController)
        // This ensures we get ALL unique values, matching what tinker shows
        $results = DB::select("SELECT DISTINCT CAST(employee_id AS CHAR) as employee_id FROM punch_logs");
        
        // Convert to array of strings
        $uniqueEmployeeIds = array_map(function($row) {
            return (string) $row->employee_id;
        }, $results);
        
        // Remove any duplicates (shouldn't happen, but safety check)
        $uniqueEmployeeIds = array_unique($uniqueEmployeeIds);
        $uniqueEmployeeIds = array_values($uniqueEmployeeIds); // Re-index array

        $this->info('Found ' . count($uniqueEmployeeIds) . ' unique employee IDs in punch_logs');
        
        // Debug: Show all IDs found
        if (count($uniqueEmployeeIds) > 0) {
            $this->line('All IDs found: ' . implode(', ', $uniqueEmployeeIds));
        } else {
            $this->warn('No employee IDs found in punch_logs table!');
        }

        $created = 0;
        $existing = 0;

        foreach ($uniqueEmployeeIds as $employeeId) {
            // Use firstOrCreate to preserve existing seeded data
            // This will only create if student doesn't exist, and won't overwrite existing data
            $student = Student::firstOrCreate(
                ['roll_number' => (string) $employeeId],
                [
                    'name' => null,
                    'father_name' => null,
                    'class_course' => null,
                    'batch' => null,
                    'parent_phone' => null,
                    'alerts_enabled' => true,
                ]
            );
            
            if ($student->wasRecentlyCreated) {
                $created++;
                $this->line("Created student record for roll number: {$employeeId}");
            } else {
                $existing++;
            }
        }

        $this->info("\nSync complete!");
        $this->info("Created: {$created} new student records");
        $this->info("Already existed: {$existing} student records");
        $this->info("Total students now: " . Student::count());

        return Command::SUCCESS;
    }
}

