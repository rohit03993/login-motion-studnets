@extends('layouts.app', ['title' => 'Students List'])

@section('content')
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-people"></i> All Students
            </div>
            <div class="muted">Complete list of all registered students</div>
        </div>
    </div>
</div>

<div class="brand-card mb-3">
    <form method="GET" action="{{ route('students.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
            <label class="form-label"><i class="bi bi-search"></i> Search</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" 
                   placeholder="Search by roll number, name, father's name, or phone...">
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label"><i class="bi bi-funnel"></i> Filter by Batch</label>
            <select name="batch" class="form-select">
                <option value="">All Batches</option>
                @if($hasNoBatch)
                    <option value="__no_batch__" {{ $batch === '__no_batch__' ? 'selected' : '' }}>
                        (No Batch / Unassigned)
                    </option>
                @endif
                @foreach($batches as $batchOption)
                    <option value="{{ $batchOption }}" {{ $batch === $batchOption ? 'selected' : '' }}>
                        {{ $batchOption }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search"></i> Search
            </button>
        </div>
        @if($search || $batch)
            <div class="col-12">
                <a href="{{ route('students.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        @endif
    </form>
</div>

<div class="brand-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title mb-0"><i class="bi bi-list-ul"></i> Students List</div>
        <div class="text-muted small">{{ $students->total() }} total students</div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Roll Number / ID</th>
                    <th>Name</th>
                    <th>Father's Name</th>
                    <th>Parent Mobile</th>
                    <th>Last Punch</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $student->roll_number }}</div>
                            @if($student->class_course)
                                <small class="text-muted">{{ $student->class_course }}</small>
                            @endif
                        </td>
                        <td>
                            @if($student->name)
                                <div>{{ $student->name }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                            @if($student->batch)
                                <small class="text-muted">Batch: {{ $student->batch }}</small>
                            @endif
                        </td>
                        <td>
                            @if($student->father_name)
                                {{ $student->father_name }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($student->parent_phone)
                                <a href="tel:{{ $student->parent_phone }}" class="text-decoration-none">
                                    {{ $student->parent_phone }}
                                </a>
                                @if($student->alerts_enabled)
                                    <br><small class="badge bg-success" style="font-size: 0.7rem;">
                                        <i class="bi bi-bell"></i> Alerts ON
                                    </small>
                                @else
                                    <br><small class="badge bg-secondary" style="font-size: 0.7rem;">
                                        <i class="bi bi-bell-slash"></i> Alerts OFF
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($student->last_punch_date)
                                <div>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($student->last_punch_date)->format('M d, Y') }}</small>
                                </div>
                                @if($student->last_punch_time)
                                    <small class="text-muted">{{ $student->last_punch_time }}</small>
                                @endif
                            @else
                                <span class="text-muted">No punches yet</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('students.show', $student->roll_number) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <div class="mt-2">No students found</div>
                            @if($search)
                                <div class="mt-2">
                                    <a href="{{ route('students.index') }}" class="btn btn-sm btn-outline-primary">
                                        View All Students
                                    </a>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($students->hasPages())
        <div class="mt-3">
            {{ $students->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

