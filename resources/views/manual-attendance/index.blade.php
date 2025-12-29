@extends('layouts.app', ['title' => 'Manual Attendance'])

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
                <i class="bi bi-pencil-square"></i> Manual Attendance Marking
            </div>
            <div class="muted">Mark students as present or absent for a specific class and date</div>
        </div>
    </div>
</div>

<div class="brand-card mb-3 filters-card-modern">
    <div class="section-title mb-3"><i class="bi bi-funnel"></i> Filters</div>
    <form method="GET" action="{{ route('manual-attendance.index') }}" class="row g-3">
        <div class="col-12 col-md-2">
            <label class="filter-label-modern"><i class="bi bi-person-badge"></i> Roll Number</label>
            <input type="text" 
                   name="roll" 
                   id="manualAttendanceRoll" 
                   value="{{ request()->has('roll') && request('roll') !== '' ? request('roll') : '' }}" 
                   class="form-control filter-input-modern" 
                   placeholder="Enter roll number" 
                   autocomplete="off"
                   data-lpignore="true">
        </div>
        <div class="col-12 col-md-2">
            <label class="filter-label-modern"><i class="bi bi-person"></i> Name</label>
            <input type="text" 
                   name="name" 
                   id="manualAttendanceName" 
                   value="{{ request()->has('name') && request('name') !== '' ? request('name') : '' }}" 
                   class="form-control filter-input-modern" 
                   placeholder="Enter name" 
                   autocomplete="off"
                   data-lpignore="true">
        </div>
        <div class="col-12 col-md-3">
            <label class="filter-label-modern"><i class="bi bi-calendar"></i> Date</label>
            <input type="date" name="date" value="{{ $date }}" class="form-control filter-input-modern" required>
        </div>
        <div class="col-12 col-md-3">
            <label class="filter-label-modern"><i class="bi bi-book"></i> Class</label>
            <select name="class" class="form-select filter-select-modern">
                <option value="ALL" {{ $classCourse === 'ALL' ? 'selected' : '' }}>All Classes</option>
                @if($hasNoClass)
                    <option value="__no_class__" {{ $classCourse === '__no_class__' ? 'selected' : '' }}>
                        (No Class / Unassigned)
                    </option>
                @endif
                @foreach((array)$classes as $cls)
                    <option value="{{ $cls }}" {{ $classCourse === $cls ? 'selected' : '' }}>
                        {{ $cls }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2 align-items-end">
            <button type="submit" class="btn load-btn-modern btn-sm flex-fill">
                <i class="bi bi-search"></i> Load
            </button>
            @if(request('roll') || request('name'))
                <a href="{{ route('manual-attendance.index', ['date' => $date, 'class' => $classCourse]) }}" class="btn reset-btn-modern btn-sm flex-fill">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            @endif
        </div>
    </form>
</div>

@if($classCourse && $date)
    <div class="row g-3">
        <!-- Present Students -->
        <div class="col-12 col-lg-6">
            <div class="brand-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-check-circle-fill text-success"></i> Present Students
                    </div>
                    <span class="badge bg-success">{{ $presentStudents->count() }}</span>
                </div>
                
                @if($presentStudents->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">No students marked as present</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Roll No.</th>
                                    <th>Name</th>
                                    <th>IN Time</th>
                                    <th>OUT Time</th>
                                    <th>WhatsApp</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($presentStudents as $item)
                                    <tr>
                                        <td class="fw-medium">
                                            <a href="{{ route('students.show', $item['student']->roll_number) }}" class="text-decoration-none fw-medium" style="color: var(--brand-accent-2);">
                                                {{ $item['student']->roll_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('students.show', $item['student']->roll_number) }}" class="text-decoration-none fw-medium" style="color: var(--brand-accent-2);">
                                                {{ $item['student']->name ?? 'N/A' }}
                                            </a>
                                        </td>
                                        <td>
                                            <div>
                                                {{ $item['in_time'] ?? 'N/A' }}
                                                @if($item['is_manual'])
                                                    <span class="badge bg-warning text-dark ms-1" title="Manually marked">
                                                        <i class="bi bi-pencil"></i> Manual
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                            {{ $item['out_time'] ?? '-' }}
                                                @if(($item['out_time'] ?? null) && !empty($item['is_manual_out']))
                                                    <span class="badge bg-warning text-dark ms-1" title="Manually marked OUT">
                                                        <i class="bi bi-pencil"></i> Manual
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                @if($item['whatsapp_in'])
                                                    <span class="badge {{ $item['whatsapp_in']['status'] === 'success' ? 'bg-success' : 'bg-danger' }}" title="{{ $item['whatsapp_in']['error'] ?? 'Sent' }}">
                                                        IN: {{ $item['whatsapp_in']['status'] === 'success' ? 'Sent' : 'Failed' }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">IN: Not sent</span>
                                                @endif
                                                @if($item['out_time'] && $item['whatsapp_out'])
                                                    <span class="badge {{ $item['whatsapp_out']['status'] === 'success' ? 'bg-success' : 'bg-danger' }}" title="{{ $item['whatsapp_out']['error'] ?? 'Sent' }}">
                                                        OUT: {{ $item['whatsapp_out']['status'] === 'success' ? 'Sent' : 'Failed' }}
                                                    </span>
                                                @elseif($item['out_time'])
                                                    <span class="badge bg-secondary">OUT: Not sent</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if(!$item['has_out'])
                                                <button class="btn btn-sm btn-outline-danger mark-out-btn" 
                                                        data-roll="{{ $item['student']->roll_number }}"
                                                        data-date="{{ $date }}"
                                                        data-in-time="{{ $item['in_time'] ?? '' }}">
                                                    <i class="bi bi-box-arrow-right"></i> Mark Out
                                                </button>
                                            @else
                                                <span class="text-muted small">Already out</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Absent Students -->
        <div class="col-12 col-lg-6">
            <div class="brand-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0">
                        <i class="bi bi-x-circle-fill text-danger"></i> Absent Students
                    </div>
                    <span class="badge bg-danger">{{ $absentStudents->count() }}</span>
                </div>
                
                @if($absentStudents->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">All students are present!</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Roll No.</th>
                                    <th>Name</th>
                                    <th>Parent Phone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($absentStudents as $item)
                                    <tr>
                                        <td class="fw-medium">
                                            <a href="{{ route('students.show', $item['student']->roll_number) }}" class="text-decoration-none fw-medium" style="color: var(--brand-accent-2);">
                                                {{ $item['student']->roll_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('students.show', $item['student']->roll_number) }}" class="text-decoration-none fw-medium" style="color: var(--brand-accent-2);">
                                                {{ $item['student']->name ?? 'N/A' }}
                                            </a>
                                        </td>
                                        <td>{{ $item['student']->parent_phone ?? 'N/A' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-success mark-present-btn" 
                                                    data-roll="{{ $item['student']->roll_number }}"
                                                    data-date="{{ $date }}">
                                                <i class="bi bi-check-circle"></i> Mark Present
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="brand-card">
        <div class="text-center text-muted py-5">
            <i class="bi bi-calendar-check" style="font-size: 3rem;"></i>
            <p class="mt-3 mb-0">Please select a class (or All) and date to view attendance</p>
        </div>
    </div>
@endif

<!-- Time Input Modal -->
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
    
    let pendingAction = null; // Store the action to execute after time input
    
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
    
    function submitAttendance(action, rollNumber, date, time) {
        const url = action === 'present' 
            ? '{{ route("manual-attendance.mark-present") }}'
            : '{{ route("manual-attendance.mark-out") }}';
        
        const actionText = action === 'present' ? 'present' : 'out';
        
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
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Server error: ' + response.status);
                }).catch(e => {
                    throw new Error('Server error: ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const whatsappMsg = data.whatsapp_sent ? ' WhatsApp sent.' : (data.whatsapp_sent === false ? ' WhatsApp not sent (check phone number).' : '');
                showToast('Success', data.message + whatsappMsg);
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Error', data.message || `Failed to mark student as ${actionText}.`, 'error');
                if (pendingAction && pendingAction.btnElement) {
                    pendingAction.btnElement.disabled = false;
                    pendingAction.btnElement.innerHTML = pendingAction.originalText;
                }
            }
        })
        .catch(error => {
            console.error(`Error marking ${actionText}:`, error);
            showToast('Error', error.message || `Failed to mark student as ${actionText}. Please try again.`, 'error');
            if (pendingAction && pendingAction.btnElement) {
                pendingAction.btnElement.disabled = false;
                pendingAction.btnElement.innerHTML = pendingAction.originalText;
            }
        });
    }
    
    // Handle time input confirmation
    confirmTimeBtn.addEventListener('click', function() {
        const time = punchTimeInput.value;
        if (!time) {
            alert('Please enter a time');
            return;
        }

        // Client-side guard: OUT time must be >= IN time (if known)
        if (pendingAction && pendingAction.action === 'out' && pendingAction.inTime) {
            const inHm = pendingAction.inTime.slice(0,5);
            if (time < inHm) {
                showToast('Error', `OUT time cannot be before IN time (${inHm}).`, 'error');
                return;
            }
        }
        
        if (pendingAction) {
            timeInputModal.hide();
            const btnElement = pendingAction.btnElement;
            btnElement.disabled = true;
            btnElement.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Marking...';
            
            submitAttendance(
                pendingAction.action,
                pendingAction.rollNumber,
                pendingAction.date,
                time
            );
            
            pendingAction = null;
        }
    });
    
    // Reset modal when closed
    document.getElementById('timeInputModal').addEventListener('hidden.bs.modal', function() {
        punchTimeInput.value = '';
        if (pendingAction && pendingAction.btnElement) {
            pendingAction.btnElement.disabled = false;
            pendingAction.btnElement.innerHTML = pendingAction.originalText;
        }
        pendingAction = null;
    });
    
    // Mark Present
    document.querySelectorAll('.mark-present-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const rollNumber = this.dataset.roll;
            const date = this.dataset.date;
            
            if (!rollNumber || !date) {
                showToast('Error', 'Missing required information.', 'error');
                return;
            }
            
            // Store action details
            pendingAction = {
                action: 'present',
                rollNumber: rollNumber,
                date: date,
                btnElement: this,
                originalText: this.innerHTML
            };
            
            // Set default time to current time
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            punchTimeInput.value = `${hours}:${minutes}`;
            
            // Show modal
            timeInputModal.show();
        });
    });
    
    // Mark Out
    document.querySelectorAll('.mark-out-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const rollNumber = this.dataset.roll;
            const date = this.dataset.date;
            const inTime = (this.dataset.inTime || '').trim();
            
            if (!rollNumber || !date) {
                showToast('Error', 'Missing required information.', 'error');
                return;
            }
            
            // Store action details
            pendingAction = {
                action: 'out',
                rollNumber: rollNumber,
                date: date,
                    inTime: inTime,
                btnElement: this,
                originalText: this.innerHTML
            };
            
            // Set default time to current time
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const currentHm = `${hours}:${minutes}`;
            
            // Enforce minimum as IN time if present
            if (inTime) {
                const inHm = inTime.slice(0,5);
                punchTimeInput.min = inHm;
                punchTimeInput.value = currentHm < inHm ? inHm : currentHm;
            } else {
                punchTimeInput.min = '';
                punchTimeInput.value = currentHm;
            }
            
            // Show modal
            timeInputModal.show();
        });
    });

    // Prevent browser autocomplete and clear any prefilled values on page load
    const rollInput = document.getElementById('manualAttendanceRoll');
    const nameInput = document.getElementById('manualAttendanceName');
    
    if (rollInput) {
        // Clear if not explicitly in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('roll') || urlParams.get('roll') === '') {
            rollInput.value = '';
        }
        // Prevent autocomplete
        rollInput.setAttribute('autocomplete', 'off');
        rollInput.setAttribute('data-lpignore', 'true');
    }
    
    if (nameInput) {
        // Clear if not explicitly in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('name') || urlParams.get('name') === '') {
            nameInput.value = '';
        }
        // Prevent autocomplete
        nameInput.setAttribute('autocomplete', 'off');
        nameInput.setAttribute('data-lpignore', 'true');
    }
});
</script>
@endpush

@endsection

