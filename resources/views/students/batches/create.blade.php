@extends('layouts.app', ['title' => 'Create Batch'])

@section('content')
<div class="brand-card mb-3">
    <div class="section-title mb-1">
        <i class="bi bi-plus-circle"></i> Create New Batch
    </div>
    <div class="muted">Add a new batch to the system</div>
</div>

<div class="brand-card">
    <form method="POST" action="{{ route('batches.store') }}">
        @csrf
        
        <div class="row g-3">
            <div class="col-12">
                <label for="name" class="form-label">
                    <i class="bi bi-diagram-3"></i> Batch Name *
                </label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">e.g., "25H1AG", "25P1AG"</div>
            </div>

            <div class="col-12">
                <label for="course_id" class="form-label">
                    <i class="bi bi-book"></i> Class (Optional)
                </label>
                <select class="form-select @error('course_id') is-invalid @enderror" 
                        id="course_id" name="course_id">
                    <option value="">No Class</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
                @error('course_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Optionally link this batch to a class</div>
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

            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Create Batch
                    </button>
                    <a href="{{ route('batches.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

