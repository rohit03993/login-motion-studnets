@extends('layouts.app', ['title' => 'Change Password'])

@section('content')
<div class="brand-card mb-3">
    <div class="section-title mb-1">
        <i class="bi bi-key"></i> Change Password
    </div>
    <div class="muted">Update your account password</div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="brand-card">
    <form method="POST" action="{{ route('profile.change-password') }}">
        @csrf

        <div class="row g-3">
            <div class="col-12">
                <label for="current_password" class="form-label">
                    <i class="bi bi-lock"></i> Current Password *
                </label>
                <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                       id="current_password" name="current_password" required autofocus>
                @error('current_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="password" class="form-label">
                    <i class="bi bi-lock-fill"></i> New Password *
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
                    <i class="bi bi-lock-fill"></i> Confirm New Password *
                </label>
                <input type="password" class="form-control" 
                       id="password_confirmation" name="password_confirmation" required>
            </div>

            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Password
                    </button>
                    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

