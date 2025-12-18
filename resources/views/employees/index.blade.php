@extends('layouts.app', ['title' => 'Manage Employees'])

@section('content')
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-people"></i> Manage Employees
            </div>
            <div class="muted">Create and manage employees (academic / non-academic)</div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle"></i> Please fix the errors below.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="brand-card mb-3">
    <form method="POST" action="{{ route('employees.store') }}" class="row g-2">
        @csrf
        <div class="col-12 col-md-3">
            <label class="form-label">ID / Roll *</label>
            <input type="text" name="roll_number" class="form-control @error('roll_number') is-invalid @enderror" required>
            @error('roll_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Father's Name</label>
            <input type="text" name="father_name" class="form-control @error('father_name') is-invalid @enderror">
            @error('father_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Mobile</label>
            <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" placeholder="+91XXXXXXXXXX or 10-digit">
            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Category *</label>
            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                <option value="academic">Academic</option>
                <option value="non_academic">Non-academic</option>
            </select>
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-plus-circle"></i> Create
            </button>
        </div>
    </form>
</div>

<div class="brand-card">
    @if($employees->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-inboxes" style="font-size: 3rem;"></i>
            <p class="mt-3 mb-0">No employees yet.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID / Roll</th>
                        <th>Name</th>
                        <th>Father</th>
                        <th>Mobile</th>
                        <th>Category</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $emp)
                        <tr>
                            <td class="fw-medium">{{ $emp->roll_number }}</td>
                            <td>{{ $emp->name }}</td>
                            <td>{{ $emp->father_name ?? '—' }}</td>
                            <td>{{ $emp->mobile ?? '—' }}</td>
                            <td>
                                <span class="badge bg-secondary text-dark">
                                    {{ $emp->category === 'academic' ? 'Academic' : 'Non-academic' }}
                                </span>
                            </td>
                            <td>
                                @if($emp->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
