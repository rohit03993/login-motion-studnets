<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentController extends Controller
{
    private const DEFAULT_COURSE = 'Default Program';
    private const DEFAULT_BATCH = 'Default Batch';
    /**
     * Update student information (name, father, class, batch, contact, alerts).
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
            'parent_phone' => 'nullable|string|max:20',
            'parent_phone_secondary' => 'nullable|string|max:20',
            'whatsapp_send_to' => 'nullable|in:primary,secondary,both',
            'alerts_enabled' => 'nullable|boolean',
        ]);

        $student->name = $validated['name'] ?? null;
        $student->father_name = $validated['father_name'] ?? null;
        $student->class_course = $validated['class_course'] ?? null;
        $student->batch = $validated['batch'] ?? null;

        // Handle primary phone normalization
        $normalizedPrimary = $this->normalizeIndianPhone($validated['parent_phone'] ?? null);
        if (!empty($validated['parent_phone']) && !$normalizedPrimary) {
            return back()
                ->withErrors(['parent_phone' => 'Enter a valid 10-digit Indian mobile (auto +91) or +91XXXXXXXXXX.'])
                ->withInput();
        }
        $student->parent_phone = $normalizedPrimary;

        // Handle secondary phone normalization
        $normalizedSecondary = $this->normalizeIndianPhone($validated['parent_phone_secondary'] ?? null);
        if (!empty($validated['parent_phone_secondary']) && !$normalizedSecondary) {
            return back()
                ->withErrors(['parent_phone_secondary' => 'Enter a valid 10-digit Indian mobile (auto +91) or +91XXXXXXXXXX.'])
                ->withInput();
        }
        $student->parent_phone_secondary = $normalizedSecondary;

        // Set WhatsApp send to preference
        $student->whatsapp_send_to = $validated['whatsapp_send_to'] ?? 'primary';
        $student->alerts_enabled = $request->boolean('alerts_enabled', true);

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
    * Create a student from an unmapped punch (JSON endpoint).
    */
    public function createFromPunch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'roll_number' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'class_course' => 'nullable|string|max:255',
            'batch' => 'nullable|string|max:255',
        ]);

        $this->ensureDefaultBucket();

        $roll = $validated['roll_number'];
        $student = Student::firstOrNew(['roll_number' => $roll]);

        $student->roll_number = $roll;
        $student->name = $validated['name'];
        $student->father_name = $validated['father_name'] ?? null;
        $student->class_course = $validated['class_course'] ?? self::DEFAULT_COURSE;
        $student->batch = $validated['batch'] ?? self::DEFAULT_BATCH;

        $normalizedPrimary = $this->normalizeIndianPhone($validated['parent_phone'] ?? null);
        if (!empty($validated['parent_phone']) && !$normalizedPrimary) {
            return response()->json([
                'success' => false,
                'message' => 'Enter a valid 10-digit Indian mobile (auto +91) or +91XXXXXXXXXX.',
            ], 422);
        }
        $student->parent_phone = $normalizedPrimary;
        $student->whatsapp_send_to = 'primary';
        $student->alerts_enabled = true;

        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'Student created/updated successfully.',
        ]);
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

    /**
     * Discontinue a student (soft delete)
     * Student will not appear in manual attendance but historical records remain
     */
    public function discontinue(string $roll): RedirectResponse
    {
        $student = Student::where('roll_number', $roll)->firstOrFail();
        $student->discontinue();
        
        return back()->with('success', "Student '{$student->name}' has been discontinued. They will not appear in manual attendance, but historical records are preserved.");
    }

    /**
     * Restore a discontinued student
     */
    public function restore(string $roll): RedirectResponse
    {
        $student = Student::withTrashed()->where('roll_number', $roll)->firstOrFail();
        $student->restore();
        
        return back()->with('success', "Student '{$student->name}' has been restored and will appear in manual attendance again.");
    }

    /**
     * Permanently delete a discontinued student and all their data
     * WARNING: This is irreversible and deletes all related records
     */
    public function deletePermanent(string $roll): RedirectResponse
    {
        $student = Student::withTrashed()->where('roll_number', $roll)->firstOrFail();
        
        // Verify student is discontinued before allowing permanent delete
        if (!$student->trashed() && !$student->discontinued_at) {
            return back()->withErrors(['error' => 'Only discontinued students can be permanently deleted.']);
        }
        
        $studentName = $student->name ?? $roll;
        
        // Permanently delete all related data
        DB::beginTransaction();
        try {
            // Delete punch logs
            DB::table('punch_logs')->where('employee_id', $roll)->delete();
            
            // Delete manual attendance records
            if (Schema::hasTable('manual_attendances')) {
                DB::table('manual_attendances')->where('roll_number', $roll)->delete();
            }
            
            // Delete WhatsApp logs
            if (Schema::hasTable('whatsapp_logs')) {
                DB::table('whatsapp_logs')->where('roll_number', $roll)->delete();
            }
            
            // Delete notification queue entries
            if (Schema::hasTable('notification_queue')) {
                DB::table('notification_queue')->where('roll_number', $roll)->delete();
            }
            
            // Permanently delete the student record (force delete)
            $student->forceDelete();
            
            DB::commit();
            
            return redirect()->route('students.index')
                ->with('success', "Student '{$studentName}' and all their data have been permanently deleted.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete student: ' . $e->getMessage()]);
        }
    }

    /**
     * Convert student to employee (Super Admin only)
     * Transforms the student profile into an employee profile - no duplicates
     */
    public function convertToEmployee(Request $request, string $roll): RedirectResponse
    {
        $student = Student::withTrashed()->where('roll_number', $roll)->firstOrFail();
        
        // SAFETY CHECK: Prevent conversion if ANY employee profile exists (active or discontinued)
        // This protects existing profiles that may have been manually created or previously converted
        $existingEmployee = \App\Models\Employee::where('roll_number', $roll)->first();
        if ($existingEmployee) {
            $status = $existingEmployee->isActiveEmployee() ? 'active' : 'discontinued';
            return back()->withErrors([
                'error' => "An employee profile with this roll number already exists (Status: {$status}). Cannot convert. Please delete or restore the existing employee profile first if you want to proceed."
            ]);
        }
        
        $validated = $request->validate([
            'category' => 'required|in:academic,non_academic',
        ]);
        
        $studentName = $student->name ?? $roll;
        
        DB::beginTransaction();
        try {
            // Create NEW employee record (transfer all data)
            // Since we checked above, we know no employee exists, so this will always create new
            $employee = new \App\Models\Employee();
            $employee->roll_number = $roll;
            $employee->name = $student->name;
            $employee->father_name = $student->father_name;
            $employee->mobile = $student->parent_phone; // Use primary phone as mobile
            $employee->category = $validated['category'];
            $employee->is_active = true;
            $employee->discontinued_at = null; // Ensure active
            $employee->save();
            
            // Permanently delete the student record (no duplicate, no discontinuation)
            // All related data (punch_logs, manual_attendances, etc.) remains intact as they reference by roll_number
            $student->forceDelete();
            
            DB::commit();
            
            return redirect()->route('employees.show', $roll)
                ->with('success', "Student profile '{$studentName}' has been transformed into an employee profile. All attendance data has been preserved.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to convert student to employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Ensure default course and batch exist for fallback assignments.
     */
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
}

