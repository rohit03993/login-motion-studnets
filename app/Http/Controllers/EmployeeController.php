<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = Employee::orderBy('name')->get();
        return view('employees.index', compact('employees'));
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
}
