@extends('layouts.app', ['title' => 'Staff Permissions'])

@section('content')
<div class="brand-card mb-3">
    <div class="section-title mb-1"><i class="bi bi-shield-lock"></i> Staff Permissions</div>
    <div class="muted">Assign class visibility (and mark IN/OUT) and employee access.</div>
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

<div class="brand-card">
    <form method="POST" action="{{ route('permissions.update') }}" class="row g-3">
        @csrf
        <div class="col-12 col-md-4">
            <label class="form-label">Staff User *</label>
            <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                <option value="">Select user</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-5">
            <label class="form-label">Allowed Classes (view + mark)</label>
            <select name="classes[]" class="form-select" multiple size="6">
                @foreach($courses as $course)
                    <option value="{{ $course->name }}">{{ $course->name }}</option>
                @endforeach
            </select>
            <div class="form-text">Hold Ctrl/Cmd to select multiple.</div>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label d-block">Employee Attendance</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="can_view_employees" id="canViewEmployees" value="1">
                <label class="form-check-label" for="canViewEmployees">
                    Can view/mark employee attendance
                </label>
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Save Permissions
            </button>
        </div>
    </form>
</div>

<div class="brand-card mt-3">
    <div class="section-title mb-2"><i class="bi bi-list-check"></i> Current Permissions</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Classes</th>
                    <th>Employee Attendance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($permissionList as $u)
                    <tr>
                        <td>{{ $u->name }} ({{ $u->email }})</td>
                        <td>{{ $u->role }}</td>
                        <td>
                            @php $cls = $u->classPermissions->pluck('class_name')->unique()->values(); @endphp
                            {{ $cls->isEmpty() ? 'None' : $cls->implode(', ') }}
                        </td>
                        <td>
                            @if($u->can_view_employees)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
