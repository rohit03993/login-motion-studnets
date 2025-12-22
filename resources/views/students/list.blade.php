@extends('layouts.app', ['title' => 'Students List'])

@section('content')
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

<div class="brand-card mb-3">
    <form method="GET" action="{{ route('students.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
            <label class="form-label"><i class="bi bi-search"></i> Search</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" 
                   placeholder="Search by roll number, name, father's name, or phone...">
        </div>
        {{-- Batch filter removed --}}
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search"></i> Search
            </button>
        </div>
        @if($search || $batch)
            <div class="col-12">
                <a href="{{ route('students.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        @endif
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
            <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-cloud-arrow-up"></i> Upload
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
        <div class="d-flex gap-2">
            <select id="bulkAction" class="form-select form-select-sm" style="width: auto;" disabled>
                <option value="">Bulk Actions...</option>
                <option value="assign-class">Assign to Class</option>
            </select>
            <button id="clearSelection" class="btn btn-sm btn-outline-secondary" style="display: none;">
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
                        </td>
                        <td>
                            @if($student->name)
                                <a href="{{ route('students.show', $student->roll_number) }}" class="text-decoration-none fw-medium" style="color: var(--brand-accent-2);">
                                    {{ $student->name }}
                                </a>
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
});
</script>
@endpush
@endif
@endsection

