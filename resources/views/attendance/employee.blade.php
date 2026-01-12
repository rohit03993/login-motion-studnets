@extends('layouts.app', ['title' => 'Employee Profile'])

@section('content')
<!-- Employee Profile Header -->
<div class="brand-card mb-3">
    <div class="row g-3 align-items-center">
        <div class="col-12 col-md-8">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="section-title mb-0">
                    <i class="bi bi-person-circle"></i> Employee Profile
                    @php
                        $isDiscontinued = ($employee->discontinued_at ?? false) || (!$employee->is_active ?? false);
                    @endphp
                    @if($isDiscontinued)
                        <span class="badge bg-warning text-dark ms-2">
                            <i class="bi bi-x-circle"></i> Discontinued
                        </span>
                    @endif
                </div>
                <a href="{{ url('/attendance') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
            <div class="muted mb-3">Live attendance data from EasyTimePro</div>
            
            <div class="row g-3">
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="stat-label">Employee ID</div>
                        <div class="stat-value" style="font-size: 20px;">{{ $roll }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="stat-label">Total Punches</div>
                        <div class="stat-value">{{ count($raw) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="stat-label">Active Days</div>
                        <div class="stat-value">{{ count($daily) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <form class="row gy-2 gx-2" method="get" action="{{ url('/employees/'.$roll) }}">
                <div class="col-12">
                    <label class="form-label"><i class="bi bi-calendar-range"></i> Date Range</label>
                </div>
                <div class="col-6">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" placeholder="From">
                </div>
                <div class="col-6">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" placeholder="To">
                </div>
                <div class="col-6">
                    <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                </div>
                <div class="col-6">
                    <a class="btn btn-outline-secondary w-100" href="{{ url('/employees/'.$roll) }}"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Employee Information Card -->
<div class="brand-card mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title mb-0"><i class="bi bi-info-circle"></i> Employee Information</div>
    </div>
    
    <!-- Read-Only View -->
    <div id="employeeInfoView" class="row g-3">
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Name</div>
            <div class="fw-medium">{{ $employee->name ?? '—' }}</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Father's Name</div>
            <div class="fw-medium">{{ $employee->father_name ?? '—' }}</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Category</div>
            <div class="fw-medium">
                @if($employee->category === 'academic')
                    <span class="badge bg-primary">Academic</span>
                @elseif($employee->category === 'non_academic')
                    <span class="badge bg-secondary">Non-academic</span>
                @else
                    —
                @endif
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Mobile</div>
            <div class="fw-medium">
                @if($employee->mobile)
                    <a href="tel:{{ $employee->mobile }}" class="text-decoration-none">
                        <i class="bi bi-telephone"></i> {{ $employee->mobile }}
                    </a>
                @else
                    —
                @endif
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Status</div>
            <div>
                @if($employee->is_active)
                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                @else
                    <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Inactive</span>
                @endif
            </div>
        </div>
    </div>
    
    @if(auth()->user()->isSuperAdmin())
    <!-- Discontinue/Restore Actions (Super Admin Only) -->
    <div class="mt-4 pt-3 border-top">
        <div class="d-flex align-items-center gap-2">
            <div class="muted small">Account Status:</div>
            @php
                $isDiscontinued = ($employee->discontinued_at ?? false) || (!$employee->is_active ?? false);
            @endphp
            @if($isDiscontinued)
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-x-circle"></i> Discontinued
                    @if($employee->discontinued_at)
                        <small>({{ \Carbon\Carbon::parse($employee->discontinued_at)->format('M d, Y') }})</small>
                    @endif
                </span>
                <form method="POST" action="{{ route('employees.restore', $roll) }}" style="display: inline;" class="ms-auto">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-arrow-counterclockwise"></i> Restore Employee
                    </button>
                </form>
            @else
                <span class="badge bg-success">
                    <i class="bi bi-check-circle"></i> Active
                </span>
                <form method="POST" action="{{ route('employees.discontinue', $roll) }}" 
                      onsubmit="return confirm('Discontinue this employee? They will not appear in employee lists, but historical records will be preserved.');" 
                      style="display: inline;" class="ms-auto">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="bi bi-x-circle"></i> Discontinue Employee
                    </button>
                </form>
            @endif
        </div>
        
        <!-- Convert to Student (Super Admin Only) -->
        @php
            // Safety check: Only block conversion if ACTIVE student exists
            // Allow conversion if student is discontinued (will be overwritten/restored)
            $existingStudent = \App\Models\Student::withTrashed()->where('roll_number', $roll)->first();
            $canConvertToStudent = !$existingStudent || ($existingStudent && !$existingStudent->isActive());
        @endphp
        <div class="mt-3 pt-3 border-top">
            <div class="d-flex align-items-center gap-2">
                <div class="muted small">Profile Type:</div>
                <span class="badge bg-info">
                    <i class="bi bi-briefcase"></i> Employee
                </span>
                @if($canConvertToStudent)
                    <form method="POST" action="{{ route('employees.convert-to-student', $roll) }}" 
                          onsubmit="return confirm('⚠️ Transform this employee profile to a student profile?\n\n- Employee profile will be PERMANENTLY DELETED\n- Student profile will be created/restored with same roll number\n- All attendance data will be preserved\n- Name, father name, and mobile will be transferred\n\n{{ $existingStudent && !$existingStudent->isActive() ? "Note: A discontinued student profile exists and will be restored.\n\n" : "" }}This action cannot be undone. Continue?');" 
                          style="display: inline;" class="ms-auto">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-arrow-right-circle"></i> Convert to Student
                        </button>
                    </form>
                @else
                    <div class="ms-auto">
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-exclamation-triangle"></i> Active student profile already exists
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Daily Attendance Timeline -->
<div class="brand-card mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title mb-0"><i class="bi bi-calendar-check"></i> Daily Attendance Timeline</div>
        @if(isset($totalDuration))
            <div>
                <span class="badge bg-info" style="font-size: 0.95rem;">
                    <i class="bi bi-clock-history"></i> Total: {{ $totalDuration['hours'] }}h {{ $totalDuration['minutes'] }}m
                </span>
            </div>
        @endif
    </div>
    @if(count($daily) > 0)
        @php
            // Sort daily array by date descending (latest first)
            usort($daily, function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });
        @endphp
        <div class="accordion" id="attendanceAccordion">
            @foreach ($daily as $index => $d)
                @php
                    $dateObj = \Carbon\Carbon::parse($d['date']);
                    $hasPairs = !empty($d['pairs']);
                    $pairCount = count($d['pairs']);
                    $accordionId = 'date-' . str_replace('-', '', $d['date']);
                    $isFirst = $index === 0;
                    $isAbsent = isset($d['is_absent']) && $d['is_absent'];
                    
                    // Calculate total duration for this date
                    $dateDurationSeconds = 0;
                    if ($hasPairs && !$isAbsent) {
                        foreach ($d['pairs'] as $pair) {
                            if ($pair['in'] && $pair['out']) {
                                $inTime = \Carbon\Carbon::parse($d['date'] . ' ' . $pair['in']);
                                $outTime = \Carbon\Carbon::parse($d['date'] . ' ' . $pair['out']);
                                $dateDurationSeconds += $inTime->diffInSeconds($outTime);
                            }
                        }
                    }
                    $dateDurationHours = floor($dateDurationSeconds / 3600);
                    $dateDurationMinutes = floor(($dateDurationSeconds % 3600) / 60);
                @endphp
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $accordionId }}">
                        <button class="accordion-button {{ $isFirst ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $accordionId }}" aria-expanded="{{ $isFirst ? 'true' : 'false' }}" aria-controls="collapse{{ $accordionId }}">
                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                <div>
                                    <span class="fw-bold">{{ $dateObj->format('M d, Y') }}</span>
                                    <span class="text-muted ms-2">{{ $dateObj->format('D') }}</span>
                                </div>
                                <div>
                                    @if($isAbsent)
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Absent</span>
                                    @else
                                        <span class="badge bg-primary">{{ $pairCount }} {{ $pairCount === 1 ? 'pair' : 'pairs' }}</span>
                                        @if($dateDurationSeconds > 0)
                                            <span class="badge bg-info text-dark ms-2">
                                                <i class="bi bi-clock"></i> {{ $dateDurationHours }}h {{ $dateDurationMinutes }}m
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse{{ $accordionId }}" class="accordion-collapse collapse {{ $isFirst ? 'show' : '' }}" aria-labelledby="heading{{ $accordionId }}" data-bs-parent="#attendanceAccordion">
                        <div class="accordion-body">
                            @if($isAbsent)
                                <div class="text-center py-4">
                                    <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
                                    <p class="mt-3 mb-0 text-danger fw-bold">There is no attendance record for this date till now</p>
                                    <p class="text-muted small mt-2">Attendance will be recorded when employee punches IN</p>
                                    
                                    @if(auth()->user()->isSuperAdmin())
                                    <!-- Manual Attendance Button for Absent Date (Super Admin Only) -->
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-success btn-sm mark-in-btn" 
                                                data-roll="{{ $roll }}" 
                                                data-date="{{ $d['date'] }}">
                                            <i class="bi bi-box-arrow-in-right"></i> Mark IN
                                        </button>
                                    </div>
                                    @endif
                                </div>
                            @elseif($hasPairs)
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th><i class="bi bi-box-arrow-in-right text-success"></i> IN Time</th>
                                                <th><i class="bi bi-box-arrow-right text-danger"></i> OUT Time</th>
                                                <th><i class="bi bi-clock"></i> Duration</th>
                                                <th><i class="bi bi-diagram-3"></i> Pair</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($d['pairs'] as $pairIndex => $pair)
                                                <tr>
                                                    <td>
                                                        @if($pair['in'])
                                                            <div>
                                                                <span class="badge bg-success"><i class="bi bi-box-arrow-in-right"></i> {{ $pair['in'] }}</span>
                                                                @if(isset($pair['is_manual_in']) && $pair['is_manual_in'])
                                                                    <span class="badge bg-warning text-dark ms-1" title="Manually marked{{ isset($pair['marked_by_in']) && $pair['marked_by_in'] ? ' by ' . $pair['marked_by_in']->name : '' }}">
                                                                        <i class="bi bi-pencil"></i> Manual
                                                                        @if(isset($pair['marked_by_in']) && $pair['marked_by_in'])
                                                                            <small class="d-block mt-1" style="font-size: 0.6rem; color: #666;">
                                                                                by {{ $pair['marked_by_in']->name }}
                                                                            </small>
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @if(isset($pair['whatsapp_in']))
                                                                @php $waIn = $pair['whatsapp_in']; @endphp
                                                                @if($waIn->status === 'success')
                                                                    <small class="d-block mt-1">
                                                                        <span class="badge bg-success" style="font-size: 0.7rem;">
                                                                            <i class="bi bi-whatsapp"></i> Sent
                                                                        </span>
                                                                    </small>
                                                                @elseif($waIn->status === 'failed')
                                                                    <small class="d-block mt-1">
                                                                        <span class="badge bg-danger" style="font-size: 0.7rem;" title="{{ $waIn->error ?? 'Failed' }}">
                                                                            <i class="bi bi-whatsapp"></i> Failed
                                                                        </span>
                                                                    </small>
                                                                @else
                                                                    <small class="d-block mt-1">
                                                                        <span class="badge bg-secondary" style="font-size: 0.7rem;">
                                                                            <i class="bi bi-whatsapp"></i> Pending
                                                                        </span>
                                                                    </small>
                                                                @endif
                                                            @else
                                                                <small class="d-block mt-1">
                                                                    <span class="badge bg-secondary" style="font-size: 0.7rem;">
                                                                        <i class="bi bi-whatsapp"></i> Not sent
                                                                    </span>
                                                                </small>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($pair['out'])
                                                            <div>
                                                                <span class="badge bg-danger"><i class="bi bi-box-arrow-right"></i> {{ $pair['out'] }}</span>
                                                                @if(isset($pair['is_manual_out']) && $pair['is_manual_out'])
                                                                    <span class="badge bg-warning text-dark ms-1" title="Manually marked OUT{{ isset($pair['marked_by_out']) && $pair['marked_by_out'] ? ' by ' . $pair['marked_by_out']->name : '' }}">
                                                                        <i class="bi bi-pencil"></i> Manual
                                                                        @if(isset($pair['marked_by_out']) && $pair['marked_by_out'])
                                                                            <small class="d-block mt-1" style="font-size: 0.6rem; color: #666;">
                                                                                by {{ $pair['marked_by_out']->name }}
                                                                            </small>
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                                @if(isset($pair['is_auto_out']) && $pair['is_auto_out'])
                                                                    <small class="d-block mt-1">
                                                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;" title="Automatically marked OUT at 7 PM">
                                                                            <i class="bi bi-clock"></i> Auto OUT
                                                                        </span>
                                                                    </small>
                                                                @endif
                                                            </div>
                                                            @if(isset($pair['whatsapp_out']))
                                                                @php $waOut = $pair['whatsapp_out']; @endphp
                                                                @if($waOut->status === 'success')
                                                                    <small class="d-block mt-1">
                                                                        <span class="badge bg-success" style="font-size: 0.7rem;">
                                                                            <i class="bi bi-whatsapp"></i> Sent
                                                                        </span>
                                                                    </small>
                                                                @elseif($waOut->status === 'failed')
                                                                    <small class="d-block mt-1">
                                                                        <span class="badge bg-danger" style="font-size: 0.7rem;" title="{{ $waOut->error ?? 'Failed' }}">
                                                                            <i class="bi bi-whatsapp"></i> Failed
                                                                        </span>
                                                                    </small>
                                                                @else
                                                                    <small class="d-block mt-1">
                                                                        <span class="badge bg-secondary" style="font-size: 0.7rem;">
                                                                            <i class="bi bi-whatsapp"></i> Pending
                                                                        </span>
                                                                    </small>
                                                                @endif
                                                            @else
                                                                <small class="d-block mt-1">
                                                                    <span class="badge bg-secondary" style="font-size: 0.7rem;">
                                                                        <i class="bi bi-whatsapp"></i> Not sent
                                                                    </span>
                                                                </small>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($pair['in'] && $pair['out'])
                                                            @php
                                                                $inTime = \Carbon\Carbon::parse($d['date'] . ' ' . $pair['in']);
                                                                $outTime = \Carbon\Carbon::parse($d['date'] . ' ' . $pair['out']);
                                                                $duration = $inTime->diff($outTime);
                                                            @endphp
                                                            <span class="text-muted">{{ $duration->format('%h:%I') }}</span>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">{{ $pairIndex + 1 }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-3">
                                    <span class="text-muted">No valid attendance pairs for this date</span>
                                </div>
                            @endif
                            
                            @if(auth()->user()->isSuperAdmin())
                            <!-- Manual Attendance Buttons (Super Admin Only) -->
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex gap-2 flex-wrap">
                                    @php
                                        // Check if this date has IN mark
                                        $hasInForDate = false;
                                        $hasOutForDate = false;
                                        if ($hasPairs && !$isAbsent) {
                                            foreach ($d['pairs'] as $pair) {
                                                if ($pair['in']) {
                                                    $hasInForDate = true;
                                                }
                                                if ($pair['out']) {
                                                    $hasOutForDate = true;
                                                }
                                            }
                                        }
                                    @endphp
                                    
                                    @if(!$hasInForDate)
                                        <button type="button" class="btn btn-success btn-sm mark-in-btn" 
                                                data-roll="{{ $roll }}" 
                                                data-date="{{ $d['date'] }}">
                                            <i class="bi bi-box-arrow-in-right"></i> Mark IN
                                        </button>
                                    @endif
                                    
                                    @if($hasInForDate && !$hasOutForDate)
                                        <button type="button" class="btn btn-danger btn-sm mark-out-btn" 
                                                data-roll="{{ $roll }}" 
                                                data-date="{{ $d['date'] }}">
                                            <i class="bi bi-box-arrow-right"></i> Mark OUT
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="muted small mt-3">
            <i class="bi bi-info-circle"></i> Logic: IN/OUT flips after ≥2 minutes gap; duplicates within 10 seconds are ignored.
        </div>
    @else
        <div class="text-center py-4">
            <i class="bi bi-inbox" style="font-size: 2rem; color: var(--brand-muted);"></i>
            <div class="mt-2 text-muted">No attendance data found.</div>
        </div>
    @endif
</div>

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
    
    let pendingAction = null; // Store the action to execute after time input
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
    document.querySelectorAll('.mark-in-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            pendingAction = 'present';
            pendingRoll = this.getAttribute('data-roll');
            pendingDate = this.getAttribute('data-date');
            
            // Set default time to current time
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            punchTimeInput.value = `${hours}:${minutes}`;
            
            // Show modal
            timeInputModal.show();
        });
    });
    
    // Handle Mark OUT buttons
    document.querySelectorAll('.mark-out-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            pendingAction = 'out';
            pendingRoll = this.getAttribute('data-roll');
            pendingDate = this.getAttribute('data-date');
            
            // Set default time to current time
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            punchTimeInput.value = `${hours}:${minutes}`;
            
            // Show modal
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
            // Extract time in HH:MM format
            const timeValue = punchTimeInput.value; // Already in HH:MM format from time input
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
