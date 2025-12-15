<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed the application's database with development test data.
     */
    public function run(): void
    {
        // Create test students
        $students = [
            ['roll_number' => '1', 'name' => 'Rohit Kumar', 'class_course' => 'Class 10', 'parent_phone' => '9876543210', 'batch' => 'A', 'father_name' => 'Mr. Kumar'],
            ['roll_number' => '2', 'name' => 'Priya Sharma', 'class_course' => 'Class 10', 'parent_phone' => '9876543211', 'batch' => 'A', 'father_name' => 'Mr. Sharma'],
            ['roll_number' => '3', 'name' => 'Amit Singh', 'class_course' => 'Class 11', 'parent_phone' => '9876543212', 'batch' => 'B', 'father_name' => 'Mr. Singh'],
            ['roll_number' => '4', 'name' => 'Sneha Patel', 'class_course' => 'Class 11', 'parent_phone' => '9876543213', 'batch' => 'B', 'father_name' => 'Mr. Patel'],
            ['roll_number' => '5', 'name' => 'Raj Verma', 'class_course' => 'Class 12', 'parent_phone' => '9876543214', 'batch' => 'C', 'father_name' => 'Mr. Verma'],
        ];

        foreach ($students as $student) {
            DB::table('students')->updateOrInsert(
                ['roll_number' => $student['roll_number']],
                array_merge($student, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('Created ' . count($students) . ' test students.');

        // Check if punch_logs table exists
        $tableExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        if (empty($tableExists)) {
            $this->command->warn('punch_logs table does not exist. Creating it...');
            
            // Create punch_logs table structure (matching EasyTimePro structure)
            DB::statement("
                CREATE TABLE IF NOT EXISTS punch_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id VARCHAR(255) NOT NULL,
                    punch_date DATE NOT NULL,
                    punch_time TIME NOT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    INDEX idx_employee_date (employee_id, punch_date),
                    INDEX idx_date (punch_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Generate test punch data for the last 7 days
        $this->generateTestPunches($students);

        $this->command->info('Development database seeded successfully!');
    }

    /**
     * Generate test punch data for students
     */
    private function generateTestPunches(array $students): void
    {
        $punchCount = 0;
        $days = 7; // Last 7 days

        for ($day = 0; $day < $days; $day++) {
            $date = Carbon::now()->subDays($day)->format('Y-m-d');
            
            foreach ($students as $student) {
                // Randomly decide if student attended (80% chance)
                if (rand(1, 100) <= 80) {
                    // IN punch between 8:00 AM and 9:30 AM
                    $inHour = rand(8, 9);
                    $inMinute = $inHour == 8 ? rand(0, 59) : rand(0, 30);
                    $inTime = sprintf('%02d:%02d:%02d', $inHour, $inMinute, rand(0, 59));

                    // OUT punch between 3:00 PM and 5:00 PM
                    $outHour = rand(15, 17);
                    $outMinute = rand(0, 59);
                    $outTime = sprintf('%02d:%02d:%02d', $outHour, $outMinute, rand(0, 59));

                    // Insert IN punch
                    DB::table('punch_logs')->insert([
                        'employee_id' => $student['roll_number'],
                        'punch_date' => $date,
                        'punch_time' => $inTime,
                        'created_at' => "$date $inTime",
                        'updated_at' => "$date $inTime",
                    ]);
                    $punchCount++;

                    // Insert OUT punch
                    DB::table('punch_logs')->insert([
                        'employee_id' => $student['roll_number'],
                        'punch_date' => $date,
                        'punch_time' => $outTime,
                        'created_at' => "$date $outTime",
                        'updated_at' => "$date $outTime",
                    ]);
                    $punchCount++;
                }
            }
        }

        $this->command->info("Generated $punchCount test punch records for the last $days days.");
    }
}

