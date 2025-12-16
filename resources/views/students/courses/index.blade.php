@extends('layouts.app', ['title' => 'Manage Classes'])

@section('content')
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-book"></i> Manage Classes
            </div>
            <div class="muted">Create and manage student classes/courses</div>
        </div>
        <div>
            <a href="{{ route('courses.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New Class
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="brand-card">
    @if($courses->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-book" style="font-size: 3rem;"></i>
            <p class="mt-3 mb-0">No classes found. Create your first class to get started.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Students</th>
                        <th>Batches</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $course)
                        <tr>
                            <td class="fw-medium">{{ $course->name }}</td>
                            <td class="text-muted">{{ $course->description ?? '—' }}</td>
                            <td>
                                <span class="badge bg-info">{{ $course->student_count ?? 0 }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $course->batches_count ?? 0 }}</span>
                            </td>
                            <td>
                                @if($course->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('courses.edit', $course) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    @if($course->isDeletable())
                                        <form method="POST" action="{{ route('courses.destroy', $course) }}" 
                                              onsubmit="return confirm('Are you sure you want to delete this class?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" disabled 
                                                title="Cannot delete: Has students or batches assigned">
                                            <i class="bi bi-lock"></i> Locked
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

