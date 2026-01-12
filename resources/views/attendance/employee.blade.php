@extends('layouts.app', ['title' => 'Employee Profile'])

@section('content')
<style>
    /* Modern 3D card effects */
    .profile-stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.25rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07),
                    0 10px 20px rgba(0, 0, 0, 0.05),
                    0 20px 40px rgba(0, 0, 0, 0.03);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .profile-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
        background-size: 200% 100%;
        animation: gradientShift 3s ease infinite;
        opacity: 0.6;
    }
    .profile-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1),
                    0 20px 30px rgba(0, 0, 0, 0.08),
                    0 30px 50px rgba(0, 0, 0, 0.05);
    }
    .profile-stat-card .stat-label {
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    .profile-stat-card .stat-value {
        color: #1e293b;
        font-size: 2rem;
        font-weight: 700;
        line-height: 1.2;
    }
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    /* Modern info card */
    .info-card-modern {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07),
                    0 10px 20px rgba(0, 0, 0, 0.05),
                    0 20px 40px rgba(0, 0, 0, 0.03);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .info-card-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
        background-size: 200% 100%;
        animation: gradientShift 3s ease infinite;
        opacity: 0.6;
    }
    .info-card-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1),
                    0 20px 30px rgba(0, 0, 0, 0.08);
    }
    /* Info field modern style */
    .info-field-modern {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        transition: all 0.2s ease;
    }
    .info-field-modern:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        transform: translateX(4px);
    }
    .info-field-modern .info-label {
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .info-field-modern .info-value {
        color: #1e293b;
        font-size: 1rem;
        font-weight: 600;
    }
    /* Date filter modern */
    .date-filter-modern {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        border: 1px solid #bae6fd;
        border-radius: 16px;
        padding: 1.25rem;
        box-shadow: 0 4px 6px rgba(14, 165, 233, 0.1);
    }
    .date-filter-modern .form-control {
        border: 2px solid #bae6fd;
        border-radius: 10px;
        font-size: 16px; /* Prevents iOS zoom */
    }
    .date-filter-modern .form-control:focus {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
    }
    /* Accordion modern styling */
    .accordion-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px !important;
        margin-bottom: 0.75rem;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    .accordion-button {
        background: #f8fafc;
        border: none;
        font-weight: 600;
        padding: 1rem 1.25rem;
    }
    .accordion-button:not(.collapsed) {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        color: #1e293b;
        box-shadow: none;
    }
    .accordion-button:focus {
        box-shadow: none;
        border-color: transparent;
    }
    .accordion-body {
        padding: 1.25rem;
        background: #ffffff;
    }
    /* Mobile table to card conversion */
    @media (max-width: 768px) {
        .profile-stat-card {
            margin-bottom: 1rem;
        }
        .info-field-modern {
            margin-bottom: 1rem;
        }
        .date-filter-modern {
            margin-top: 1rem;
        }
        .date-filter-modern .btn {
            min-height: 44px;
            font-size: 0.9rem;
        }
        /* Convert form mobile styling */
        .border-top form {
            width: 100%;
        }
        .border-top .btn {
            width: 100% !important;
            min-height: 44px;
            font-size: 0.9rem;
        }
        /* Hide table on mobile, show card view */
        .table-responsive {
            display: none;
        }
        .attendance-mobile-list {
            display: block;
        }
        /* Mobile attendance item */
        .attendance-mobile-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        .attendance-mobile-item:last-child {
            margin-bottom: 0;
        }
        .attendance-mobile-item .pair-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .attendance-mobile-item .pair-number {
            font-weight: 600;
            color: #64748b;
            font-size: 0.85rem;
        }
        .attendance-mobile-item .time-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .attendance-mobile-item .time-label {
            font-weight: 600;
            color: #475569;
            min-width: 60px;
            font-size: 0.85rem;
        }
        .attendance-mobile-item .duration-badge {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #e2e8f0;
        }
    }
    /* Desktop: show table, hide mobile view */
    @media (min-width: 769px) {
        .attendance-mobile-list {
            display: none;
        }
        .table-responsive {
            display: block;
        }
    }
</style>
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
                    <div class="profile-stat-card">
                        <div class="stat-label"><i class="bi bi-person-badge"></i> Employee ID</div>
                        <div class="stat-value">{{ $roll }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="profile-stat-card">
                        <div class="stat-label"><i class="bi bi-list-check"></i> Total Punches</div>
                        <div class="stat-value">{{ count($raw) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="profile-stat-card">
                        <div class="stat-label"><i class="bi bi-calendar-check"></i> Active Days</div>
                        <div class="stat-value">{{ count($daily) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="date-filter-modern">
                <form class="row gy-2 gx-2" method="get" action="{{ url('/employees/'.$roll) }}">
                    <div class="col-12">
                        <label class="form-label fw-bold"><i class="bi bi-calendar-range"></i> Date Range</label>
                    </div>
                    <div class="col-6">
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" placeholder="From">
                    </div>
                    <div class="col-6">
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" placeholder="To">
                    </div>
                    <div class="col-6">
                        <button class="btn btn-primary w-100" style="min-height: 44px;"><i class="bi bi-search"></i> Filter</button>
                    </div>
                    <div class="col-6">
                        <a class="btn btn-outline-secondary w-100" href="{{ url('/employees/'.$roll) }}" style="min-height: 44px;"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Employee Information Card -->
<div class="info-card-modern mb-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="section-title mb-0"><i class="bi bi-info-circle"></i> Employee Information</div>
    </div>
    
    <!-- Read-Only View -->
    <div id="employeeInfoView" class="row g-3">
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-field-modern">
                <div class="info-label"><i class="bi bi-person"></i> Name</div>
                <div class="info-value">{{ $employee->name ?? '—' }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-field-modern">
                <div class="info-label"><i class="bi bi-person-badge"></i> Father's Name</div>
                <div class="info-value">{{ $employee->father_name ?? '—' }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-field-modern">
                <div class="info-label"><i class="bi bi-briefcase"></i> Category</div>
                <div class="info-value">
                    @if($employee->category === 'academic')
                        <span class="badge bg-primary" style="font-size: 0.9rem;"><i class="bi bi-mortarboard"></i> Academic</span>
                    @elseif($employee->category === 'non_academic')
                        <span class="badge bg-secondary" style="font-size: 0.9rem;"><i class="bi bi-briefcase"></i> Non-academic</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-field-modern">
                <div class="info-label"><i class="bi bi-telephone"></i> Mobile</div>
                <div class="info-value">
                    @if($employee->mobile)
                        <a href="tel:{{ $employee->mobile }}" class="text-decoration-none" style="color: var(--brand-accent-2);">
                            <i class="bi bi-telephone-fill"></i> {{ $employee->mobile }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-field-modern">
                <div class="info-label"><i class="bi bi-check-circle"></i> Status</div>
                <div class="info-value">
                    @if($employee->is_active)
                        <span class="badge bg-success" style="font-size: 0.9rem;"><i class="bi bi-check-circle-fill"></i> Active</span>
                    @else
                        <span class="badge bg-secondary" style="font-size: 0.9rem;"><i class="bi bi-x-circle"></i> Inactive</span>
                    @endif
                </div>
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
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="muted small">Profile Type:</div>
                        <span class="badge bg-info">
                            <i class="bi bi-briefcase"></i> Employee
                        </span>
                    </div>
                </div>
                @if($canConvertToStudent)
                    <div class="col-12 col-md-auto">
                        <form method="POST" action="{{ route('employees.convert-to-student', $roll) }}" 
                              onsubmit="return confirm('⚠️ Transform this employee profile to a student profile?\n\n- Employee profile will be PERMANENTLY DELETED\n- Student profile will be created/restored with same roll number\n- All attendance data will be preserved\n- Name, father name, and mobile will be transferred\n\n{{ $existingStudent && !$existingStudent->isActive() ? "Note: A discontinued student profile exists and will be restored.\n\n" : "" }}This action cannot be undone. Continue?');" 
                              class="d-inline-block w-100 w-md-auto">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm w-100 w-md-auto" style="white-space: nowrap; min-height: 38px;">
                                <i class="bi bi-arrow-right-circle"></i> Convert to Student
                            </button>
                        </form>
                    </div>
                @else
                    <div class="col-12 col-md-auto">
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
<div class="info-card-modern mb-3">
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
                                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                                    <span class="badge bg-success"><i class="bi bi-box-arrow-in-right"></i> {{ $pair['in'] }}</span>
                                                                    @if(isset($pair['is_manual_in']) && $pair['is_manual_in'])
                                                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                                                            <i class="bi bi-pencil"></i> Manual
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                @if(isset($pair['is_manual_in']) && $pair['is_manual_in'] && isset($pair['marked_by_in']) && $pair['marked_by_in'])
                                                                    <div class="mt-1">
                                                                        <small class="text-primary" style="font-size: 0.8rem; font-weight: 500;">
                                                                            <i class="bi bi-person-fill"></i> Marked by: <strong style="color: #1e40af;">{{ is_object($pair['marked_by_in']) ? $pair['marked_by_in']->name : $pair['marked_by_in'] }}</strong>
                                                                        </small>
                                                                    </div>
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
                                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                                    <span class="badge bg-danger"><i class="bi bi-box-arrow-right"></i> {{ $pair['out'] }}</span>
                                                                    @if(isset($pair['is_manual_out']) && $pair['is_manual_out'])
                                                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                                                            <i class="bi bi-pencil"></i> Manual
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                @if(isset($pair['is_manual_out']) && $pair['is_manual_out'] && isset($pair['marked_by_out']) && $pair['marked_by_out'])
                                                                    <div class="mt-1">
                                                                        <small class="text-primary" style="font-size: 0.8rem; font-weight: 500;">
                                                                            <i class="bi bi-person-fill"></i> Marked by: <strong style="color: #1e40af;">{{ is_object($pair['marked_by_out']) ? $pair['marked_by_out']->name : $pair['marked_by_out'] }}</strong>
                                                                        </small>
                                                                    </div>
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
                                
                                <!-- Mobile-friendly card view -->
                                <div class="attendance-mobile-list">
                                    @foreach ($d['pairs'] as $pairIndex => $pair)
                                        <div class="attendance-mobile-item">
                                            <div class="pair-header">
                                                <span class="pair-number">Pair {{ $pairIndex + 1 }}</span>
                                                @if($pair['in'] && $pair['out'])
                                                    @php
                                                        $inTime = \Carbon\Carbon::parse($d['date'] . ' ' . $pair['in']);
                                                        $outTime = \Carbon\Carbon::parse($d['date'] . ' ' . $pair['out']);
                                                        $duration = $inTime->diff($outTime);
                                                    @endphp
                                                    <span class="badge bg-info text-dark">
                                                        <i class="bi bi-clock"></i> {{ $duration->format('%h:%I') }}
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <div class="time-row">
                                                <span class="time-label">IN:</span>
                                                @if($pair['in'])
                                                    <span class="badge bg-success" style="font-size: 0.85rem;">
                                                        <i class="bi bi-box-arrow-in-right"></i> {{ $pair['in'] }}
                                                    </span>
                                                    @if(isset($pair['is_manual_in']) && $pair['is_manual_in'])
                                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                                            <i class="bi bi-pencil"></i> Manual
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                            @if(isset($pair['is_manual_in']) && $pair['is_manual_in'] && isset($pair['marked_by_in']) && $pair['marked_by_in'])
                                                <div class="ms-4 mt-1 mb-2">
                                                    <small class="text-primary" style="font-size: 0.8rem; font-weight: 500;">
                                                        <i class="bi bi-person-fill"></i> Marked by: <strong style="color: #1e40af;">{{ is_object($pair['marked_by_in']) ? $pair['marked_by_in']->name : $pair['marked_by_in'] }}</strong>
                                                    </small>
                                                </div>
                                            @endif
                                            
                                            <div class="time-row">
                                                <span class="time-label">OUT:</span>
                                                @if($pair['out'])
                                                    <span class="badge bg-danger" style="font-size: 0.85rem;">
                                                        <i class="bi bi-box-arrow-right"></i> {{ $pair['out'] }}
                                                    </span>
                                                    @if(isset($pair['is_manual_out']) && $pair['is_manual_out'])
                                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                                            <i class="bi bi-pencil"></i> Manual
                                                        </span>
                                                    @endif
                                                    @if(isset($pair['is_auto_out']) && $pair['is_auto_out'])
                                                        <span class="badge bg-info text-dark" style="font-size: 0.7rem;">
                                                            <i class="bi bi-clock"></i> Auto OUT
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                            @if(isset($pair['is_manual_out']) && $pair['is_manual_out'] && isset($pair['marked_by_out']) && $pair['marked_by_out'])
                                                <div class="ms-4 mt-1 mb-2">
                                                    <small class="text-primary" style="font-size: 0.8rem; font-weight: 500;">
                                                        <i class="bi bi-person-fill"></i> Marked by: <strong style="color: #1e40af;">{{ is_object($pair['marked_by_out']) ? $pair['marked_by_out']->name : $pair['marked_by_out'] }}</strong>
                                                    </small>
                                                </div>
                                            @endif
                                            
                                            @if(isset($pair['whatsapp_in']) || isset($pair['whatsapp_out']))
                                                <div class="mt-2 pt-2 border-top">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @if(isset($pair['whatsapp_in']))
                                                            @php $waIn = $pair['whatsapp_in']; @endphp
                                                            <span class="badge {{ $waIn->status === 'success' ? 'bg-success' : ($waIn->status === 'failed' ? 'bg-danger' : 'bg-secondary') }}" style="font-size: 0.7rem;">
                                                                <i class="bi bi-whatsapp"></i> IN: {{ $waIn->status === 'success' ? 'Sent' : ($waIn->status === 'failed' ? 'Failed' : 'Pending') }}
                                                            </span>
                                                        @endif
                                                        @if(isset($pair['whatsapp_out']))
                                                            @php $waOut = $pair['whatsapp_out']; @endphp
                                                            <span class="badge {{ $waOut->status === 'success' ? 'bg-success' : ($waOut->status === 'failed' ? 'bg-danger' : 'bg-secondary') }}" style="font-size: 0.7rem;">
                                                                <i class="bi bi-whatsapp"></i> OUT: {{ $waOut->status === 'success' ? 'Sent' : ($waOut->status === 'failed' ? 'Failed' : 'Pending') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
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
