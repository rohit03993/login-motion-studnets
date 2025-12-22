<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Course;
use App\Models\Batch;
use App\Models\Roster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentsListController extends Controller
{
    private const DEFAULT_COURSE = 'Default Program';
    private const DEFAULT_BATCH = 'Default Batch';
    /**
     * List all students with their details
     * Shows all unique employee_ids from punch_logs, even if they don't have student records
     */
    public function index(Request $request)
    {
        $roll = $request->query('roll');
        $name = $request->query('name');
        $classFilter = $request->query('class');
        $dateFilter = $request->query('date');
        $search = $request->query('search'); // Keep for backward compatibility
        $batch = $request->query('batch');
        $user = auth()->user();
        $isSuper = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : false;
        $allowedClasses = $isSuper ? [] : \App\Models\UserClassPermission::where('user_id', $user->id)
            ->pluck('class_name')->unique()->values()->toArray();
        
        // Check if punch_logs table exists
        $punchLogsExists = DB::select("SHOW TABLES LIKE 'punch_logs'");
        // Check if employees table exists
        $employeesTableExists = DB::select("SHOW TABLES LIKE 'employees'");
        
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
                ->leftJoin('students as s', 's.roll_number', '=', 'ids.roll_number');
            
            // Exclude discontinued students
            $base->where(function($q) {
                $q->whereNull('s.discontinued_at')
                  ->orWhereNull('s.roll_number'); // Include unmapped punches
            });
            
            // Exclude employees if employees table exists
            if (!empty($employeesTableExists)) {
                $base->leftJoin('employees as e', 'e.roll_number', '=', 'ids.roll_number')
                     ->whereNull('e.roll_number'); // Exclude employees - only show students
            }
            
            $base->select(
                    'ids.roll_number',
                    's.name',
                    's.father_name',
                    's.class_course',
                    's.batch',
                    's.parent_phone',
                    's.alerts_enabled',
                    's.deleted_at',
                    's.discontinued_at'
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

            // Permissions filter (classes)
            if (!$isSuper) {
                if (empty($allowedClasses)) {
                    $base->whereRaw('1=0');
                } else {
                    $base->whereIn('s.class_course', $allowedClasses);
                }
            }

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

            // Apply filters: roll, name, class, or legacy search
            if ($roll) {
                $base->where('ids.roll_number', 'like', "%{$roll}%");
            }
            if ($name) {
                $base->where(function($q) use ($name) {
                    $q->where('s.name', 'like', "%{$name}%")
                      ->orWhere('s.father_name', 'like', "%{$name}%");
                });
            }
            if ($classFilter) {
                $base->where('s.class_course', $classFilter);
            }
            // Legacy search support (backward compatibility)
            if ($search && !$roll && !$name) {
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

            return view('students.list', compact('students', 'search', 'batch', 'batches', 'hasNoBatch', 'roll', 'name', 'classFilter', 'dateFilter'));
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

        // Base query (no punch logs path)
        // Exclude employees - ensure roll_number doesn't exist in employees table
        $employeesTableExists = DB::select("SHOW TABLES LIKE 'employees'");
        $query = Student::query();
        if (!empty($employeesTableExists)) {
            $employeeRollNumbers = DB::table('employees')->pluck('roll_number')->toArray();
            if (!empty($employeeRollNumbers)) {
                $query->whereNotIn('roll_number', $employeeRollNumbers);
            }
        }
        if (!$isSuper) {
            if (empty($allowedClasses)) {
                $query->whereRaw('1=0');
            } else {
                $query->whereIn('class_course', $allowedClasses);
            }
        }

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
     * Bulk import students from CSV/Excel (roll_number,name,father_name,parent_phone).
     */
    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'map_roll' => 'required|string',
            'map_name' => 'required|string',
            'map_father' => 'nullable|string',
            'map_phone' => 'nullable|string',
            'overwrite_mode' => 'nullable|boolean',
        ]);

        $file = $validated['file'];
        $path = $file->getRealPath();

        $rows = $this->readCsv($path);
        if (empty($rows)) {
            return back()->withErrors(['file' => 'The file is empty or not readable.']);
        }

        // Normalize headers
        $headers = array_map(function ($h) {
            return Str::of($h)->trim()->toString();
        }, array_keys($rows[0]));

        // Normalize column mappings - find best match for phone column if exact match fails
        $mapRoll = $validated['map_roll'];
        $mapName = $validated['map_name'];
        $mapFather = $validated['map_father'] ?? null;
        $mapPhone = $validated['map_phone'] ?? null;
        
        // If phone mapping is provided but doesn't exist in headers, try to find similar column
        if ($mapPhone && !isset($rows[0][$mapPhone])) {
            $phoneVariations = ['MOBILE', 'PHONE', 'MOBILE NO', 'PHONE NO', 'CONTACT', 'CONTACT NO', 'MOBILE NUMBER', 'PHONE NUMBER'];
            foreach ($phoneVariations as $variation) {
                if (isset($rows[0][$variation])) {
                    $mapPhone = $variation;
                    break;
                }
            }
            // If still not found, try case-insensitive partial match
            if (!isset($rows[0][$mapPhone])) {
                foreach ($headers as $header) {
                    $headerUpper = strtoupper($header);
                    if (strpos($headerUpper, 'MOBILE') !== false || strpos($headerUpper, 'PHONE') !== false) {
                        $mapPhone = $header;
                        break;
                    }
                }
            }
        }
        
        $overwriteMode = $request->boolean('overwrite_mode', false);

        $this->ensureDefaultBucket();

        $created = 0;
        $updated = 0;
        foreach ($rows as $row) {
            $roll = trim((string) ($row[$mapRoll] ?? ''));
            if ($roll === '') {
                continue;
            }

            $isNew = !Student::where('roll_number', $roll)->exists();
            $student = Student::firstOrNew(['roll_number' => $roll]);
            $student->roll_number = $roll;
            
            // Name: always update if provided (required field)
            $nameValue = trim((string) ($row[$mapName] ?? ''));
            if ($nameValue !== '') {
                $student->name = $nameValue;
            } elseif ($overwriteMode && $isNew) {
                $student->name = null;
            }
            
            // Father name: update only if provided or overwrite mode
            if ($mapFather) {
                $fatherValue = trim((string) ($row[$mapFather] ?? ''));
                if ($fatherValue !== '') {
                    $student->father_name = $fatherValue;
                } elseif ($overwriteMode) {
                    $student->father_name = null;
                }
                // else: preserve existing value
            }
            
            // Class/Batch: preserve existing unless overwrite mode or new student
            if ($overwriteMode || $isNew) {
                $student->class_course = self::DEFAULT_COURSE;
                $student->batch = self::DEFAULT_BATCH;
            }
            // else: preserve existing class/batch
            
            // Phone: normalize and update only if provided or overwrite mode
            if ($mapPhone) {
                $phoneValue = trim((string) ($row[$mapPhone] ?? ''));
                if ($phoneValue !== '') {
                    $normalized = $this->normalizeIndianPhone($phoneValue);
                    if ($normalized) {
                        $student->parent_phone = $normalized;
                    }
                } elseif ($overwriteMode) {
                    $student->parent_phone = null;
                }
                // else: preserve existing phone
            }
            
            // Set defaults only for new students
            if ($isNew) {
                $student->whatsapp_send_to = 'primary';
                $student->alerts_enabled = true;
            }
            // else: preserve existing whatsapp_send_to and alerts_enabled
            
            $student->save();
            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        // Store roster with mapping for future auto-creation
        Storage::makeDirectory('rosters');
        $storedPath = $file->storeAs('rosters', time() . '-' . $file->getClientOriginalName());
        Roster::create([
            'file_name' => $file->getClientOriginalName(),
            'storage_path' => $storedPath,
            'headers' => $headers,
            'mapping' => [
                'roll' => $mapRoll,
                'name' => $mapName,
                'father' => $mapFather,
                'phone' => $mapPhone,
            ],
        ]);

        $message = "Imported {$created} new student(s)";
        if ($updated > 0) {
            $message .= ", updated {$updated} existing student(s)";
        }
        if ($overwriteMode) {
            $message .= ". All fields overwritten.";
        } else {
            $message .= ". Existing class/batch preserved for existing students.";
        }
        
        return back()->with('success', $message);
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

        $course = Course::with('batches')->where('name', $validated['class_course'])->first();
        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Selected class does not exist.',
            ], 400);
        }

        // Choose the first batch of the course if present, otherwise fallback to Default Batch
        $targetBatch = $course->batches->first()->name ?? self::DEFAULT_BATCH;

        $updated = 0;
        foreach ($validated['student_rolls'] as $rollNumber) {
            $student = Student::firstOrNew(['roll_number' => $rollNumber]);
            $student->roll_number = $rollNumber;
            $student->class_course = $validated['class_course'];
            $student->batch = $targetBatch;
            $student->save();
            $updated++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully assigned class and batch to {$updated} student(s).",
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

    private function ensureDefaultBucket(): void
    {
        $course = Course::firstOrCreate(
            ['name' => self::DEFAULT_COURSE],
            ['description' => 'Auto-created default program/bucket', 'is_active' => true]
        );

        Batch::firstOrCreate(
            ['name' => self::DEFAULT_BATCH],
            ['course_id' => $course->id, 'description' => 'Auto-created default batch', 'is_active' => true]
        );
    }

    private function normalizeIndianPhone(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        // If already has +91 prefix, return as is (after validation)
        if (str_starts_with($raw, '+91')) {
            $digits = preg_replace('/\D+/', '', $raw);
            if (strlen($digits) === 12) {
                return $raw; // Already correct format
            }
        }

        $digits = preg_replace('/\D+/', '', $raw);

        // 10-digit local number
        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }
        
        // 12-digit with leading 91
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+' . $digits;
        }
        
        // 13-digit captured when input was +91XXXXXXXXXX (non-digits stripped)
        if (strlen($digits) === 13 && str_starts_with($digits, '091')) {
            return '+' . substr($digits, 1);
        }

        return null;
    }

    /**
     * Read CSV into an array of associative rows with basic delimiter/header detection.
     */
    private function readCsv(string $path): array
    {
        $rows = [];
        if (!is_readable($path)) {
            return $rows;
        }

        if (($handle = fopen($path, 'r')) === false) {
            return $rows;
        }

        // Read first few lines to detect delimiter and header
        $sampleLines = [];
        $maxSample = 5;
        while (($line = fgets($handle)) !== false && count($sampleLines) < $maxSample) {
            $trim = trim($line);
            if ($trim === '') {
                continue;
            }
            $sampleLines[] = $trim;
        }
        // Reset pointer
        rewind($handle);

        // Detect delimiter: choose from comma, semicolon, tab by max splits
        $delims = [',', ';', "\t"];
        $bestDelim = ',';
        $bestScore = -1;
        foreach ($delims as $d) {
            $score = 0;
            foreach ($sampleLines as $l) {
                $score += substr_count($l, $d);
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelim = $d;
            }
        }

        // Determine header line: first non-empty; if only one column but next line has more, use next line
        $header = null;
        $firstDataLine = null;
        if (!empty($sampleLines)) {
            $hdrParts = array_map('trim', explode($bestDelim, $sampleLines[0]));
            if (count($hdrParts) === 1 && isset($sampleLines[1])) {
                $nextParts = array_map('trim', explode($bestDelim, $sampleLines[1]));
                if (count($nextParts) > 1) {
                    $header = $nextParts;
                    $firstDataLine = 2; // start reading after second sample line
                }
            }
            if ($header === null) {
                $header = $hdrParts;
                $firstDataLine = 1;
            }
        }

        // Generate generic headers if empty
        $header = array_map(function ($h, $idx) {
            $trimmed = Str::of($h)->trim()->toString();
            return $trimmed !== '' ? $trimmed : 'Column' . ($idx + 1);
        }, $header, array_keys($header));

        $lineNumber = 0;
        while (($data = fgetcsv($handle, 0, $bestDelim)) !== false) {
            $lineNumber++;
            if ($lineNumber < $firstDataLine) {
                continue; // skip header/title lines
            }
            if (count($data) === 1 && trim($data[0]) === '') {
                continue;
            }
            $row = [];
            foreach ($header as $idx => $col) {
                $row[$col] = $data[$idx] ?? null;
            }
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }
}

