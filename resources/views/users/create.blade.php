@extends('layouts.app', ['title' => 'Create User'])

@section('content')
<div class="brand-card mb-3">
    <div class="section-title mb-1">
        <i class="bi bi-person-plus"></i> Create New User
    </div>
    <div class="muted">Add a new staff member or admin</div>
</div>

<div class="brand-card">
    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-12">
                <label for="name" class="form-label">
                    <i class="bi bi-person"></i> Full Name *
                </label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope"></i> Email Address *
                </label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                       id="email" name="email" value="{{ old('email') }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="password" class="form-label">
                    <i class="bi bi-lock"></i> Password *
                </label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                       id="password" name="password" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Minimum 8 characters</small>
            </div>

            <div class="col-12">
                <label for="password_confirmation" class="form-label">
                    <i class="bi bi-lock-fill"></i> Confirm Password *
                </label>
                <input type="password" class="form-control" 
                       id="password_confirmation" name="password_confirmation" required>
            </div>

            <div class="col-12">
                <label for="role" class="form-label">
                    <i class="bi bi-shield-check"></i> Role *
                </label>
                <select class="form-select @error('role') is-invalid @enderror" 
                        id="role" name="role" required>
                    <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                </select>
                @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create User
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

