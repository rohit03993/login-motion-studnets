@extends('layouts.app', ['title' => 'Manage Employees'])

@section('content')
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-people"></i> Manage Employees
            </div>
            <div class="muted">Create and manage employees (academic / non-academic) with login and permissions</div>
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
                <thead class="table-light">
                    <tr>
                        <th>ID / Roll</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Category</th>
                        <th>Login Status</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $emp)
                        @php
                            $hasLogin = $emp->hasLogin();
                            $user = $emp->user;
                            $permissions = $hasLogin ? $user->classPermissions->pluck('class_name')->toArray() : [];
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('employees.show', $emp->roll_number) }}" class="text-decoration-none fw-medium" style="color: var(--brand-accent-2);">
                                    {{ $emp->roll_number }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('employees.show', $emp->roll_number) }}" class="text-decoration-none fw-medium" style="color: var(--brand-accent-2);">
                                    {{ $emp->name }}
                                </a>
                            </td>
                            <td>
                                @if($emp->mobile)
                                    <a href="tel:{{ $emp->mobile }}" class="text-decoration-none">
                                        <i class="bi bi-telephone"></i> {{ $emp->mobile }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary text-dark">
                                    {{ $emp->category === 'academic' ? 'Academic' : 'Non-academic' }}
                                </span>
                            </td>
                            <td>
                                @if($hasLogin)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Has Login
                                    </span>
                                    <br><small class="text-muted">{{ $user->email }}</small>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-x-circle"></i> No Login
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($emp->discontinued_at)
                                    <span class="badge bg-warning">Discontinued</span>
                                    <br><small class="text-muted">{{ \Carbon\Carbon::parse($emp->discontinued_at)->format('M d, Y') }}</small>
                                @elseif($emp->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if(!$hasLogin)
                                        <button type="button" class="btn btn-sm btn-success" onclick="openGenerateLoginModal('{{ $emp->roll_number }}', '{{ addslashes($emp->name) }}')">
                                            <i class="bi bi-key"></i> Generate Login
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-primary" onclick="openPermissionsModal('{{ $emp->roll_number }}', '{{ addslashes($emp->name) }}', {{ json_encode($permissions) }}, {{ $user->can_view_employees ? 'true' : 'false' }})">
                                            <i class="bi bi-shield-lock"></i> Edit Permissions
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

<!-- Generate Login Modal -->
<div class="modal fade" id="generateLoginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Login Credentials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="generateLoginForm">
                <div class="modal-body">
                    <input type="hidden" id="loginEmployeeRoll" name="roll">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" id="loginEmployeeInfo" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" id="loginEmail" name="email" class="form-control" required>
                        <small class="text-muted">Will be used for login</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="text" id="loginPassword" name="password" class="form-control" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-key"></i> Generate Login
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Permissions Modal -->
<div class="modal fade" id="permissionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Permissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="permissionsForm">
                <div class="modal-body">
                    <input type="hidden" id="permissionsEmployeeRoll" name="roll">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" id="permissionsEmployeeName" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="canViewEmployees" name="can_view_employees" value="1">
                            <label class="form-check-label" for="canViewEmployees">
                                Can View Employees
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Class Permissions</label>
                        <div class="text-muted small mb-2">Select classes this employee can view and manage. By default, no classes are selected.</div>
                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            @forelse($courses as $course)
                                <div class="form-check mb-2">
                                    <input class="form-check-input class-permission" type="checkbox" name="classes[]" value="{{ $course->name }}" id="class_{{ $course->id }}">
                                    <label class="form-check-label" for="class_{{ $course->id }}">
                                        {{ $course->name }}
                                    </label>
                                </div>
                            @empty
                                <div class="text-muted">No classes available. Create classes first.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let generateLoginModal, permissionsModal;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap modals
    generateLoginModal = new bootstrap.Modal(document.getElementById('generateLoginModal'));
    permissionsModal = new bootstrap.Modal(document.getElementById('permissionsModal'));

    // Generate Login Form
    document.getElementById('generateLoginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const roll = document.getElementById('loginEmployeeRoll').value;
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';

        fetch(`/employees/${roll}/generate-login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email, password })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const savedRoll = roll;
                generateLoginModal.hide();
                
                // Store roll number in sessionStorage BEFORE reload
                sessionStorage.setItem('openPermissionsFor', savedRoll);
                
                // Show success message and automatically open permissions modal
                alert(`Login credentials generated successfully!\n\nEmail: ${data.data.email}\nPassword: ${data.data.password}\n\nYou will now be asked to set permissions.`);
                
                // Reload page to get updated employee data, then permissions modal will open automatically
                window.location.reload();
            } else {
                alert(data.message || 'Failed to generate login credentials');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-key"></i> Generate Login';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-key"></i> Generate Login';
        });
    });

    // Permissions Form
    document.getElementById('permissionsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const roll = document.getElementById('permissionsEmployeeRoll').value;
        const classes = Array.from(document.querySelectorAll('.class-permission:checked')).map(cb => cb.value);
        const canViewEmployees = document.getElementById('canViewEmployees').checked;
        const submitBtn = this.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        fetch(`/employees/${roll}/update-permissions`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ classes, can_view_employees: canViewEmployees })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Permissions updated successfully!');
                permissionsModal.hide();
                window.location.reload();
            } else {
                alert(data.message || 'Failed to update permissions');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-save"></i> Save Permissions';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-save"></i> Save Permissions';
        });
    });
});

function openGenerateLoginModal(rollNumber, name) {
    document.getElementById('loginEmployeeRoll').value = rollNumber;
    document.getElementById('loginEmployeeInfo').value = `${rollNumber} - ${name}`;
    // Auto-generate email suggestion
    const emailSuggestion = rollNumber.toLowerCase().replace(/[^a-z0-9]/g, '') + '@staff.local';
    document.getElementById('loginEmail').value = emailSuggestion;
    // Auto-generate password
    const password = Math.random().toString(36).slice(-8) + Math.floor(Math.random() * 100);
    document.getElementById('loginPassword').value = password;
    generateLoginModal.show();
}

function openPermissionsModal(rollNumber, name, selectedClasses, canViewEmployees) {
    document.getElementById('permissionsEmployeeRoll').value = rollNumber;
    document.getElementById('permissionsEmployeeName').value = name;
    document.getElementById('canViewEmployees').checked = canViewEmployees;
    
    // Reset all checkboxes
    document.querySelectorAll('.class-permission').forEach(cb => cb.checked = false);
    
    // Set selected classes
    if (selectedClasses && selectedClasses.length > 0) {
        selectedClasses.forEach(className => {
            const checkbox = document.querySelector(`.class-permission[value="${className}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    permissionsModal.show();
}

// Check if we need to open permissions modal after page load (after login generation)
window.addEventListener('load', function() {
    const openForRoll = sessionStorage.getItem('openPermissionsFor');
    if (openForRoll) {
        sessionStorage.removeItem('openPermissionsFor');
        // Find the employee row and open permissions modal
        setTimeout(function() {
            const rows = document.querySelectorAll('tbody tr');
            for (let row of rows) {
                const rollCell = row.querySelector('td:first-child a');
                if (rollCell && rollCell.textContent.trim() === openForRoll) {
                    const nameCell = row.querySelector('td:nth-child(2) a');
                    const name = nameCell ? nameCell.textContent.trim() : '';
                    // Open permissions modal with empty permissions (new user)
                    openPermissionsModal(openForRoll, name, [], false);
                    break;
                }
            }
        }, 300);
    }
});
</script>
@endpush
@endsection
