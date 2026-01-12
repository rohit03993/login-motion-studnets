@extends('layouts.app', ['title' => 'Students List'])

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
    /* Mobile optimizations */
    @media (max-width: 768px) {
        .filters-card-modern {
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
        /* Hide table on mobile, show cards */
        .table-responsive {
            display: none;
        }
        .students-mobile-list {
            display: block;
        }
        /* Mobile student card */
        .student-card-mobile {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .student-card-mobile .student-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .student-card-mobile .student-info {
            flex: 1;
        }
        .student-card-mobile .student-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--brand-accent-2);
            margin-bottom: 0.25rem;
        }
        .student-card-mobile .student-roll {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        .student-card-mobile .student-detail-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .student-card-mobile .student-detail-label {
            font-weight: 600;
            color: #475569;
            min-width: 100px;
        }
        .student-card-mobile .student-detail-value {
            color: #1e293b;
            flex: 1;
        }
        .student-card-mobile .student-actions {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #e2e8f0;
        }
        .student-card-mobile .btn {
            min-height: 44px;
            width: 100%;
        }
        .student-card-mobile .form-check {
            margin-bottom: 0.5rem;
        }
        .student-card-mobile .form-check-label {
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }
        /* Bulk actions mobile */
        @media (max-width: 768px) {
            #bulkAction,
            #clearSelection {
                min-height: 44px;
                font-size: 0.9rem;
            }
        }
    }
    /* Desktop: show table, hide mobile view */
    @media (min-width: 769px) {
        .students-mobile-list {
            display: none;
        }
        .table-responsive {
            display: block;
        }
    }
</style>
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-people"></i> All Students
            </div>
            <div class="muted">Complete list of all registered students</div>
        </div>
    </div>
    @if(session('success'))
        <div class="alert alert-success mt-3 mb-0">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mt-3 mb-0">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
</div>

<div class="brand-card mb-3 filters-card-modern">
    <div class="section-title mb-3"><i class="bi bi-funnel"></i> Filters</div>
    <form method="GET" action="{{ route('students.index') }}" class="row g-3" id="studentsListFilterForm">
        <div class="col-12 col-sm-6 col-md-3">
            <label class="filter-label-modern"><i class="bi bi-person-badge"></i> Roll Number</label>
            <input type="text" 
                   name="roll" 
                   id="studentListRoll" 
                   value="{{ request()->has('roll') && request('roll') !== '' ? request('roll') : '' }}" 
                   class="form-control filter-input-modern" 
                   placeholder="Enter roll number" 
                   autocomplete="off"
                   data-lpignore="true">
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <label class="filter-label-modern"><i class="bi bi-person"></i> Name</label>
            <input type="text" 
                   name="name" 
                   id="studentListName" 
                   value="{{ request()->has('name') && request('name') !== '' ? request('name') : '' }}" 
                   class="form-control filter-input-modern" 
                   placeholder="Enter name" 
                   autocomplete="off"
                   data-lpignore="true">
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <label class="filter-label-modern"><i class="bi bi-book"></i> Class</label>
            <select name="class" class="form-select filter-select-modern">
                <option value="">All Classes</option>
                @php
                    $courseClasses = \App\Models\Course::orderBy('name')->pluck('name')->toArray();
                    $studentClasses = \App\Models\Student::whereNotNull('class_course')
                        ->where('class_course', '!=', '')
                        ->whereNull('discontinued_at')
                        ->distinct()
                        ->orderBy('class_course')
                        ->pluck('class_course')
                        ->toArray();
                    $allClasses = collect($courseClasses)->merge($studentClasses)->unique()->sort()->values()->toArray();
                @endphp
                @foreach($allClasses as $cls)
                    <option value="{{ $cls }}" {{ request('class') === $cls ? 'selected' : '' }}>
                        {{ $cls }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <label class="filter-label-modern"><i class="bi bi-calendar-event"></i> Date</label>
            <input type="date" name="date" value="{{ request('date') }}" class="form-control filter-input-modern" max="{{ date('Y-m-d') }}">
        </div>
        <div class="col-12 col-sm-12 col-md-2 d-flex gap-2 align-items-end">
            <button type="submit" class="btn filter-btn-modern btn-sm flex-fill">
                <i class="bi bi-search"></i> <span class="d-none d-sm-inline">Filter</span>
            </button>
            @if(request('roll') || request('name') || request('class') || request('date'))
                <a href="{{ route('students.index') }}" class="btn reset-btn-modern btn-sm flex-fill">
                    <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline">Reset</span>
                </a>
            @endif
        </div>
    </form>
</div>

@if(auth()->user()->isSuperAdmin())
<div class="brand-card mb-3">
    <form id="importForm" method="POST" action="{{ route('students.import') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
        @csrf
        <div class="col-12 col-md-6">
            <label class="form-label"><i class="bi bi-upload"></i> Bulk Import (CSV)</label>
            <input type="file" name="file" id="csvFile" accept=".csv,.txt" class="form-control" required>
            <small class="text-muted">Upload CSV, then map columns.</small>
        </div>
        <div class="col-12 col-md-6">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <label class="form-label small">Roll #</label>
                    <select name="map_roll" id="map_roll" class="form-select form-select-sm" required></select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small">Name</label>
                    <select name="map_name" id="map_name" class="form-select form-select-sm" required></select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small">Father</label>
                    <select name="map_father" id="map_father" class="form-select form-select-sm">
                        <option value="">(none)</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small">Phone</label>
                    <select name="map_phone" id="map_phone" class="form-select form-select-sm">
                        <option value="">(none)</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-2 mt-2">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="overwrite_mode" id="overwriteMode" value="1">
                <label class="form-check-label small" for="overwriteMode" title="If checked, overwrites all fields. If unchecked, only updates provided fields and preserves existing class/batch.">
                    Overwrite all fields
                </label>
            </div>
            <button type="submit" class="btn btn-success w-100" style="min-height: 44px;">
                <i class="bi bi-cloud-arrow-up"></i> <span class="d-none d-sm-inline">Upload</span>
            </button>
        </div>
    </form>
</div>
@endif

@if(auth()->user()->isSuperAdmin())
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-md-between">
        <div>
            <span id="selectedCount" class="badge bg-primary" style="display: none;">0 selected</span>
        </div>
        <div class="d-flex gap-2 flex-wrap w-100 w-md-auto">
            <select id="bulkAction" class="form-select form-select-sm flex-fill flex-md-none" style="min-width: 150px;" disabled>
                <option value="">Bulk Actions...</option>
                <option value="assign-class">Assign to Class</option>
            </select>
            <button id="clearSelection" class="btn btn-sm btn-outline-secondary flex-fill flex-md-none" style="display: none; min-height: 38px;">
                <i class="bi bi-x-circle"></i> Clear
            </button>
        </div>
    </div>
</div>
@endif

<div class="brand-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title mb-0"><i class="bi bi-list-ul"></i> Students List</div>
        <div class="text-muted small">{{ $students->total() }} total students</div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    @if(auth()->user()->isSuperAdmin())
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" title="Select All">
                        </th>
                    @endif
                    <th>Roll Number / ID</th>
                    <th>Name</th>
                    <th>Father's Name</th>
                    <th>Parent Mobile</th>
                    <th>Last Punch</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        @if(auth()->user()->isSuperAdmin())
                            <td>
                                <input type="checkbox" class="student-checkbox" value="{{ $student->roll_number }}">
                            </td>
                        @endif
                        <td>
                            <a href="{{ route('students.show', $student->roll_number) }}" class="text-decoration-none fw-medium" style="color: var(--brand-accent-2);">
                                {{ $student->roll_number }}
                            </a>
                            @if($student->class_course)
                                <br><small class="text-muted">{{ $student->class_course }}</small>
                            @endif
                            @if($student->deleted_at || $student->discontinued_at)
                                <br><small class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                    <i class="bi bi-x-circle"></i> Discontinued
                                </small>
                            @endif
                        </td>
                        <td>
                            @if($student->name)
                                <a href="{{ route('students.show', $student->roll_number) }}" class="text-decoration-none fw-medium" style="color: var(--brand-accent-2);">
                                    {{ $student->name }}
                                </a>
                                @if($student->deleted_at || $student->discontinued_at)
                                    <br><small class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                        <i class="bi bi-x-circle"></i> Discontinued
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($student->father_name)
                                {{ $student->father_name }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($student->parent_phone)
                                <a href="tel:{{ $student->parent_phone }}" class="text-decoration-none">
                                    <i class="bi bi-telephone"></i> {{ $student->parent_phone }}
                                </a>
                                <br>
                                @if($student->alerts_enabled)
                                    <small class="badge bg-success" style="font-size: 0.7rem;">
                                        <i class="bi bi-bell"></i> Alerts ON
                                    </small>
                                @else
                                    <small class="badge bg-secondary" style="font-size: 0.7rem;">
                                        <i class="bi bi-bell-slash"></i> Alerts OFF
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($student->last_punch_date)
                                <div>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($student->last_punch_date)->format('M d, Y') }}</small>
                                </div>
                                @if($student->last_punch_time)
                                    <small class="text-muted">{{ $student->last_punch_time }}</small>
                                @endif
                            @else
                                <span class="text-muted">No punches yet</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('students.show', $student->roll_number) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? '7' : '6' }}" class="text-center text-muted py-5">
                            <i class="bi bi-inboxes" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">No students found</p>
                            @if($search)
                                <div class="mt-2">
                                    <a href="{{ route('students.index') }}" class="btn btn-sm btn-outline-primary">
                                        View All Students
                                    </a>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Mobile-friendly card view -->
    <div class="students-mobile-list">
        @forelse($students as $student)
            <div class="student-card-mobile">
                @if(auth()->user()->isSuperAdmin())
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input student-checkbox" value="{{ $student->roll_number }}" id="mobile-check-{{ $student->roll_number }}">
                        <label class="form-check-label" for="mobile-check-{{ $student->roll_number }}">Select</label>
                    </div>
                @endif
                <div class="student-header">
                    <div class="student-info">
                        <div class="student-name">
                            <a href="{{ route('students.show', $student->roll_number) }}" class="text-decoration-none" style="color: var(--brand-accent-2);">
                                {{ $student->name ?? 'N/A' }}
                            </a>
                            @if($student->deleted_at || $student->discontinued_at)
                                <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem;">
                                    <i class="bi bi-x-circle"></i> Discontinued
                                </span>
                            @endif
                        </div>
                        <div class="student-roll">
                            <i class="bi bi-person-badge"></i> 
                            <a href="{{ route('students.show', $student->roll_number) }}" class="text-decoration-none" style="color: var(--brand-accent-2);">
                                {{ $student->roll_number }}
                            </a>
                            @if($student->class_course)
                                <span class="badge bg-info text-dark ms-2" style="font-size: 0.7rem;">
                                    <i class="bi bi-book"></i> {{ $student->class_course }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="student-details">
                    @if($student->father_name)
                        <div class="student-detail-row">
                            <span class="student-detail-label"><i class="bi bi-person"></i> Father:</span>
                            <span class="student-detail-value">{{ $student->father_name }}</span>
                        </div>
                    @endif
                    
                    @if($student->parent_phone)
                        <div class="student-detail-row">
                            <span class="student-detail-label"><i class="bi bi-telephone"></i> Phone:</span>
                            <span class="student-detail-value">
                                <a href="tel:{{ $student->parent_phone }}" class="text-decoration-none">
                                    {{ $student->parent_phone }}
                                </a>
                                @if($student->alerts_enabled)
                                    <span class="badge bg-success ms-2" style="font-size: 0.7rem;">
                                        <i class="bi bi-bell"></i> Alerts ON
                                    </span>
                                @else
                                    <span class="badge bg-secondary ms-2" style="font-size: 0.7rem;">
                                        <i class="bi bi-bell-slash"></i> Alerts OFF
                                    </span>
                                @endif
                            </span>
                        </div>
                    @endif
                    
                    @if($student->last_punch_date)
                        <div class="student-detail-row">
                            <span class="student-detail-label"><i class="bi bi-clock-history"></i> Last Punch:</span>
                            <span class="student-detail-value">
                                <div>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($student->last_punch_date)->format('M d, Y') }}</small>
                                    @if($student->last_punch_time)
                                        <br><small class="text-muted">{{ $student->last_punch_time }}</small>
                                    @endif
                                </div>
                            </span>
                        </div>
                    @else
                        <div class="student-detail-row">
                            <span class="student-detail-label"><i class="bi bi-clock-history"></i> Last Punch:</span>
                            <span class="student-detail-value text-muted">No punches yet</span>
                        </div>
                    @endif
                </div>
                
                <div class="student-actions">
                    <a href="{{ route('students.show', $student->roll_number) }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-eye"></i> View Details
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-inboxes" style="font-size: 3rem;"></i>
                <p class="mt-3 mb-0">No students found</p>
                @if($search)
                    <div class="mt-2">
                        <a href="{{ route('students.index') }}" class="btn btn-sm btn-outline-primary">
                            View All Students
                        </a>
                    </div>
                @endif
            </div>
        @endforelse
    </div>

    @if($students->hasPages())
        <div class="mt-3">
            {{ $students->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@if(auth()->user()->isSuperAdmin())
<!-- Bulk Assign Class Modal -->
<div class="modal fade" id="bulkAssignClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Class to Selected Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>You are about to assign a class to <span id="classAssignCount" class="fw-bold">0</span> student(s).</p>
                <div class="mb-3">
                    <label for="bulkClassSelect" class="form-label">Select Class *</label>
                    <select class="form-select" id="bulkClassSelect" required>
                        <option value="">Choose a class...</option>
                        @foreach(\App\Models\Course::where('is_active', true)->orderBy('name')->get() as $course)
                            <option value="{{ $course->name }}">{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAssignClass">
                    <i class="bi bi-check-circle"></i> Assign Class
                </button>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSV header mapping
    const fileInput = document.getElementById('csvFile');
    const mapRoll = document.getElementById('map_roll');
    const mapName = document.getElementById('map_name');
    const mapFather = document.getElementById('map_father');
    const mapPhone = document.getElementById('map_phone');

    function populateMappingOptions(headers) {
        [mapRoll, mapName, mapFather, mapPhone].forEach(sel => {
            if (!sel) return;
            const current = sel.value;
            sel.innerHTML = (sel.id === 'map_father' || sel.id === 'map_phone')
                ? '<option value=\"\">(none)</option>'
                : '';
            headers.forEach(h => {
                const opt = document.createElement('option');
                opt.value = h;
                opt.textContent = h;
                sel.appendChild(opt);
            });
            if (current) sel.value = current;
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                const lines = text.split(/\\r?\\n/).filter(l => l.trim() !== '');
                if (!lines.length) return;
                const delims = [',',';','\\t'];
                let bestDelim = ',';
                let bestScore = -1;
                delims.forEach(d => {
                    const score = (lines[0].match(new RegExp(`\\${d}`,'g')) || []).length;
                    if (score > bestScore) { bestScore = score; bestDelim = d; }
                });
                let headerLine = lines[0];
                if (lines.length > 1) {
                    const firstParts = headerLine.split(bestDelim).map(h => h.trim());
                    const secondParts = lines[1].split(bestDelim).map(h => h.trim());
                    if (firstParts.length === 1 && secondParts.length > 1) {
                        headerLine = lines[1];
                    }
                }
                let headers = headerLine.split(bestDelim).map(h => h.trim());
                headers = headers.map((h, idx) => h !== '' ? h : `Column${idx+1}`);
                populateMappingOptions(headers);
            };
            reader.readAsText(file);
        });
    }

    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.student-checkbox');
    const bulkAction = document.getElementById('bulkAction');
    const selectedCount = document.getElementById('selectedCount');
    const clearSelection = document.getElementById('clearSelection');
    const bulkAssignClassModal = new bootstrap.Modal(document.getElementById('bulkAssignClassModal'));

    function updateSelectionUI() {
        const selected = Array.from(checkboxes).filter(cb => cb.checked);
        const count = selected.length;
        
        if (count > 0) {
            selectedCount.textContent = count + ' selected';
            selectedCount.style.display = 'inline-block';
            clearSelection.style.display = 'inline-block';
            bulkAction.disabled = false;
        } else {
            selectedCount.style.display = 'none';
            clearSelection.style.display = 'none';
            bulkAction.disabled = true;
            bulkAction.value = '';
        }
    }

    // Select all checkbox
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectionUI();
        });
    }

    // Individual checkboxes
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (selectAll) {
                selectAll.checked = Array.from(checkboxes).every(c => c.checked);
            }
            updateSelectionUI();
        });
    });

    // Clear selection
    if (clearSelection) {
        clearSelection.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
            if (selectAll) selectAll.checked = false;
            updateSelectionUI();
        });
    }

    // Bulk action dropdown
    if (bulkAction) {
        bulkAction.addEventListener('change', function() {
            const selected = Array.from(checkboxes).filter(cb => cb.checked);
            if (selected.length === 0) return;

            if (this.value === 'assign-class') {
                document.getElementById('classAssignCount').textContent = selected.length;
                bulkAssignClassModal.show();
            }
            
            this.value = ''; // Reset dropdown
        });
    }

    // Confirm assign class
    document.getElementById('confirmAssignClass')?.addEventListener('click', function() {
        const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
        const classCourse = document.getElementById('bulkClassSelect').value;
        
        if (!classCourse) {
            alert('Please select a class');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Assigning...';

        fetch('{{ route("students.bulk-assign-class") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                student_rolls: selected,
                class_course: classCourse
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bulkAssignClassModal.hide();
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Failed to assign class');
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-check-circle"></i> Assign Class';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-check-circle"></i> Assign Class';
        });
    });

    // Confirm assign batch
    // batch assignment removed

    // Prevent browser autocomplete and clear any prefilled values on page load
    const rollInput = document.getElementById('studentListRoll');
    const nameInput = document.getElementById('studentListName');
    
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
@endif
@endsection

