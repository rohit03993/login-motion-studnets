<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentsListController extends Controller
{
    /**
     * List all students with their details
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $batch = $request->query('batch');
        
        // Get all unique batches for filter dropdown
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
        
        // Get all unique students with their last punch info
        $query = Student::query()
            ->select('students.*')
            ->selectRaw('(SELECT MAX(CONCAT(punch_date, " ", punch_time)) 
                          FROM punch_logs 
                          WHERE employee_id = students.roll_number) as last_punch_datetime')
            ->selectRaw('(SELECT punch_date 
                          FROM punch_logs 
                          WHERE employee_id = students.roll_number 
                          ORDER BY punch_date DESC, punch_time DESC 
                          LIMIT 1) as last_punch_date')
            ->selectRaw('(SELECT punch_time 
                          FROM punch_logs 
                          WHERE employee_id = students.roll_number 
                          ORDER BY punch_date DESC, punch_time DESC 
                          LIMIT 1) as last_punch_time');

        // Apply batch filter
        if ($batch) {
            if ($batch === '__no_batch__') {
                // Show students with no batch (null or empty)
                $query->where(function($q) {
                    $q->whereNull('batch')->orWhere('batch', '');
                });
            } else {
                $query->where('batch', $batch);
            }
        }

        // Apply search filter (roll number, name, father's name, mobile)
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

