<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    /**
     * Display a listing of courses
     */
    public function index(): View
    {
        $courses = Course::withCount('batches')
            ->orderBy('name')
            ->get();

        // Manually add student count for each course
        foreach ($courses as $course) {
            $course->student_count = $course->students()->count();
        }

        return view('students.courses.index', compact('courses'));
    }

    /**
     * Show the form for creating a new course
     */
    public function create(): View
    {
        return view('students.courses.create');
    }

    /**
     * Store a newly created course
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name',
            'description' => 'nullable|string|max:1000',
        ]);

        Course::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('courses.index')
            ->with('success', 'Course created successfully.');
    }

    /**
     * Show the form for editing the specified course
     */
    public function edit(Course $course): View
    {
        return view('students.courses.edit', compact('course'));
    }

    /**
     * Update the specified course
     */
    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name,' . $course->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $course->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool)$validated['is_active'] : $course->is_active,
        ]);

        return redirect()->route('courses.index')
            ->with('success', 'Course updated successfully.');
    }

    /**
     * Remove the specified course
     */
    public function destroy(Course $course): RedirectResponse
    {
        if (!$course->isDeletable()) {
            return redirect()->route('courses.index')
                ->with('error', 'Cannot delete course. It has students or batches assigned.');
        }

        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Course deleted successfully.');
    }
}
