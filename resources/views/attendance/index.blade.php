@extends('layouts.app', ['title' => 'Live Attendance'])

@section('content')
<style>
    .live-stat {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        border: none;
        box-shadow: 0 15px 35px rgba(79, 70, 229, 0.25);
    }
    .live-stat .stat-label { color: rgba(255,255,255,0.82); }
    .live-stat .stat-value { color: #fff; }
    .punch-card {
        border: 1px solid #e9edf5;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(15,23,42,0.08);
        overflow: hidden;
    }
    .punch-card .card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
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
        background: linear-gradient(135deg, #ecfeff, #eef2ff);
        border: 1px solid #e0f2fe;
        box-shadow: 0 10px 30px rgba(15,23,42,0.06);
    }
    @media (max-width: 768px) {
        .live-stat .stat-value { font-size: 18px; }
        .stat-card { margin-bottom: 8px; }
    }
</style>
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-clock-history"></i> {{ $isEmployeeView ? 'Employee Attendance' : 'Live Attendance Dashboard' }}
            </div>
            <div class="muted">{{ $isEmployeeView ? 'Real-time punches for employees' : 'Real-time punches from EasyTimePro' }}</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-success" href="{{ url('/attendance/export') }}?{{ http_build_query(request()->query()) }}">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>
</div>

<div class="brand-card mb-3 filters-card">
    <div class="section-title mb-3"><i class="bi bi-funnel"></i> Filters</div>
    <form class="row gy-2 gx-2 align-items-end" method="get" action="{{ url('/attendance') }}">
        <div class="col-12 col-md-4">
            <label class="form-label"><i class="bi bi-person-badge"></i> Roll / Employee ID</label>
            <input type="text" name="roll" value="{{ $filters['roll'] ?? '' }}" class="form-control" placeholder="e.g. 25175000xxx">
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label"><i class="bi bi-person"></i> Name (partial)</label>
            <input type="text" name="name" value="{{ $filters['name'] ?? '' }}" class="form-control" placeholder="Student name">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label"><i class="bi bi-calendar-event"></i> Date</label>
            <input type="date" name="date" value="{{ $filters['date'] ?? date('Y-m-d') }}" class="form-control" max="{{ date('Y-m-d') }}">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
        </div>
        <div class="col-auto">
            <a class="btn btn-outline-secondary" href="{{ url('/attendance') }}"><i class="bi bi-arrow-clockwise"></i> Reset</a>
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
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <div class="stat-card live-stat">
            <div class="stat-label">Total Punches</div>
            <div class="stat-value">{{ number_format($todayStats['total'] ?? 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card live-stat clickable-stat" data-filter-state="IN" style="cursor: pointer;" title="Click to filter students currently IN">
            <div class="stat-label"><i class="bi bi-box-arrow-in-right"></i> IN</div>
            <div class="stat-value">{{ $todayStats['in'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card live-stat clickable-stat" data-filter-state="OUT" style="cursor: pointer;" title="Click to filter students currently OUT">
            <div class="stat-label"><i class="bi bi-box-arrow-right"></i> OUT</div>
            <div class="stat-value">{{ $todayStats['out'] ?? 0 }}</div>
        </div>
    </div>
</div>

<div class="brand-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title mb-0"><i class="bi bi-list-ul"></i> Punch Records</div>
        <div class="text-muted small">{{ $rows->total() }} total records</div>
    </div>
    
    @if($groupedRows && $groupedRows->count() > 0)
        @foreach ($groupedRows as $rollNumber => $studentPunches)
            @php
                $firstPunch = $studentPunches->first();
                $punchCount = $studentPunches->count();
                $dailyPairs = $studentPairs[$rollNumber] ?? [];
                usort($dailyPairs, function($a, $b) { return strcmp($b['date'], $a['date']); });
                $rendered = false;
                $displayName = $firstPunch->student_name ?? $firstPunch->employee_name;
                $isEmployee = !empty($firstPunch->employee_name) && empty($firstPunch->student_name);
            @endphp
            <div class="card mb-3 punch-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        @if($displayName)
                            @if($firstPunch->student_name)
                                <a href="{{ route('students.show', $rollNumber) }}" class="text-decoration-none text-dark fw-bold">
                                    {{ $displayName }}
                                </a>
                            @else
                                <span class="fw-bold text-dark">{{ $displayName }}</span>
                            @endif
                        @else
                            <span class="fw-bold text-warning">Unmapped</span>
                        @endif
                        @if($isEmployee || $isEmployeeView)
                            <span class="badge bg-dark ms-2" title="Employee">Employee</span>
                        @endif
                        <span class="text-muted ms-2">(Roll: {{ $rollNumber }})</span>
                        @if($firstPunch->class_course)
                            <span class="punch-chip ms-2">
                                <i class="bi bi-book"></i> {{ $firstPunch->class_course }}
                            </span>
                        @elseif($firstPunch->employee_category)
                            <span class="punch-chip ms-2">
                                <i class="bi bi-briefcase"></i> {{ $firstPunch->employee_category === 'academic' ? 'Academic' : 'Non-academic' }}
                            </span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-primary">{{ $punchCount }} {{ $punchCount === 1 ? 'punch' : 'punches' }}</span>
                        @if(isset($durationByRoll[$rollNumber]))
                            @php $d = $durationByRoll[$rollNumber]; @endphp
                            <span class="badge bg-info text-dark" title="Total duration across all IN–OUT pairs in range">
                                {{ $d['hours'] }}h {{ $d['minutes'] }}m
                            </span>
                        @endif
                        @if(!$firstPunch->student_name && !$firstPunch->employee_name)
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary {{ $isEmployeeView ? 'd-none' : '' }} create-student-btn"
                                    data-roll="{{ $rollNumber }}">
                                <i class="bi bi-person-plus"></i> Create student
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary create-employee-btn"
                                    data-roll="{{ $rollNumber }}">
                                <i class="bi bi-briefcase"></i> Create employee
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
                                                            <span class="badge bg-success" style="font-size: 0.9rem;"><i class="bi bi-box-arrow-in-right"></i> {{ $pair['in'] }}</span>
                                                            @if(!empty($pair['is_manual_in']))
                                                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;" title="Manually marked IN">
                                                                    <i class="bi bi-pencil"></i> Manual
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($pair['out'])
                                                        <div>
                                                            <span class="badge bg-danger" style="font-size: 0.9rem;"><i class="bi bi-box-arrow-right"></i> {{ $pair['out'] }}</span>
                                                            @if(!empty($pair['is_manual_out']))
                                                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;" title="Manually marked OUT">
                                                                    <i class="bi bi-pencil"></i> Manual
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

                    @if(!$rendered)
                        <div class="table-responsive mt-2">
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
});
</script>
@endpush
