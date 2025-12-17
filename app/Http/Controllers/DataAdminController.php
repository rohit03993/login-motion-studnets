<?php

namespace App\Http\Controllers;

use App\Console\Commands\ResetStudentData;
use App\Models\Batch;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataAdminController extends Controller
{
    private const DEFAULT_COURSE = 'Default Program';
    private const DEFAULT_BATCH = 'Default Batch';

    public function reset(): RedirectResponse
    {
        Artisan::call('students:reset');
        return back()->with('status', 'Students and manual attendance reset. Default bucket ensured.');
    }

    public function seedDefaults(): RedirectResponse
    {
        $course = Course::firstOrCreate(
            ['name' => self::DEFAULT_COURSE],
            ['description' => 'Auto-created default program/bucket', 'is_active' => true]
        );

        Batch::firstOrCreate(
            ['name' => self::DEFAULT_BATCH],
            ['course_id' => $course->id, 'description' => 'Auto-created default batch', 'is_active' => true]
        );

        return back()->with('status', 'Default Program/Batch ensured.');
    }

    public function clearPunchLogs(): RedirectResponse
    {
        if (Schema::hasTable('punch_logs')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('punch_logs')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return back()->with('status', 'punch_logs truncated.');
        }
        return back()->with('status', 'punch_logs table not found; skipped.');
    }

    public function clearWhatsappLogs(): RedirectResponse
    {
        if (Schema::hasTable('whatsapp_logs')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('whatsapp_logs')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return back()->with('status', 'whatsapp_logs truncated.');
        }
        return back()->with('status', 'whatsapp_logs table not found; skipped.');
    }
}
