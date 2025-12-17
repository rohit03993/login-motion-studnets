<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetStudentData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example:
     * php artisan students:reset
     */
    protected $signature = 'students:reset {--keep-manual=0 : Keep manual_attendances table data instead of truncating}';

    /**
     * The console command description.
     */
    protected $description = 'Truncate students/manual_attendances, keep punch_logs, and seed a default course+batch bucket';

    private const DEFAULT_COURSE = 'Default Program';
    private const DEFAULT_BATCH = 'Default Batch';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Resetting student-related data (students + manual_attendances) while keeping punch_logs...');

        // Use simple flow (no explicit transaction) because TRUNCATE auto-commits on MySQL
        try {
            if (Schema::hasTable('students')) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table('students')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                $this->line('- truncated students');
            } else {
                $this->warn('- students table not found; skipped');
            }

            if (!$this->option('keep-manual') && Schema::hasTable('manual_attendances')) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table('manual_attendances')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                $this->line('- truncated manual_attendances');
            } else {
                $this->line('- manual_attendances kept');
            }
        } catch (\Throwable $e) {
            $this->error('Reset failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Ensure default course + batch exist
        $course = Course::firstOrCreate(
            ['name' => self::DEFAULT_COURSE],
            ['description' => 'Auto-created default program/bucket', 'is_active' => true]
        );

        Batch::firstOrCreate(
            ['name' => self::DEFAULT_BATCH],
            ['course_id' => $course->id, 'description' => 'Auto-created default batch', 'is_active' => true]
        );

        $this->info('Default bucket ensured: "' . self::DEFAULT_COURSE . '" / "' . self::DEFAULT_BATCH . '"');
        $this->info('Done.');

        return self::SUCCESS;
    }
}
