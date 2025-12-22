<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Course;
use App\Models\UserClassPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = Employee::with('user.classPermissions')->orderBy('name')->get();
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
}
