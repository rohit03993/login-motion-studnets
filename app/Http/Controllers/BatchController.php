<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchController extends Controller
{
    /**
     * Display a listing of batches
     */
    public function index(Request $request): View
    {
        $query = Batch::with('course');

        // Filter by course if provided
        if ($request->has('course_id') && $request->course_id) {
            $query->where('course_id', $request->course_id);
        }

        $batches = $query->orderBy('name')->get();

        // Manually add student count
        foreach ($batches as $batch) {
            $batch->student_count = $batch->students()->count();
        }

        $courses = Course::where('is_active', true)->orderBy('name')->get();

        return view('students.batches.index', compact('batches', 'courses'));
    }

    /**
     * Show the form for creating a new batch
     */
    public function create(): View
    {
        $courses = Course::where('is_active', true)->orderBy('name')->get();
        return view('students.batches.create', compact('courses'));
    }

    /**
     * Store a newly created batch
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:batches,name',
            'course_id' => 'nullable|exists:courses,id',
            'description' => 'nullable|string|max:1000',
        ]);

        Batch::create([
            'name' => $validated['name'],
            'course_id' => $validated['course_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('batches.index')
            ->with('success', 'Batch created successfully.');
    }

    /**
     * Show the form for editing the specified batch
     */
    public function edit(Batch $batch): View
    {
        $courses = Course::where('is_active', true)->orderBy('name')->get();
        return view('students.batches.edit', compact('batch', 'courses'));
    }

    /**
     * Update the specified batch
     */
    public function update(Request $request, Batch $batch): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:batches,name,' . $batch->id,
            'course_id' => 'nullable|exists:courses,id',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $batch->update([
            'name' => $validated['name'],
            'course_id' => $validated['course_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool)$validated['is_active'] : $batch->is_active,
        ]);

        return redirect()->route('batches.index')
            ->with('success', 'Batch updated successfully.');
    }

    /**
     * Remove the specified batch
     */
    public function destroy(Batch $batch): RedirectResponse
    {
        if (!$batch->isDeletable()) {
            return redirect()->route('batches.index')
                ->with('error', 'Cannot delete batch. It has students assigned.');
        }

        $batch->delete();

        return redirect()->route('batches.index')
            ->with('success', 'Batch deleted successfully.');
    }
}
