<?php

namespace App\Http\Controllers;

use App\Models\Student;
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
        
        if (empty($punchLogsExists)) {
            // Fallback to students table only if punch_logs doesn't exist
            $query = Student::query()
                ->select('students.*')
                ->selectRaw('NULL as last_punch_datetime')
                ->selectRaw('NULL as last_punch_date')
                ->selectRaw('NULL as last_punch_time');
        } else {
            // Use raw SQL to get all unique employee_ids from punch_logs and join with students
            // This ensures we show ALL entries from punch_logs, even without student records
            $baseQuery = "
                SELECT DISTINCT
                    CAST(p.employee_id AS CHAR) as roll_number,
                    s.name,
                    s.father_name,
                    s.class_course,
                    s.batch,
                    s.parent_phone,
                    s.alerts_enabled,
                    (SELECT MAX(CONCAT(p2.punch_date, ' ', p2.punch_time)) 
                     FROM punch_logs p2 
                     WHERE CAST(p2.employee_id AS CHAR) = CAST(p.employee_id AS CHAR)) as last_punch_datetime,
                    (SELECT p3.punch_date 
                     FROM punch_logs p3 
                     WHERE CAST(p3.employee_id AS CHAR) = CAST(p.employee_id AS CHAR)
                     ORDER BY p3.punch_date DESC, p3.punch_time DESC 
                     LIMIT 1) as last_punch_date,
                    (SELECT p4.punch_time 
                     FROM punch_logs p4 
                     WHERE CAST(p4.employee_id AS CHAR) = CAST(p.employee_id AS CHAR)
                     ORDER BY p4.punch_date DESC, p4.punch_time DESC 
                     LIMIT 1) as last_punch_time
                FROM punch_logs p
                LEFT JOIN students s ON CAST(s.roll_number AS CHAR) = CAST(p.employee_id AS CHAR)
            ";
            
            $whereConditions = [];
            $params = [];
            
            // Apply batch filter
            if ($batch) {
                if ($batch === '__no_batch__') {
                    $whereConditions[] = "(s.batch IS NULL OR s.batch = '')";
                } else {
                    $whereConditions[] = "s.batch = ?";
                    $params[] = $batch;
                }
            }
            
            // Apply search filter
            if ($search) {
                $whereConditions[] = "(CAST(p.employee_id AS CHAR) LIKE ? OR s.name LIKE ? OR s.father_name LIKE ? OR s.parent_phone LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if (!empty($whereConditions)) {
                $baseQuery .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            $baseQuery .= " ORDER BY roll_number";
            
            // Get total count for pagination (before LIMIT/OFFSET)
            $countQuery = "SELECT COUNT(DISTINCT CAST(p.employee_id AS CHAR)) as total FROM punch_logs p LEFT JOIN students s ON CAST(s.roll_number AS CHAR) = CAST(p.employee_id AS CHAR)";
            if (!empty($whereConditions)) {
                $countQuery .= " WHERE " . implode(" AND ", $whereConditions);
            }
            $total = DB::select($countQuery, $params)[0]->total ?? 0;
            
            // Apply pagination
            $perPage = 50;
            $currentPage = $request->query('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            $baseQuery .= " LIMIT {$perPage} OFFSET {$offset}";
            
            $results = DB::select($baseQuery, $params);
            
            // Convert to collection and create paginator manually
            $students = new \Illuminate\Pagination\LengthAwarePaginator(
                collect($results),
                $total,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            // Get all unique batches for filter dropdown (from students table)
            $batches = Student::whereNotNull('batch')
                ->where('batch', '!=', '')
                ->distinct()
                ->orderBy('batch')
                ->pluck('batch')
                ->toArray();
            
            // Check if there are students with no batch
            $hasNoBatch = DB::select("
                SELECT COUNT(DISTINCT CAST(p.employee_id AS CHAR)) as count
                FROM punch_logs p
                LEFT JOIN students s ON CAST(s.roll_number AS CHAR) = CAST(p.employee_id AS CHAR)
                WHERE (s.batch IS NULL OR s.batch = '')
            ")[0]->count > 0;
            
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
}

