@extends('layouts.app', ['title' => 'Live Attendance'])

@section('content')
<style>
    /* Subtle background to reduce white appearance */
    body {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
    }
    .container-wide {
        background: transparent;
    }
    .live-stat {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        border: none;
        box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2),
                    0 10px 20px rgba(79, 70, 229, 0.15),
                    0 20px 40px rgba(79, 70, 229, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .live-stat::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: shimmer 3s ease-in-out infinite;
    }
    .live-stat:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 8px 12px rgba(79, 70, 229, 0.25),
                    0 20px 30px rgba(79, 70, 229, 0.2),
                    0 30px 50px rgba(79, 70, 229, 0.15);
    }
    @keyframes shimmer {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        50% { transform: translate(30%, 30%) rotate(180deg); }
    }
    .live-stat .stat-label { color: rgba(255,255,255,0.9); }
    .live-stat .stat-value { color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .punch-card {
        border: 1px solid #e9edf5;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 
                    0 10px 20px rgba(0, 0, 0, 0.05),
                    0 20px 40px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        background: #ffffff;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .punch-card::before {
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
    .punch-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1), 
                    0 20px 30px rgba(0, 0, 0, 0.08),
                    0 30px 50px rgba(0, 0, 0, 0.05);
    }
    .punch-card .card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem 1.25rem;
    }
    .punch-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #eef2ff;
        border: 1px solid #e0e7ff;
        color: #4338ca;
        font-size: 12px;
        font-weight: 600;
    }
    .punch-card tbody tr:hover {
        background: #f9fafb;
    }
    .state-pill {
        border-radius: 999px;
        padding: 6px 12px;
        font-weight: 600;
        font-size: 12px;
    }
    .filters-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px rgba(15,23,42,0.05),
                    0 10px 20px rgba(15,23,42,0.04),
                    0 20px 40px rgba(15,23,42,0.03);
        border-radius: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .filters-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
        background-size: 200% 100%;
        animation: gradientShift 3s ease infinite;
        opacity: 0.5;
    }
    .filters-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 12px rgba(15,23,42,0.08),
                    0 20px 30px rgba(15,23,42,0.06),
                    0 30px 50px rgba(15,23,42,0.04);
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
    .filter-btn-modern {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border: none;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }
    .filter-btn-modern:hover {
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
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    @media (max-width: 768px) {
        .live-stat .stat-value { font-size: 18px; }
        .stat-card { margin-bottom: 8px; }
        /* Mobile filter form */
        .filters-card {
            padding: 1rem !important;
        }
        .filter-input-modern,
        .filter-select-modern {
            font-size: 16px; /* Prevents zoom on iOS */
        }
        .filter-btn-modern,
        .reset-btn-modern {
            min-height: 44px; /* Touch-friendly */
            font-size: 0.9rem;
        }
        /* Mobile-optimized punch cards */
        .punch-card {
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        .punch-card .card-header {
            padding: 0.75rem;
            flex-direction: column;
            align-items: flex-start !important;
            gap: 0.5rem;
        }
        .punch-card .card-body {
            padding: 0.75rem;
        }
        /* Hide table on mobile, show card view */
        .punch-card .table-responsive {
            display: none;
        }
        .punch-card .mobile-punch-list {
            display: block;
        }
        /* Mobile punch item */
        .mobile-punch-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .mobile-punch-item:last-child {
            margin-bottom: 0;
        }
        .mobile-punch-date {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .mobile-punch-times {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .mobile-punch-time-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .mobile-punch-time-row strong {
            min-width: 50px;
            font-size: 0.85rem;
            color: #64748b;
        }
        .mobile-punch-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .mobile-punch-duration {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.85rem;
            color: #64748b;
        }
        /* Compact header badges */
        .punch-card .card-header .badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        .punch-card .card-header .punch-chip {
            font-size: 0.7rem;
            padding: 0.25em 0.6em;
        }
    }
    /* Desktop: show table, hide mobile view */
    @media (min-width: 769px) {
        .punch-card .mobile-punch-list {
            display: none;
        }
        .punch-card .table-responsive {
            display: block;
        }
    }
    /* Auto-refresh button animations */
    .auto-refresh-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 8px 16px;
        font-size: 0.75rem;
        box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        cursor: default;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 140px;
        justify-content: center;
    }
    .auto-refresh-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
    }
    .auto-refresh-btn .refresh-icon {
        animation: pulse 2s ease-in-out infinite;
    }
    .auto-refresh-btn .punch-animation {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        margin-left: 4px;
        animation: punchPulse 1.5s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% {
            transform: rotate(0deg) scale(1);
            opacity: 1;
        }
        50% {
            transform: rotate(180deg) scale(1.1);
            opacity: 0.8;
        }
    }
    @keyframes punchPulse {
        0%, 100% {
            transform: scale(1);
            opacity: 1;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        50% {
            transform: scale(1.3);
            opacity: 0.7;
            box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
        }
    }
    .auto-refresh-btn .countdown-text {
        font-weight: 600;
        letter-spacing: 0.5px;
    }
</style>

<div class="brand-card mb-3 filters-card">
    <div class="section-title mb-3"><i class="bi bi-funnel"></i> Filters</div>
    <form class="row g-3" method="get" action="{{ url($isEmployeeView ? '/employees/attendance' : '/attendance') }}" id="attendanceFilterForm">
        <div class="col-12 col-sm-6 col-md-3">
            <label class="filter-label-modern"><i class="bi bi-person-badge"></i> Roll / Employee ID</label>
            <input type="text" 
                   name="roll" 
                   id="filterRoll" 
                   value="{{ request()->has('roll') && request('roll') !== '' ? request('roll') : '' }}" 
                   class="form-control filter-input-modern" 
                   placeholder="Enter roll number" 
                   autocomplete="off"
                   data-lpignore="true">
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <label class="filter-label-modern"><i class="bi bi-person"></i> Name (partial)</label>
            <input type="text" 
                   name="name" 
                   id="filterName" 
                   value="{{ request()->has('name') && request('name') !== '' ? request('name') : '' }}" 
                   class="form-control filter-input-modern" 
                   placeholder="Enter name" 
                   autocomplete="off"
                   data-lpignore="true">
        </div>
        @if(!$isEmployeeView)
        <div class="col-12 col-sm-6 col-md-2">
            <label class="filter-label-modern"><i class="bi bi-book"></i> Class</label>
            <select name="class" class="form-select filter-select-modern" id="filterClass">
                <option value="">All Classes</option>
                @if(isset($courses) && is_iterable($courses))
                    @foreach($courses as $course)
                        <option value="{{ $course->name ?? '' }}" {{ request('class') === ($course->name ?? '') ? 'selected' : '' }}>
                            {{ $course->name ?? '' }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
        @endif
        <div class="col-12 col-sm-6 col-md-2">
            <label class="filter-label-modern"><i class="bi bi-calendar-event"></i> Date</label>
            <input type="date" 
                   name="date" 
                   id="filterDate" 
                   value="{{ request('date') ?: date('Y-m-d') }}" 
                   class="form-control filter-input-modern" 
                   max="{{ date('Y-m-d') }}">
        </div>
        <div class="col-12 col-sm-12 col-md-2 d-flex gap-2 align-items-end">
            <button type="submit" class="btn filter-btn-modern btn-sm flex-fill">
                <i class="bi bi-search"></i> <span class="d-none d-sm-inline">Filter</span>
            </button>
            <a class="btn reset-btn-modern btn-sm flex-fill" href="{{ url($isEmployeeView ? '/employees/attendance' : '/attendance') }}">
                <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline">Reset</span>
            </a>
        </div>
    </form>
</div>

@if(isset($setup_required) && $setup_required)
    <div class="brand-card">
        <div class="alert alert-warning mb-0">
            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> EasyTimePro Parallel Database Setup Required</h5>
            <p class="mb-2">The <code>punch_logs</code> table has not been created yet. This table is automatically created by EasyTimePro's parallel database feature.</p>
            <hr>
            <p class="mb-2"><strong>Next Steps:</strong></p>
            <ol class="mb-0">
                <li>Configure EasyTimePro to connect to your VPS database (<code>logintask</code>)</li>
                <li>Once connected, EasyTimePro will automatically create the <code>punch_logs</code> table</li>
                <li>After the table is created, run: <code>php artisan trigger:setup</code> to create the notification trigger and indexes</li>
                <li>Refresh this page to see attendance data</li>
            </ol>
        </div>
    </div>
@else
<!-- Auto-refresh button (Bottom Right) -->
<div class="auto-refresh-btn">
    <i class="bi bi-arrow-clockwise refresh-icon"></i>
        <span class="countdown-text" id="autoRefreshCountdown">2:00</span>
        <small style="opacity: 0.8; font-size: 0.65rem;">next</small>
    <span class="punch-animation"></span>
</div>

<!-- Students Statistics Cards (Hidden in Employee View) -->
@if(!$isEmployeeView)
<div class="mb-4">
    <div class="section-title mb-3">
        <i class="bi bi-people"></i> Students
        <small class="text-muted ms-2" id="lastRefreshTime" style="font-size: 0.75rem;"></small>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="stat-card live-stat" style="background: linear-gradient(135deg, #10b981, #059669);" data-stat="student-in">
                <div class="stat-label"><i class="bi bi-box-arrow-in-right"></i> IN</div>
                <div class="stat-value" style="font-size: 2.5rem;">{{ number_format($studentStats['in'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card live-stat" style="background: linear-gradient(135deg, #ef4444, #dc2626);" data-stat="student-out">
                <div class="stat-label"><i class="bi bi-box-arrow-right"></i> OUT</div>
                <div class="stat-value" style="font-size: 2.5rem;">{{ number_format($studentStats['out'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card live-stat" style="background: linear-gradient(135deg, #6366f1, #4f46e5);" data-stat="student-total">
                <div class="stat-label">Total</div>
                <div class="stat-value" style="font-size: 2.5rem;">{{ number_format($studentStats['total'] ?? 0) }}</div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Employees Statistics Cards -->
<div class="mb-4">
    <div class="section-title mb-3"><i class="bi bi-briefcase"></i> Employees</div>
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="stat-card live-stat" style="background: linear-gradient(135deg, #f59e0b, #d97706);" data-stat="employee-in">
                <div class="stat-label"><i class="bi bi-box-arrow-in-right"></i> IN</div>
                <div class="stat-value" style="font-size: 2.5rem;">{{ number_format($employeeStats['in'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card live-stat" style="background: linear-gradient(135deg, #ec4899, #db2777);" data-stat="employee-out">
                <div class="stat-label"><i class="bi bi-box-arrow-right"></i> OUT</div>
                <div class="stat-value" style="font-size: 2.5rem;">{{ number_format($employeeStats['out'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card live-stat" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);" data-stat="employee-total">
                <div class="stat-label">Total</div>
                <div class="stat-value" style="font-size: 2.5rem;">{{ number_format($employeeStats['total'] ?? 0) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="brand-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title mb-0"><i class="bi bi-list-ul"></i> Punch Records</div>
        <div class="text-muted small punch-records-total">{{ $rows->total() }} total records</div>
    </div>
    
    @if($groupedRows && $groupedRows->count() > 0)
        @foreach ($groupedRows as $rollNumber => $studentPunches)
            @php
                $firstPunch = $studentPunches->first();
                $dailyPairs = $studentPairs[$rollNumber] ?? [];
                usort($dailyPairs, function($a, $b) { return strcmp($b['date'], $a['date']); });
                $rendered = false;
                $displayName = $firstPunch->student_name ?? $firstPunch->employee_name;
                $isEmployee = !empty($firstPunch->employee_name) && empty($firstPunch->student_name);
                
                // Get date-specific stats for the selected date
                $rollStr = (string) $rollNumber;
                $selectedDate = $filters['date'] ?? date('Y-m-d');
                $dateStats = $studentDateStats[$rollStr][$selectedDate] ?? null;
                
                // If viewing a single date, use date-specific count; otherwise use total count
                if ($dateStats && $filters['date_from'] === $filters['date_to']) {
                    $punchCount = $dateStats['punch_count'];
                } else {
                    $punchCount = $studentPunches->count();
                }
            @endphp
            <div class="card mb-3 punch-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                            @if($displayName)
                                @if($firstPunch->student_name)
                                    <a href="{{ route('students.show', $rollNumber) }}" class="text-decoration-none text-dark fw-bold" style="font-size: 1rem;">
                                        {{ $displayName }}
                                    </a>
                                    @if($firstPunch->student_deleted_at || $firstPunch->student_discontinued_at)
                                        <span class="badge bg-warning text-dark" title="Discontinued" style="font-size: 0.7rem;">
                                            <i class="bi bi-x-circle"></i> Discontinued
                                        </span>
                                    @endif
                                @elseif($firstPunch->employee_name)
                                    <a href="{{ route('employees.show', $rollNumber) }}" class="text-decoration-none text-dark fw-bold" style="font-size: 1rem;">
                                        {{ $displayName }}
                                    </a>
                                    @if($firstPunch->employee_discontinued_at || !$firstPunch->employee_is_active)
                                        <span class="badge bg-warning text-dark" title="Discontinued" style="font-size: 0.7rem;">
                                            <i class="bi bi-x-circle"></i> Discontinued
                                        </span>
                                    @endif
                                @else
                                    <span class="fw-bold text-dark" style="font-size: 1rem;">{{ $displayName }}</span>
                                @endif
                            @else
                                <span class="fw-bold text-warning" style="font-size: 1rem;">Unmapped</span>
                            @endif
                            @if($isEmployee || $isEmployeeView)
                                <span class="badge bg-dark" title="Employee" style="font-size: 0.7rem;">Employee</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center flex-wrap gap-1" style="font-size: 0.85rem;">
                            <span class="text-muted"><i class="bi bi-person-badge"></i> {{ $rollNumber }}</span>
                            @if($firstPunch->class_course)
                                <span class="punch-chip">
                                    <i class="bi bi-book"></i> {{ $firstPunch->class_course }}
                                </span>
                            @elseif($firstPunch->employee_category)
                                <span class="punch-chip">
                                    <i class="bi bi-briefcase"></i> {{ $firstPunch->employee_category === 'academic' ? 'Academic' : 'Non-academic' }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-primary">{{ $punchCount }} {{ $punchCount === 1 ? 'punch' : 'punches' }}</span>
                        @if($dateStats && $filters['date_from'] === $filters['date_to'])
                            @php $d = $dateStats; @endphp
                            <span class="badge bg-info text-dark" title="Total duration for {{ $selectedDate }}">
                                {{ $d['duration_hours'] }}h {{ $d['duration_minutes'] }}m
                            </span>
                        @elseif(isset($durationByRoll[$rollNumber]))
                            @php $d = $durationByRoll[$rollNumber]; @endphp
                            <span class="badge bg-info text-dark" title="Total duration across all IN–OUT pairs in range">
                                {{ $d['hours'] }}h {{ $d['minutes'] }}m
                            </span>
                        @endif
                        @if(!$firstPunch->student_name && !$firstPunch->employee_name)
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary {{ $isEmployeeView ? 'd-none' : '' }} create-student-btn"
                                    data-roll="{{ $rollNumber }}">
                                <i class="bi bi-person-plus"></i> <span class="d-none d-md-inline">Create student</span>
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary create-employee-btn"
                                    data-roll="{{ $rollNumber }}">
                                <i class="bi bi-briefcase"></i> <span class="d-none d-md-inline">Create employee</span>
                            </button>
                        @else
                            @php
                                $canMark = true;
                                if(!$isEmployeeView) {
                                    $canMark = empty($allowedClasses) || in_array($firstPunch->class_course, $allowedClasses);
                                } else {
                                    $canMark = $canViewEmployees;
                                }
                            @endphp
                            @if(!$canMark)
                                <span class="badge bg-secondary">No mark rights</span>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.95rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="font-size: 0.9rem;"><i class="bi bi-calendar"></i> Date</th>
                                    <th style="font-size: 0.9rem;"><i class="bi bi-box-arrow-in-right text-success"></i> IN Time</th>
                                    <th style="font-size: 0.9rem;"><i class="bi bi-box-arrow-right text-danger"></i> OUT Time</th>
                                    <th style="font-size: 0.9rem;"><i class="bi bi-clock"></i> Duration</th>
                                    <th style="font-size: 0.9rem;"><i class="bi bi-diagram-3"></i> Pair</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dailyPairs as $d)
                                    @php
                                        $dateObj = \Carbon\Carbon::parse($d['date']);
                                        $hasPairs = !empty($d['pairs']);
                                    @endphp
                                    @if($hasPairs)
                                        @foreach ($d['pairs'] as $pairIndex => $pair)
                                            @php $rendered = true; @endphp
                                            <tr>
                                                @if($pairIndex === 0)
                                                    <td rowspan="{{ count($d['pairs']) }}" class="align-middle" style="font-size: 0.9rem;">
                                                        <div class="fw-medium">{{ $dateObj->format('M d, Y') }}</div>
                                                        <small class="text-muted">{{ $dateObj->format('D') }}</small>
                                                    </td>
                                                @endif
                                                <td>
                                                    @if($pair['in'])
                                                        <div>
                                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                                <span class="badge bg-success" style="font-size: 0.9rem;"><i class="bi bi-box-arrow-in-right"></i> {{ $pair['in'] }}</span>
                                                                @if(!empty($pair['is_manual_in']))
                                                                    <span class="badge bg-warning text-dark" style="font-size: 0.7rem;" title="Manually marked IN{{ isset($pair['marked_by_in']) && $pair['marked_by_in'] ? ' by ' . $pair['marked_by_in']->name : '' }}">
                                                                        <i class="bi bi-pencil"></i> Manual
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @if(!empty($pair['is_manual_in']) && isset($pair['marked_by_in']) && $pair['marked_by_in'])
                                                                <div class="mt-1 d-flex align-items-center gap-1">
                                                                    <small class="text-primary" style="font-size: 0.8rem; font-weight: 500;">
                                                                        <i class="bi bi-person-fill"></i> Marked by: <strong style="color: #1e40af;">{{ is_object($pair['marked_by_in']) ? $pair['marked_by_in']->name : $pair['marked_by_in'] }}</strong>
                                                                    </small>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($pair['out'])
                                                        <div>
                                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                                <span class="badge bg-danger" style="font-size: 0.9rem;"><i class="bi bi-box-arrow-right"></i> {{ $pair['out'] }}</span>
                                                                @if(!empty($pair['is_manual_out']))
                                                                    <span class="badge bg-warning text-dark" style="font-size: 0.7rem;" title="Manually marked OUT{{ isset($pair['marked_by_out']) && $pair['marked_by_out'] ? ' by ' . $pair['marked_by_out']->name : '' }}">
                                                                        <i class="bi bi-pencil"></i> Manual
                                                                    </span>
                                                                @endif
                                                                @if(isset($pair['is_auto_out']) && $pair['is_auto_out'])
                                                                    <span class="badge bg-info text-dark" style="font-size: 0.7rem;" title="Automatically marked OUT at 7 PM">
                                                                        <i class="bi bi-clock"></i> Auto OUT
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @if(!empty($pair['is_manual_out']) && isset($pair['marked_by_out']) && $pair['marked_by_out'])
                                                                <div class="mt-1 d-flex align-items-center gap-1">
                                                                    <small class="text-primary" style="font-size: 0.8rem; font-weight: 500;">
                                                                        <i class="bi bi-person-fill"></i> Marked by: <strong style="color: #1e40af;">{{ is_object($pair['marked_by_out']) ? $pair['marked_by_out']->name : $pair['marked_by_out'] }}</strong>
                                                                    </small>
                                                                </div>
                                                            @endif
                                                        </div>
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
                                                        <span class="text-muted" style="font-size: 0.9rem;">{{ $duration->format('%h:%I') }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary" style="font-size: 0.85rem;">{{ $pairIndex + 1 }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        @php $rendered = true; @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-medium">{{ $dateObj->format('M d, Y') }}</div>
                                                <small class="text-muted">{{ $dateObj->format('D') }}</small>
                                            </td>
                                            <td colspan="4" class="text-muted">No valid attendance pairs</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile-friendly card view -->
                    <div class="mobile-punch-list">
                        @foreach ($dailyPairs as $d)
                            @php
                                $dateObj = \Carbon\Carbon::parse($d['date']);
                                $hasPairs = !empty($d['pairs']);
                            @endphp
                            @if($hasPairs)
                                @foreach ($d['pairs'] as $pairIndex => $pair)
                                    <div class="mobile-punch-item">
                                        <div class="mobile-punch-date">
                                            <i class="bi bi-calendar text-primary"></i>
                                            <span>{{ $dateObj->format('M d, Y') }}</span>
                                            <small class="text-muted ms-2">({{ $dateObj->format('D') }})</small>
                                            <span class="badge bg-secondary ms-auto" style="font-size: 0.7rem;">Pair {{ $pairIndex + 1 }}</span>
                                        </div>
                                        <div class="mobile-punch-times">
                                            <div class="mobile-punch-time-row">
                                                <strong>IN:</strong>
                                                @if($pair['in'])
                                                    <span class="badge bg-success" style="font-size: 0.85rem;">
                                                        <i class="bi bi-box-arrow-in-right"></i> {{ $pair['in'] }}
                                                    </span>
                                                    @if(!empty($pair['is_manual_in']))
                                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                                            <i class="bi bi-pencil"></i> Manual
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                            @if(!empty($pair['is_manual_in']) && isset($pair['marked_by_in']) && $pair['marked_by_in'])
                                                <div class="ms-4 mt-1 mb-2">
                                                    <small class="text-primary" style="font-size: 0.8rem; font-weight: 500;">
                                                        <i class="bi bi-person-fill"></i> Marked by: <strong style="color: #1e40af;">{{ is_object($pair['marked_by_in']) ? $pair['marked_by_in']->name : $pair['marked_by_in'] }}</strong>
                                                    </small>
                                                </div>
                                            @endif
                                            
                                            <div class="mobile-punch-time-row">
                                                <strong>OUT:</strong>
                                                @if($pair['out'])
                                                    <span class="badge bg-danger" style="font-size: 0.85rem;">
                                                        <i class="bi bi-box-arrow-right"></i> {{ $pair['out'] }}
                                                    </span>
                                                    @if(!empty($pair['is_manual_out']))
                                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                                            <i class="bi bi-pencil"></i> Manual
                                                        </span>
                                                    @endif
                                                    @if(isset($pair['is_auto_out']) && $pair['is_auto_out'])
                                                        <span class="badge bg-info text-dark" style="font-size: 0.7rem;" title="Automatically marked OUT at 7 PM">
                                                            <i class="bi bi-clock"></i> Auto OUT
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                            @if(!empty($pair['is_manual_out']) && isset($pair['marked_by_out']) && $pair['marked_by_out'])
                                                <div class="ms-4 mt-1 mb-2">
                                                    <small class="text-primary" style="font-size: 0.8rem; font-weight: 500;">
                                                        <i class="bi bi-person-fill"></i> Marked by: <strong style="color: #1e40af;">{{ is_object($pair['marked_by_out']) ? $pair['marked_by_out']->name : $pair['marked_by_out'] }}</strong>
                                                    </small>
                                                </div>
                                            @endif
                                            
                                            @if($pair['in'] && $pair['out'])
                                                @php
                                                    $inTime = \Carbon\Carbon::parse($d['date'] . ' ' . $pair['in']);
                                                    $outTime = \Carbon\Carbon::parse($d['date'] . ' ' . $pair['out']);
                                                    $duration = $inTime->diff($outTime);
                                                @endphp
                                                <div class="mobile-punch-duration">
                                                    <i class="bi bi-clock text-primary"></i> Duration: <strong>{{ $duration->format('%h:%I') }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="mobile-punch-item">
                                    <div class="mobile-punch-date">
                                        <i class="bi bi-calendar text-primary"></i>
                                        <span>{{ $dateObj->format('M d, Y') }}</span>
                                        <small class="text-muted ms-2">({{ $dateObj->format('D') }})</small>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.85rem;">No valid attendance pairs</div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if($isEmployee && auth()->user()->isSuperAdmin())
                    <!-- Manual Attendance Buttons for Employees (Super Admin Only) -->
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex gap-2 flex-wrap">
                            @php
                                // Check attendance status for the selected date
                                $selectedDate = $filters['date'] ?? date('Y-m-d');
                                $hasInForDate = false;
                                $hasOutForDate = false;
                                
                                // Check if this date has IN/OUT in the pairs
                                foreach ($dailyPairs as $d) {
                                    if ($d['date'] === $selectedDate) {
                                        foreach ($d['pairs'] as $pair) {
                                            if ($pair['in']) {
                                                $hasInForDate = true;
                                            }
                                            if ($pair['out']) {
                                                $hasOutForDate = true;
                                            }
                                        }
                                        break;
                                    }
                                }
                            @endphp
                            
                            @if(!$hasInForDate)
                                <button type="button" class="btn btn-success btn-sm mark-in-btn-employee" 
                                        data-roll="{{ $rollNumber }}" 
                                        data-date="{{ $selectedDate }}">
                                    <i class="bi bi-box-arrow-in-right"></i> Mark IN
                                </button>
                            @endif
                            
                            @if($hasInForDate && !$hasOutForDate)
                                <button type="button" class="btn btn-danger btn-sm mark-out-btn-employee" 
                                        data-roll="{{ $rollNumber }}" 
                                        data-date="{{ $selectedDate }}">
                                    <i class="bi bi-box-arrow-right"></i> Mark OUT
                                </button>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(!$rendered)
                        <div class="table-responsive mt-2 d-none d-md-block">
                            <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.95rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-calendar"></i> Date</th>
                                        <th><i class="bi bi-box-arrow-in-right text-success"></i> IN Time</th>
                                        <th><i class="bi bi-box-arrow-right text-danger"></i> OUT Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $raw = $studentPunches->sortByDesc('punch_date')->sortByDesc('punch_time')->values(); @endphp
                                    @foreach($raw as $r)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($r->punch_date)->format('M d, Y') }}</td>
                                            <td>
                                                <span class="badge bg-success" style="font-size: 0.9rem;">
                                                    <i class="bi bi-box-arrow-in-right"></i> {{ $r->punch_time }}
                                                </span>
                                            </td>
                                            <td><span class="text-muted">—</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- Mobile view for raw punches -->
                        <div class="mobile-punch-list d-md-none">
                            @php $raw = $studentPunches->sortByDesc('punch_date')->sortByDesc('punch_time')->values(); @endphp
                            @foreach($raw as $r)
                                <div class="mobile-punch-item">
                                    <div class="mobile-punch-date">
                                        <i class="bi bi-calendar text-primary"></i>
                                        <span>{{ \Carbon\Carbon::parse($r->punch_date)->format('M d, Y') }}</span>
                                        <small class="text-muted ms-2">({{ \Carbon\Carbon::parse($r->punch_date)->format('D') }})</small>
                                    </div>
                                    <div class="mobile-punch-times">
                                        <div class="mobile-punch-time-row">
                                            <strong>IN:</strong>
                                            <span class="badge bg-success" style="font-size: 0.85rem;">
                                                <i class="bi bi-box-arrow-in-right"></i> {{ $r->punch_time }}
                                            </span>
                                        </div>
                                        <div class="mobile-punch-time-row">
                                            <strong>OUT:</strong>
                                            <span class="text-muted">—</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="text-center py-4">
            <i class="bi bi-inbox" style="font-size: 2rem; color: var(--brand-muted);"></i>
            <div class="mt-2 text-muted">No punches found.</div>
        </div>
    @endif
@endif

    <div class="mt-3">
        {{ $rows->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- Create Student Modal -->
<div class="modal fade" id="createStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="csModalTitle">Create Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createStudentForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" id="csRollLabel">Roll Number</label>
                        <input type="text" class="form-control" id="csRoll" name="roll_number" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" id="csName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Father's Name</label>
                        <input type="text" class="form-control" id="csFather" name="father_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Mobile</label>
                        <input type="text" class="form-control" id="csPhone" name="parent_phone" placeholder="+91XXXXXXXXXX or 10-digit">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="csIsEmployee">
                        <label class="form-check-label" for="csIsEmployee">Create as Employee</label>
                    </div>
                    <div class="mb-3 student-only">
                        <label class="form-label">Program / Class</label>
                        <select class="form-select" id="csClass" name="class_course">
                            @forelse($courses as $course)
                                <option value="{{ $course->name }}">{{ $course->name }}</option>
                            @empty
                                <option value="Default Program">Default Program</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="mb-3 employee-only d-none">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="csCategory" name="category">
                            <option value="academic">Academic</option>
                            <option value="non_academic">Non-academic</option>
                        </select>
                    </div>
                </form>
                <div class="alert alert-info small" id="csInfoText">
                    The student will be created and mapped to the roll number so future punches display their details.
                </div>
                <div class="alert alert-danger d-none" id="csError"></div>
                <div class="alert alert-success d-none" id="csSuccess"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="csSaveBtn">
                    <i class="bi bi-check-circle"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Time Input Modal for Employee Manual Attendance -->
@if($isEmployeeView && auth()->user()->isSuperAdmin())
<div class="modal fade" id="timeInputModalEmployee" tabindex="-1" aria-labelledby="timeInputModalEmployeeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timeInputModalEmployeeLabel">Enter Time</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="timeInputFormEmployee">
                    <div class="mb-3">
                        <label for="punchTimeEmployee" class="form-label">
                            <i class="bi bi-clock"></i> Time (HH:MM format)
                        </label>
                        <input type="time" class="form-control" id="punchTimeEmployee" name="punchTime" required>
                        <div class="form-text">Enter the time for this manual attendance entry</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmTimeBtnEmployee">
                    <i class="bi bi-check-circle"></i> Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Toast for Employee -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11;">
    <div id="toastEmployee" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto" id="toast-title-employee">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toast-message-employee"></div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const createStudentModal = new bootstrap.Modal(document.getElementById('createStudentModal'));
    const csRoll = document.getElementById('csRoll');
    const csName = document.getElementById('csName');
    const csFather = document.getElementById('csFather');
    const csPhone = document.getElementById('csPhone');
    const csClass = document.getElementById('csClass');
    const csCategory = document.getElementById('csCategory');
    const csIsEmployee = document.getElementById('csIsEmployee');
    const studentOnly = document.querySelectorAll('.student-only');
    const employeeOnly = document.querySelectorAll('.employee-only');
    const csError = document.getElementById('csError');
    const csSuccess = document.getElementById('csSuccess');
    const csSaveBtn = document.getElementById('csSaveBtn');
    const classOptions = @json($courses->pluck('name'));
    const defaultClass = classOptions.length ? classOptions[0] : 'Default Program';

    const csModalTitle = document.getElementById('csModalTitle');
    const csRollLabel = document.getElementById('csRollLabel');
    const csInfoText = document.getElementById('csInfoText');

    function setModeEmployee(isEmployee) {
        if (isEmployee) {
            studentOnly.forEach(el => el.classList.add('d-none'));
            employeeOnly.forEach(el => el.classList.remove('d-none'));
            csModalTitle.textContent = 'Create Employee';
            csRollLabel.textContent = 'Employee ID';
            csInfoText.textContent = 'The employee will be created and mapped to the employee ID so future punches display their details.';
        } else {
            studentOnly.forEach(el => el.classList.remove('d-none'));
            employeeOnly.forEach(el => el.classList.add('d-none'));
            csModalTitle.textContent = 'Create Student';
            csRollLabel.textContent = 'Roll Number';
            csInfoText.textContent = 'The student will be created and mapped to the roll number so future punches display their details.';
        }
        csIsEmployee.checked = isEmployee;
    }

    document.querySelectorAll('.create-student-btn, .create-employee-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const roll = this.dataset.roll || '';
            const asEmployee = this.classList.contains('create-employee-btn');
            csRoll.value = roll;
            csName.value = '';
            csFather.value = '';
            csPhone.value = '';
            csClass.value = defaultClass;
            csCategory.value = 'academic';
            setModeEmployee(asEmployee);
            csError.classList.add('d-none');
            csSuccess.classList.add('d-none');
            createStudentModal.show();
        });
    });

    csIsEmployee.addEventListener('change', function() {
        setModeEmployee(this.checked);
    });

    csSaveBtn.addEventListener('click', function() {
        csError.classList.add('d-none');
        csSuccess.classList.add('d-none');

        const asEmployee = csIsEmployee.checked;
        const payload = {
            roll_number: csRoll.value.trim(),
            name: csName.value.trim(),
            father_name: csFather.value.trim(),
            parent_phone: csPhone.value.trim(),
            _token: '{{ csrf_token() }}'
        };
        if (asEmployee) {
            payload.category = csCategory.value || 'academic';
        } else {
            payload.class_course = csClass.value.trim() || defaultClass;
        }

        if (!payload.roll_number || !payload.name) {
            const idLabel = asEmployee ? 'Employee ID' : 'Roll number';
            csError.textContent = idLabel + ' and name are required.';
            csError.classList.remove('d-none');
            return;
        }

        csSaveBtn.disabled = true;
        csSaveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        const endpoint = asEmployee ? '{{ route('employees.create-from-punch') }}' : '{{ route('students.create-from-punch') }}';

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(async (res) => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                throw new Error(data.message || 'Failed to create student.');
            }
            csSuccess.textContent = data.message || 'Student created.';
            csSuccess.classList.remove('d-none');
            setTimeout(() => window.location.reload(), 800);
        })
        .catch(err => {
            csError.textContent = err.message || 'Failed to create student.';
            csError.classList.remove('d-none');
        })
        .finally(() => {
            csSaveBtn.disabled = false;
            csSaveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Save';
        });
    });
    
    // Make IN/OUT stat cards clickable to filter
    document.querySelectorAll('.clickable-stat').forEach(card => {
        card.addEventListener('click', function() {
            const filterState = this.dataset.filterState;
            const url = new URL(window.location.href);
            
            // Toggle filter: if already filtering by this state, remove filter; otherwise set it
            const currentFilter = url.searchParams.get('filter_state');
            if (currentFilter === filterState) {
                url.searchParams.delete('filter_state');
            } else {
                url.searchParams.set('filter_state', filterState);
            }
            
            // Reset to page 1 when filtering
            url.searchParams.set('page', '1');
            
            window.location.href = url.toString();
        });
        
        // Add hover effect
        card.addEventListener('mouseenter', function() {
            this.style.opacity = '0.8';
            this.style.transform = 'scale(1.02)';
            this.style.transition = 'all 0.2s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.opacity = '1';
            this.style.transform = 'scale(1)';
        });
    });
    
    // Highlight active filter
    const currentFilter = new URLSearchParams(window.location.search).get('filter_state');
    if (currentFilter) {
        document.querySelectorAll('.clickable-stat').forEach(card => {
            if (card.dataset.filterState === currentFilter) {
                card.style.boxShadow = '0 0 0 3px rgba(0, 123, 255, 0.5)';
                card.style.borderColor = '#007bff';
            }
        });
    }

    // Prevent browser autocomplete and clear any prefilled values on page load
    const rollInput = document.getElementById('filterRoll');
    const nameInput = document.getElementById('filterName');
    
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

    // Auto-refresh page every 2 minutes (120 seconds)
    let autoRefreshInterval;
    let countdownInterval;
    let refreshCountdown = 120; // 2 minutes in seconds

    // Update countdown display
    function updateCountdown() {
        const minutes = Math.floor(refreshCountdown / 60);
        const seconds = refreshCountdown % 60;
        const countdownEl = document.getElementById('autoRefreshCountdown');
        if (countdownEl) {
            countdownEl.textContent = `Auto-refresh in ${minutes}:${String(seconds).padStart(2, '0')}`;
        }
    }

    // Start countdown timer
    updateCountdown();
    countdownInterval = setInterval(function() {
        refreshCountdown--;
        updateCountdown();
        
        if (refreshCountdown <= 0) {
            refreshCountdown = 120; // Reset to 2 minutes
        }
    }, 1000);

    // Auto-refresh function - reloads the page with current filters
    function refreshPage() {
        // Preserve current URL (including filters) and reload
        window.location.reload();
    }

    // Start auto-refresh interval - reload every 2 minutes (120,000 milliseconds)
    // Continues even when tab is hidden
    autoRefreshInterval = setInterval(refreshPage, 120000);

    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    });

    @if($isEmployeeView && auth()->user()->isSuperAdmin())
    // Employee Manual Attendance Marking
    const toastEmployeeEl = document.getElementById('toastEmployee');
    const toastEmployeeTitle = document.getElementById('toast-title-employee');
    const toastEmployeeMessage = document.getElementById('toast-message-employee');
    const toastEmployee = toastEmployeeEl ? new bootstrap.Toast(toastEmployeeEl) : null;
    
    const timeInputModalEmployee = document.getElementById('timeInputModalEmployee');
    const timeInputModalEmployeeInstance = timeInputModalEmployee ? new bootstrap.Modal(timeInputModalEmployee) : null;
    const punchTimeInputEmployee = document.getElementById('punchTimeEmployee');
    const confirmTimeBtnEmployee = document.getElementById('confirmTimeBtnEmployee');
    const timeInputFormEmployee = document.getElementById('timeInputFormEmployee');
    
    let pendingActionEmployee = null;
    let pendingRollEmployee = null;
    let pendingDateEmployee = null;
    
    function showToastEmployee(title, message, type = 'success') {
        if (!toastEmployee) return;
        toastEmployeeTitle.textContent = title;
        toastEmployeeMessage.textContent = message;
        toastEmployeeEl.className = 'toast';
        if (type === 'success') {
            toastEmployeeEl.classList.add('text-bg-success');
        } else {
            toastEmployeeEl.classList.add('text-bg-danger');
        }
        toastEmployee.show();
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
            if (timeInputModalEmployeeInstance) timeInputModalEmployeeInstance.hide();
            if (data.success) {
                showToastEmployee('Success', data.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToastEmployee('Error', data.message || 'Failed to mark attendance', 'error');
            }
        })
        .catch(error => {
            if (timeInputModalEmployeeInstance) timeInputModalEmployeeInstance.hide();
            showToastEmployee('Error', 'An error occurred. Please try again.', 'error');
            console.error('Error:', error);
        });
    }
    
    // Handle Mark IN buttons for employees
    document.querySelectorAll('.mark-in-btn-employee').forEach(btn => {
        btn.addEventListener('click', function() {
            pendingActionEmployee = 'present';
            pendingRollEmployee = this.getAttribute('data-roll');
            pendingDateEmployee = this.getAttribute('data-date');
            
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            if (punchTimeInputEmployee) punchTimeInputEmployee.value = `${hours}:${minutes}`;
            
            if (timeInputModalEmployeeInstance) timeInputModalEmployeeInstance.show();
        });
    });
    
    // Handle Mark OUT buttons for employees
    document.querySelectorAll('.mark-out-btn-employee').forEach(btn => {
        btn.addEventListener('click', function() {
            pendingActionEmployee = 'out';
            pendingRollEmployee = this.getAttribute('data-roll');
            pendingDateEmployee = this.getAttribute('data-date');
            
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            if (punchTimeInputEmployee) punchTimeInputEmployee.value = `${hours}:${minutes}`;
            
            if (timeInputModalEmployeeInstance) timeInputModalEmployeeInstance.show();
        });
    });
    
    // Handle confirm button
    if (confirmTimeBtnEmployee) {
        confirmTimeBtnEmployee.addEventListener('click', function() {
            if (!punchTimeInputEmployee || !punchTimeInputEmployee.value) {
                showToastEmployee('Error', 'Please enter a time', 'error');
                return;
            }
            
            if (pendingActionEmployee && pendingRollEmployee && pendingDateEmployee) {
                const timeValue = punchTimeInputEmployee.value;
                submitEmployeeAttendance(pendingActionEmployee, pendingRollEmployee, pendingDateEmployee, timeValue);
            }
        });
    }
    
    // Reset modal when closed
    if (timeInputModalEmployee) {
        timeInputModalEmployee.addEventListener('hidden.bs.modal', function() {
            if (timeInputFormEmployee) timeInputFormEmployee.reset();
            pendingActionEmployee = null;
            pendingRollEmployee = null;
            pendingDateEmployee = null;
        });
    }
    @endif
});
</script>
@endpush
