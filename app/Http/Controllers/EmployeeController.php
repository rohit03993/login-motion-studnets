<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Course;
use App\Models\UserClassPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        // Show only active employees (not discontinued)
        $employees = Employee::with('user.classPermissions')
            ->where('is_active', true)
            ->whereNull('discontinued_at')
            ->orderBy('name')
            ->get();
        $courses = Course::where('is_active', true)->orderBy('name')->get();
        return view('employees.index', compact('employees', 'courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'roll_number' => 'required|string|max:255|unique:employees,roll_number',
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'category' => 'required|in:academic,non_academic',
        ]);

        Employee::create($validated + ['is_active' => true]);

        return back()->with('success', 'Employee created successfully.');
    }

    /**
     * Create an employee from an unmapped punch (JSON).
     */
    public function createFromPunch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'roll_number' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'category' => 'required|in:academic,non_academic',
        ]);

        $employee = Employee::firstOrNew(['roll_number' => $validated['roll_number']]);
        $employee->name = $validated['name'];
        $employee->father_name = $validated['father_name'] ?? null;
        $employee->mobile = $this->normalizeIndianPhone($validated['parent_phone'] ?? null);
        $employee->category = $validated['category'];
        $employee->is_active = true;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Employee created/updated successfully.',
        ]);
    }

    private function normalizeIndianPhone(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+' . $digits;
        }
        if (strlen($digits) === 13 && str_starts_with($digits, '091')) {
            return '+' . substr($digits, 1);
        }

        return null;
    }

    /**
     * Generate login credentials for an employee
     */
    public function generateLogin(Request $request, string $roll): JsonResponse
    {
        $employee = Employee::where('roll_number', $roll)->firstOrFail();
        
        // Check if employee already has login
        if ($employee->hasLogin()) {
            return response()->json([
                'success' => false,
                'message' => 'Employee already has login credentials.',
            ], 400);
        }

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        // Create user account
        $user = User::create([
            'name' => $employee->name,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'staff',
            'can_view_employees' => false,
        ]);

        // Link employee to user
        $employee->user_id = $user->id;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Login credentials generated successfully.',
            'data' => [
                'email' => $user->email,
                'password' => $validated['password'], // Return plain password for display
            ],
        ]);
    }

    /**
     * Update permissions for an employee with login
     */
    public function updatePermissions(Request $request, string $roll): JsonResponse
    {
        $employee = Employee::where('roll_number', $roll)->firstOrFail();
        
        if (!$employee->hasLogin()) {
            return response()->json([
                'success' => false,
                'message' => 'Employee does not have login credentials. Generate login first.',
            ], 400);
        }

        $validated = $request->validate([
            'classes' => 'nullable|array',
            'classes.*' => 'string',
            'can_view_employees' => 'nullable|boolean',
        ]);

        $user = $employee->user;

        // Update employee view permission
        $user->can_view_employees = $request->boolean('can_view_employees', false);
        $user->save();

        // Sync class permissions
        UserClassPermission::where('user_id', $user->id)->delete();
        $classes = $validated['classes'] ?? [];
        foreach ($classes as $className) {
            UserClassPermission::create([
                'user_id' => $user->id,
                'class_name' => $className,
                'can_mark' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully.',
        ]);
    }

    /**
     * Discontinue an employee
     * Employee will not appear in lists but historical records remain
     */
    public function discontinue(string $roll): RedirectResponse
    {
        $employee = Employee::where('roll_number', $roll)->firstOrFail();
        $employee->discontinue();
        
        return back()->with('success', "Employee '{$employee->name}' has been discontinued. They will not appear in employee lists, but historical records are preserved.");
    }

    /**
     * Restore a discontinued employee
     */
    public function restore(string $roll): RedirectResponse
    {
        $employee = Employee::where('roll_number', $roll)->firstOrFail();
        $employee->restore();
        
        return back()->with('success', "Employee '{$employee->name}' has been restored and will appear in employee lists again.");
    }

    /**
     * Convert employee to student (Super Admin only)
     * Transforms the employee profile into a student profile - no duplicates
     */
    public function convertToStudent(Request $request, string $roll): RedirectResponse
    {
        $employee = Employee::where('roll_number', $roll)->firstOrFail();
        
        // SAFETY CHECK: Only prevent conversion if ACTIVE student exists
        // Allow conversion if student is discontinued (will be restored/overwritten)
        $existingStudent = \App\Models\Student::withTrashed()->where('roll_number', $roll)->first();
        if ($existingStudent && $existingStudent->isActive()) {
            return back()->withErrors([
                'error' => "An active student profile with this roll number already exists. Cannot convert. Please discontinue the existing student profile first if you want to proceed."
            ]);
        }
        
        $employeeName = $employee->name ?? $roll;
        
        DB::beginTransaction();
        try {
            // Create or restore student record (transfer all data)
            // If discontinued student exists, restore and update it; otherwise create new
            if ($existingStudent) {
                // Restore discontinued student and update with employee data
                $student = $existingStudent;
                $student->restore(); // Restore if soft-deleted
            } else {
                // Create new student record
                $student = new \App\Models\Student();
                $student->roll_number = $roll;
            }
            
            // Update/Set student data from employee
            $student->name = $employee->name;
            $student->father_name = $employee->father_name;
            $student->parent_phone = $employee->mobile; // Use mobile as primary phone
            $student->parent_phone_secondary = null;
            $student->whatsapp_send_to = 'primary';
            $student->alerts_enabled = true;
            $student->class_course = $student->class_course ?? null; // Preserve existing if restored
            $student->batch = $student->batch ?? null; // Preserve existing if restored
            $student->discontinued_at = null; // Ensure active
            $student->deleted_at = null; // Ensure not soft-deleted
            $student->save();
            
            // Permanently delete the employee record (no duplicate, no discontinuation)
            // All related data (punch_logs, etc.) remains intact as they reference by roll_number
            // Note: Employee model doesn't use SoftDeletes, so delete() is a hard delete
            // If employee has a user account (user_id), it will remain but won't be linked to anything
            // This is fine as the user account can be cleaned up separately if needed
            $employee->delete();
            
            DB::commit();
            
            return redirect()->route('students.show', $roll)
                ->with('success', "Employee profile '{$employeeName}' has been transformed into a student profile. All attendance data has been preserved.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to convert employee to student: ' . $e->getMessage()]);
        }
    }
}
