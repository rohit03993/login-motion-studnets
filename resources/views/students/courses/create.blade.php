@extends('layouts.app', ['title' => 'Create Class'])

@section('content')
<div class="brand-card mb-3">
    <div class="section-title mb-1">
        <i class="bi bi-plus-circle"></i> Create New Class
    </div>
    <div class="muted">Add a new class/course to the system</div>
</div>

<div class="brand-card">
    <form method="POST" action="{{ route('courses.store') }}">
        @csrf
        
        <div class="row g-3">
            <div class="col-12">
                <label for="name" class="form-label">
                    <i class="bi bi-book"></i> Class Name *
                </label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">e.g., "11", "12", "Foundation"</div>
            </div>

            <div class="col-12">
                <label for="description" class="form-label">
                    <i class="bi bi-text-paragraph"></i> Description
                </label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="batch_name" class="form-label">
                    <i class="bi bi-diagram-3"></i> Batch Name *
                </label>
                <input type="text" class="form-control @error('batch_name') is-invalid @enderror" 
                       id="batch_name" name="batch_name" value="{{ old('batch_name') }}" required>
                @error('batch_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">A default batch will be created with this class.</div>
            </div>

            <div class="col-12 col-md-6">
                <label for="batch_description" class="form-label">
                    <i class="bi bi-text-paragraph"></i> Batch Description
                </label>
                <input type="text" class="form-control @error('batch_description') is-invalid @enderror" 
                       id="batch_description" name="batch_description" value="{{ old('batch_description') }}">
                @error('batch_description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Create Class
                    </button>
                    <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

