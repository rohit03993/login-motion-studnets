<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Update student information (name, father, class, batch).
     */
    public function update(Request $request, string $roll): RedirectResponse
    {
        // Create or get the student record
        $student = Student::firstOrNew(['roll_number' => $roll]);
        $student->roll_number = $roll; // Ensure roll_number is set

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'class_course' => 'nullable|string|max:255',
            'batch' => 'nullable|string|max:255',
        ]);

        $student->name = $validated['name'] ?? null;
        $student->father_name = $validated['father_name'] ?? null;
        $student->class_course = $validated['class_course'] ?? null;
        $student->batch = $validated['batch'] ?? null;
        $student->save();

        return back()->with('success', 'Student information updated.');
    }

    /**
     * Update parent phone and alert toggle for a student.
     */
    public function updateContact(Request $request, string $roll): RedirectResponse
    {
        // Create or update the student record so we can store the phone even if mapping was missing
        $student = Student::firstOrNew(['roll_number' => $roll]);

        $validated = $request->validate([
            'parent_phone' => 'nullable|string|max:20',
            'alerts_enabled' => 'nullable|boolean',
        ]);

        $normalized = $this->normalizeIndianPhone($validated['parent_phone'] ?? null);

        if (!empty($validated['parent_phone']) && !$normalized) {
            return back()
                ->withErrors(['parent_phone' => 'Enter a valid 10-digit Indian mobile (auto +91) or +91XXXXXXXXXX.'])
                ->withInput();
        }

        $student->parent_phone = $normalized;
        $student->alerts_enabled = $request->boolean('alerts_enabled', true);
        $student->save();

        return back()->with('success', 'Contact details updated.');
    }

    /**
     * Normalize Indian mobile numbers to +91XXXXXXXXXX.
     */
    private function normalizeIndianPhone(?string $raw): ?string
    {
        if (!$raw) {
            return null;
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
}

