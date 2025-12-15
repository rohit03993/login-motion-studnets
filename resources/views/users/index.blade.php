@extends('layouts.app', ['title' => 'User Management'])

@section('content')
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-people"></i> User Management
            </div>
            <div class="muted">Manage staff and admin users</div>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Add New User
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="brand-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $user->name }}</div>
                            @if($user->id === auth()->id())
                                <small class="text-muted">(You)</small>
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->isSuperAdmin())
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-shield-check"></i> Super Admin
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="bi bi-person"></i> Staff
                                </span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ $user->created_at->format('M d, Y') }}</small>
                            @if($user->password_changed_at)
                                <br><small class="text-info">
                                    <i class="bi bi-key"></i> Password changed: {{ $user->password_changed_at->format('M d, Y H:i') }}
                                </small>
                            @else
                                <br><small class="text-muted">
                                    <i class="bi bi-key"></i> Default password
                                </small>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <div class="mt-2">No users found</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="mt-3">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

