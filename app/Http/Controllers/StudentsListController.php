<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Course;
use App\Models\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentsListController extends Controller
{
    /**
     * List all students with their details
     * Shows all unique employee_ids from punch_logs, even if they don't have student records
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $batch = $request->query('batch');
        
        // Check if punch_logs table exists
        $punchLogsExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        
        // Get all unique batches for filter dropdown (from students table)
        $batches = Student::whereNotNull('batch')
            ->where('batch', '!=', '')
            ->distinct()
            ->orderBy('batch')
            ->pluck('batch')
            ->toArray();
        
        // Check if there are students with no batch
        $hasNoBatch = Student::where(function($q) {
            $q->whereNull('batch')->orWhere('batch', '');
        })->exists();
        
        if (!empty($punchLogsExists)) {
            // Build ID source from punch_logs (and manual_attendances if it exists)
            $machineIds = DB::table('punch_logs')
                ->selectRaw('DISTINCT CAST(employee_id AS CHAR) as roll_number');

            $manualIds = null;
            if ($this->manualTableExists()) {
                $manualIds = DB::table('manual_attendances')
                    ->selectRaw('DISTINCT CAST(roll_number AS CHAR) as roll_number');
            }

            $idsQuery = $manualIds ? $machineIds->union($manualIds) : $machineIds;

            // Unified punches source for last punch info
            $punchesMachine = DB::table('punch_logs')
                ->selectRaw("CAST(employee_id AS CHAR) as roll_number, punch_date, punch_time");
            $punchesUnified = $punchesMachine;
            if ($this->manualTableExists()) {
                $punchesManual = DB::table('manual_attendances')
                    ->selectRaw("CAST(roll_number AS CHAR) as roll_number, punch_date, punch_time");
                $punchesUnified = $punchesUnified->unionAll($punchesManual);
            }

            $base = DB::query()->fromSub($idsQuery, 'ids')
                ->leftJoin('students as s', 's.roll_number', '=', 'ids.roll_number')
                ->select(
                    'ids.roll_number',
                    's.name',
                    's.father_name',
                    's.class_course',
                    's.batch',
                    's.parent_phone',
                    's.alerts_enabled'
                )
                ->selectSub(function($q) use ($punchesUnified) {
                    $q->fromSub($punchesUnified, 'p')
                      ->whereColumn('p.roll_number', 'ids.roll_number')
                      ->selectRaw("MAX(CONCAT(p.punch_date, ' ', p.punch_time))");
                }, 'last_punch_datetime')
                ->selectSub(function($q) use ($punchesUnified) {
                    $q->fromSub($punchesUnified, 'p')
                      ->whereColumn('p.roll_number', 'ids.roll_number')
                      ->orderBy('p.punch_date', 'desc')
                      ->orderBy('p.punch_time', 'desc')
                      ->select('p.punch_date')
                      ->limit(1);
                }, 'last_punch_date')
                ->selectSub(function($q) use ($punchesUnified) {
                    $q->fromSub($punchesUnified, 'p')
                      ->whereColumn('p.roll_number', 'ids.roll_number')
                      ->orderBy('p.punch_date', 'desc')
                      ->orderBy('p.punch_time', 'desc')
                      ->select('p.punch_time')
                      ->limit(1);
                }, 'last_punch_time');

            // Filters
            if ($batch) {
                if ($batch === '__no_batch__') {
                    $base->where(function($q) {
                        $q->whereNull('s.batch')->orWhere('s.batch', '');
                    });
                } else {
                    $base->where('s.batch', $batch);
                }
            }

            if ($search) {
                $base->where(function($q) use ($search) {
                    $q->where('ids.roll_number', 'like', "%{$search}%")
                      ->orWhere('s.name', 'like', "%{$search}%")
                      ->orWhere('s.father_name', 'like', "%{$search}%")
                      ->orWhere('s.parent_phone', 'like', "%{$search}%");
                });
            }

            $students = $base
                ->orderBy('ids.roll_number')
                ->paginate(50)
                ->appends($request->query());

            return view('students.list', compact('students', 'search', 'batch', 'batches', 'hasNoBatch'));
        }
        
        // Get all unique batches for filter dropdown (from students table)
        $batches = Student::whereNotNull('batch')
            ->where('batch', '!=', '')
            ->distinct()
            ->orderBy('batch')
            ->pluck('batch')
            ->toArray();
        
        // Check if there are students with no batch
        $hasNoBatch = Student::where(function($q) {
            $q->whereNull('batch')->orWhere('batch', '');
        })->exists();

        // Apply batch filter
        if ($batch) {
            if ($batch === '__no_batch__') {
                $query->where(function($q) {
                    $q->whereNull('batch')->orWhere('batch', '');
                });
            } else {
                $query->where('batch', $batch);
            }
        }

        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('roll_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%");
            });
        }

        // Order by roll number
        $students = $query->orderBy('roll_number')->paginate(50);

        return view('students.list', compact('students', 'search', 'batch', 'batches', 'hasNoBatch'));
    }

    /**
     * Bulk assign class to selected students
     */
    public function bulkAssignClass(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_rolls' => 'required|array|min:1',
            'student_rolls.*' => 'required|string',
            'class_course' => 'required|string|max:255',
        ]);

        $course = Course::where('name', $validated['class_course'])->first();
        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Selected class does not exist.',
            ], 400);
        }

        $updated = 0;
        foreach ($validated['student_rolls'] as $rollNumber) {
            $student = Student::firstOrNew(['roll_number' => $rollNumber]);
            $student->roll_number = $rollNumber;
            $student->class_course = $validated['class_course'];
            $student->save();
            $updated++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully assigned class to {$updated} student(s).",
            'updated_count' => $updated,
        ]);
    }

    /**
     * Bulk assign batch to selected students
     */
    public function bulkAssignBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_rolls' => 'required|array|min:1',
            'student_rolls.*' => 'required|string',
            'batch' => 'required|string|max:255',
        ]);

        $batch = Batch::where('name', $validated['batch'])->first();
        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Selected batch does not exist.',
            ], 400);
        }

        $updated = 0;
        foreach ($validated['student_rolls'] as $rollNumber) {
            $student = Student::firstOrNew(['roll_number' => $rollNumber]);
            $student->roll_number = $rollNumber;
            $student->batch = $validated['batch'];
            $student->save();
            $updated++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully assigned batch to {$updated} student(s).",
            'updated_count' => $updated,
        ]);
    }

    private function manualTableExists(): bool
    {
        $res = DB::select("SHOW TABLES LIKE 'manual_attendances'");
        return !empty($res);
    }
}

