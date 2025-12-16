@extends('layouts.app', ['title' => 'Edit Class'])

@section('content')
<div class="brand-card mb-3">
    <div class="section-title mb-1">
        <i class="bi bi-pencil"></i> Edit Class
    </div>
    <div class="muted">Update class information</div>
</div>

<div class="brand-card">
    <form method="POST" action="{{ route('courses.update', $course) }}">
        @csrf
        @method('PUT')
        
        <div class="row g-3">
            <div class="col-12">
                <label for="name" class="form-label">
                    <i class="bi bi-book"></i> Class Name *
                </label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name', $course->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="description" class="form-label">
                    <i class="bi bi-text-paragraph"></i> Description
                </label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="3">{{ old('description', $course->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="is_active" 
                           name="is_active" {{ old('is_active', $course->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Active (uncheck to disable this class)
                    </label>
                </div>
            </div>

            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Class
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

