@extends('layouts.app', ['title' => 'Manage Batches'])

@section('content')
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-diagram-3"></i> Manage Batches
            </div>
            <div class="muted">Create and manage student batches</div>
        </div>
        <div>
            <a href="{{ route('batches.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New Batch
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

<div class="brand-card mb-3">
    <form method="GET" action="{{ route('batches.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label"><i class="bi bi-funnel"></i> Filter by Class</label>
            <select name="course_id" class="form-select" onchange="this.form.submit()">
                <option value="">All Classes</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                        {{ $course->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @if(request('course_id'))
            <div class="col-12 col-md-2">
                <a href="{{ route('batches.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        @endif
    </form>
</div>

<div class="brand-card">
    @if($batches->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-diagram-3" style="font-size: 3rem;"></i>
            <p class="mt-3 mb-0">No batches found. Create your first batch to get started.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Description</th>
                        <th>Students</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batches as $batch)
                        <tr>
                            <td class="fw-medium">{{ $batch->name }}</td>
                            <td>
                                @if($batch->course)
                                    <span class="badge bg-info">{{ $batch->course->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $batch->description ?? '—' }}</td>
                            <td>
                                <span class="badge bg-primary">{{ $batch->student_count ?? 0 }}</span>
                            </td>
                            <td>
                                @if($batch->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('batches.edit', $batch) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    @if($batch->isDeletable())
                                        <form method="POST" action="{{ route('batches.destroy', $batch) }}" 
                                              onsubmit="return confirm('Are you sure you want to delete this batch?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" disabled 
                                                title="Cannot delete: Has students assigned">
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

