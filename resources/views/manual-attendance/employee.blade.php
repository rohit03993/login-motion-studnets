@extends('layouts.app', ['title' => 'Employee Manual Attendance'])

@section('content')
<style>
    .filters-card-modern {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        border: 1px solid #bae6fd;
        box-shadow: 0 4px 20px rgba(14, 165, 233, 0.1);
        border-radius: 16px;
        transition: all 0.3s ease;
    }
    .filters-card-modern:hover {
        box-shadow: 0 6px 25px rgba(14, 165, 233, 0.15);
        background: linear-gradient(135deg, #e0f2fe, #dbeafe);
    }
    .filter-input-modern {
        border: 2px solid #bae6fd;
        border-radius: 10px;
        transition: all 0.3s ease;
        background: #ffffff;
    }
    .filter-input-modern:focus {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        outline: none;
        background: #ffffff;
    }
    .filter-select-modern {
        border: 2px solid #bae6fd;
        border-radius: 10px;
        transition: all 0.3s ease;
        background: #ffffff;
    }
    .filter-select-modern:focus {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        outline: none;
        background: #ffffff;
    }
    .load-btn-modern {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border: none;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }
    .load-btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        background: linear-gradient(135deg, #dc2626, #b91c1c);
    }
    .reset-btn-modern {
        background: linear-gradient(135deg, #64748b, #475569);
        border: none;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(100, 116, 139, 0.3);
    }
    .reset-btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
        background: linear-gradient(135deg, #475569, #334155);
    }
    .filter-label-modern {
        color: #475569;
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 6px;
        display: block;
    }
</style>
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-pencil-square"></i> Employee Manual Attendance Marking
            </div>
            <div class="muted">Mark employees as present or absent for a specific date</div>
        </div>
    </div>
</div>

<div class="brand-card mb-3 filters-card-modern">
    <div class="section-title mb-3"><i class="bi bi-funnel"></i> Filters</div>
    <form method="GET" action="{{ route('manual-attendance.employee.index') }}" class="row g-3">
        <div class="col-12 col-md-2">
            <label class="filter-label-modern"><i class="bi bi-person-badge"></i> Roll Number</label>
            <input type="text" 
                   name="roll" 
                   id="employeeAttendanceRoll" 
                   value="{{ $rollFilter ?? '' }}" 
                   class="form-control filter-input-modern" 
                   placeholder="Enter roll number" 
                   autocomplete="off"
                   data-lpignore="true">
        </div>
        <div class="col-12 col-md-2">
            <label class="filter-label-modern"><i class="bi bi-person"></i> Name</label>
            <input type="text" 
                   name="name" 
                   id="employeeAttendanceName" 
                   value="{{ $nameFilter ?? '' }}" 
                   class="form-control filter-input-modern" 
                   placeholder="Enter name" 
                   autocomplete="off"
                   data-lpignore="true">
        </div>
        <div class="col-12 col-md-2">
            <label class="filter-label-modern"><i class="bi bi-briefcase"></i> Category</label>
            <select name="category" class="form-select filter-select-modern">
                <option value="">All Categories</option>
                <option value="academic" {{ ($categoryFilter ?? '') === 'academic' ? 'selected' : '' }}>Academic</option>
                <option value="non_academic" {{ ($categoryFilter ?? '') === 'non_academic' ? 'selected' : '' }}>Non-academic</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="filter-label-modern"><i class="bi bi-calendar"></i> Date</label>
            <input type="date" name="date" value="{{ $date }}" class="form-control filter-input-modern" required>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2 align-items-end">
            <button type="submit" class="btn load-btn-modern btn-sm flex-fill">
                <i class="bi bi-search"></i> Load
            </button>
            @if(($rollFilter ?? '') || ($nameFilter ?? '') || ($categoryFilter ?? ''))
                <a href="{{ route('manual-attendance.employee.index', ['date' => $date]) }}" class="btn reset-btn-modern btn-sm flex-fill">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            @endif
        </div>
    </form>
</div>

@if($date)
    <!-- Employees Summary Cards -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="brand-card text-center" style="background: linear-gradient(135deg, #fff7ed, #ffedd5); border: 2px solid #fb923c;">
                <div class="muted small mb-1"><i class="bi bi-box-arrow-in-right"></i> IN</div>
                <div class="stat-value" style="font-size: 2.5rem; font-weight: bold; color: #ea580c;">{{ $statsIn ?? 0 }}</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="brand-card text-center" style="background: linear-gradient(135deg, #fdf2f8, #fce7f3); border: 2px solid #ec4899;">
                <div class="muted small mb-1"><i class="bi bi-box-arrow-right"></i> OUT</div>
                <div class="stat-value" style="font-size: 2.5rem; font-weight: bold; color: #db2777;">{{ $statsOut ?? 0 }}</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="brand-card text-center" style="background: linear-gradient(135deg, #f3e8ff, #e9d5ff); border: 2px solid #a855f7;">
                <div class="muted small mb-1"><i class="bi bi-people"></i> TOTAL</div>
                <div class="stat-value" style="font-size: 2.5rem; font-weight: bold; color: #9333ea;">{{ $statsTotal ?? 0 }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Present Employees -->
        <div class="col-12 col-lg-6">
            <div class="brand-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-check-circle text-success"></i> Present Employees
                    </div>
                    <span class="badge bg-success">{{ count($presentEmployees) }}</span>
                </div>
                @if(count($presentEmployees) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Roll</th>
                                    <th>IN Time</th>
                                    <th>OUT Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($presentEmployees as $item)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $item['employee']->roll_number) }}" class="text-decoration-none">
                                                {{ $item['employee']->name ?? 'N/A' }}
                                            </a>
                                            <div>
                                                @if($item['employee']->category === 'academic')
                                                    <span class="badge bg-primary" style="font-size: 0.7rem;">Academic</span>
                                                @elseif($item['employee']->category === 'non_academic')
                                                    <span class="badge bg-secondary" style="font-size: 0.7rem;">Non-academic</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <code>{{ $item['employee']->roll_number }}</code>
                                        </td>
                                        <td>
                                            <div>
                                                {{ $item['in_time'] ?? 'N/A' }}
                                                @if($item['is_manual'])
                                                    <span class="badge bg-warning text-dark ms-1" title="Manually marked{{ $item['marked_by_in'] ? ' by ' . $item['marked_by_in']->name : '' }}">
                                                        <i class="bi bi-pencil"></i> Manual
                                                        @if($item['marked_by_in'])
                                                            <small class="d-block mt-1" style="font-size: 0.7rem; color: #666;">
                                                                by {{ $item['marked_by_in']->name }}
                                                            </small>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                            {{ $item['out_time'] ?? '-' }}
                                                @if(($item['out_time'] ?? null) && !empty($item['is_manual_out']))
                                                    <span class="badge bg-warning text-dark ms-1" title="Manually marked OUT{{ $item['marked_by_out'] ? ' by ' . $item['marked_by_out']->name : '' }}">
                                                        <i class="bi bi-pencil"></i> Manual
                                                        @if($item['marked_by_out'])
                                                            <small class="d-block mt-1" style="font-size: 0.7rem; color: #666;">
                                                                by {{ $item['marked_by_out']->name }}
                                                            </small>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                            @if(!$item['has_out'])
                                                <button type="button" class="btn btn-danger btn-sm mt-1 mark-out-btn-employee" 
                                                        data-roll="{{ $item['employee']->roll_number }}" 
                                                        data-date="{{ $date }}">
                                                    <i class="bi bi-box-arrow-right"></i> Mark OUT
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('employees.show', $item['employee']->roll_number) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">No present employees found</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Absent Employees -->
        <div class="col-12 col-lg-6">
            <div class="brand-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-x-circle text-danger"></i> Absent Employees
                    </div>
                    <span class="badge bg-danger">{{ count($absentEmployees) }}</span>
                </div>
                @if(count($absentEmployees) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Roll</th>
                                    <th>Category</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($absentEmployees as $item)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $item['employee']->roll_number) }}" class="text-decoration-none">
                                                {{ $item['employee']->name ?? 'N/A' }}
                                            </a>
                                        </td>
                                        <td>
                                            <code>{{ $item['employee']->roll_number }}</code>
                                        </td>
                                        <td>
                                            @if($item['employee']->category === 'academic')
                                                <span class="badge bg-primary">Academic</span>
                                            @elseif($item['employee']->category === 'non_academic')
                                                <span class="badge bg-secondary">Non-academic</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-success btn-sm mark-in-btn-employee" 
                                                    data-roll="{{ $item['employee']->roll_number }}" 
                                                    data-date="{{ $date }}">
                                                <i class="bi bi-box-arrow-in-right"></i> Mark IN
                                            </button>
                                            <a href="{{ route('employees.show', $item['employee']->roll_number) }}" class="btn btn-outline-primary btn-sm ms-1">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">All employees are present!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="brand-card">
        <div class="text-center text-muted py-5">
            <i class="bi bi-calendar-check" style="font-size: 3rem;"></i>
            <p class="mt-3 mb-0">Please select a date to view employee attendance</p>
        </div>
    </div>
@endif

<!-- Time Input Modal (Same style as students) -->
<div class="modal fade" id="timeInputModal" tabindex="-1" aria-labelledby="timeInputModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timeInputModalLabel">Enter Time</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="timeInputForm">
                    <div class="mb-3">
                        <label for="punchTime" class="form-label">
                            <i class="bi bi-clock"></i> Time (HH:MM format)
                        </label>
                        <input type="time" class="form-control" id="punchTime" name="punchTime" required>
                        <div class="form-text">Enter the time for this manual attendance entry</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmTimeBtn">
                    <i class="bi bi-check-circle"></i> Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11;">
    <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto" id="toast-title">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toast-message"></div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toastEl = document.getElementById('toast');
    const toastTitle = document.getElementById('toast-title');
    const toastMessage = document.getElementById('toast-message');
    const toast = new bootstrap.Toast(toastEl);
    
    // Time Input Modal
    const timeInputModal = new bootstrap.Modal(document.getElementById('timeInputModal'));
    const punchTimeInput = document.getElementById('punchTime');
    const confirmTimeBtn = document.getElementById('confirmTimeBtn');
    const timeInputForm = document.getElementById('timeInputForm');
    
    let pendingAction = null;
    let pendingRoll = null;
    let pendingDate = null;
    
    function showToast(title, message, type = 'success') {
        toastTitle.textContent = title;
        toastMessage.textContent = message;
        toastEl.className = 'toast';
        if (type === 'success') {
            toastEl.classList.add('text-bg-success');
        } else {
            toastEl.classList.add('text-bg-danger');
        }
        toast.show();
    }
    
    function submitEmployeeAttendance(action, rollNumber, date, time) {
        const url = action === 'present' 
            ? '{{ route("manual-attendance.employee.mark-present") }}'
            : '{{ route("manual-attendance.employee.mark-out") }}';
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                roll_number: rollNumber,
                date: date,
                time: time
            })
        })
        .then(response => response.json())
        .then(data => {
            timeInputModal.hide();
            if (data.success) {
                showToast('Success', data.message, 'success');
                // Reload page after 1 second to show updated attendance
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('Error', data.message || 'Failed to mark attendance', 'error');
            }
        })
        .catch(error => {
            timeInputModal.hide();
            showToast('Error', 'An error occurred. Please try again.', 'error');
            console.error('Error:', error);
        });
    }
    
    // Handle Mark IN buttons
    document.querySelectorAll('.mark-in-btn-employee').forEach(btn => {
        btn.addEventListener('click', function() {
            pendingAction = 'present';
            pendingRoll = this.getAttribute('data-roll');
            pendingDate = this.getAttribute('data-date');
            
            // Set default time to current time
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            punchTimeInput.value = `${hours}:${minutes}`;
            
            timeInputModal.show();
        });
    });
    
    // Handle Mark OUT buttons
    document.querySelectorAll('.mark-out-btn-employee').forEach(btn => {
        btn.addEventListener('click', function() {
            pendingAction = 'out';
            pendingRoll = this.getAttribute('data-roll');
            pendingDate = this.getAttribute('data-date');
            
            // Set default time to current time
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            punchTimeInput.value = `${hours}:${minutes}`;
            
            timeInputModal.show();
        });
    });
    
    // Handle confirm button
    confirmTimeBtn.addEventListener('click', function() {
        if (!punchTimeInput.value) {
            showToast('Error', 'Please enter a time', 'error');
            return;
        }
        
        if (pendingAction && pendingRoll && pendingDate) {
            const timeValue = punchTimeInput.value;
            submitEmployeeAttendance(pendingAction, pendingRoll, pendingDate, timeValue);
        }
    });
    
    // Reset modal when closed
    document.getElementById('timeInputModal').addEventListener('hidden.bs.modal', function() {
        timeInputForm.reset();
        pendingAction = null;
        pendingRoll = null;
        pendingDate = null;
    });
});
</script>
@endpush

@endsection

