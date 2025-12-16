@extends('layouts.app', ['title' => 'Student Profile'])

@section('content')
<!-- Student Profile Header -->
<div class="brand-card mb-3">
    <div class="row g-3 align-items-center">
        <div class="col-12 col-md-8">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="section-title mb-0">
                    <i class="bi bi-person-circle"></i> Student Profile
                </div>
                <a href="{{ url('/attendance') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
            <div class="muted mb-3">Live attendance data from EasyTimePro</div>
            
            <div class="row g-3">
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="stat-label">Roll Number</div>
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
            <form class="row gy-2 gx-2" method="get" action="{{ url('/students/'.$roll) }}">
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
                    <a class="btn btn-outline-secondary w-100" href="{{ url('/students/'.$roll) }}"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Student Information Card -->
<div class="brand-card mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title mb-0"><i class="bi bi-info-circle"></i> Student Information</div>
        <button type="button" class="btn btn-outline-primary btn-sm" id="editStudentBtn">
            <i class="bi bi-pencil"></i> Edit
        </button>
    </div>
    
    @if ($errors->any())
        <div class="alert alert-danger py-2 mb-3">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
    @endif
    
    <!-- Read-Only View -->
    <div id="studentInfoView" class="row g-3">
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Name</div>
            <div class="fw-medium">{{ $student->name ?? '—' }}</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Father's Name</div>
            <div class="fw-medium">{{ $student->father_name ?? '—' }}</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Class/Course</div>
            <div class="fw-medium">{{ $student->class_course ?? '—' }}</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Batch</div>
            <div class="fw-medium">{{ $student->batch ?? '—' }}</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Primary Mobile</div>
            <div class="fw-medium">
                @if($student->parent_phone)
                    <a href="tel:{{ $student->parent_phone }}" class="text-decoration-none">
                        <i class="bi bi-telephone"></i> {{ $student->parent_phone }}
                    </a>
                @else
                    —
                @endif
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">Secondary Mobile</div>
            <div class="fw-medium">
                @if($student->parent_phone_secondary)
                    <a href="tel:{{ $student->parent_phone_secondary }}" class="text-decoration-none">
                        <i class="bi bi-telephone"></i> {{ $student->parent_phone_secondary }}
                    </a>
                @else
                    —
                @endif
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">WhatsApp Send To</div>
            <div>
                @if($student->whatsapp_send_to === 'primary')
                    <span class="badge bg-primary"><i class="bi bi-phone"></i> Primary Only</span>
                @elseif($student->whatsapp_send_to === 'secondary')
                    <span class="badge bg-info"><i class="bi bi-phone"></i> Secondary Only</span>
                @elseif($student->whatsapp_send_to === 'both')
                    <span class="badge bg-success"><i class="bi bi-phone"></i> Both Numbers</span>
                @else
                    <span class="badge bg-secondary">Primary Only</span>
                @endif
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="muted small mb-1">WhatsApp Alerts</div>
            <div>
                @if($student->alerts_enabled)
                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Enabled</span>
                @else
                    <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Disabled</span>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Edit Form (Hidden by default) -->
    <form method="post" action="{{ route('students.update', $roll) }}" id="studentInfoForm" class="row gy-2 gx-2" style="display: none;">
        @csrf
        <div class="col-12">
            <label class="form-label">Name</label>
            <input type="text" name="name" value="{{ old('name', $student->name) }}" class="form-control" placeholder="Student name">
        </div>
        <div class="col-12">
            <label class="form-label">Father's Name</label>
            <input type="text" name="father_name" value="{{ old('father_name', $student->father_name) }}" class="form-control" placeholder="Father's name">
        </div>
        <div class="col-6">
            <label class="form-label">Class/Course</label>
            <input type="text" name="class_course" value="{{ old('class_course', $student->class_course) }}" class="form-control" placeholder="Class">
        </div>
        <div class="col-6">
            <label class="form-label">Batch</label>
            <input type="text" name="batch" value="{{ old('batch', $student->batch) }}" class="form-control" placeholder="Batch">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Primary Mobile (+91 auto)</label>
            <input type="text" name="parent_phone" value="{{ old('parent_phone', $student->parent_phone) }}" class="form-control" placeholder="10-digit or +91XXXXXXXXXX">
            <div class="form-text small">Enter 10 digits; +91 will be auto-applied.</div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Secondary Mobile (+91 auto)</label>
            <input type="text" name="parent_phone_secondary" value="{{ old('parent_phone_secondary', $student->parent_phone_secondary) }}" class="form-control" placeholder="10-digit or +91XXXXXXXXXX">
            <div class="form-text small">Optional second number for WhatsApp alerts.</div>
        </div>
        <div class="col-12">
            <label class="form-label">Send WhatsApp To</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="whatsapp_send_to" id="whatsapp_primary" value="primary" {{ old('whatsapp_send_to', $student->whatsapp_send_to ?? 'primary') === 'primary' ? 'checked' : '' }}>
                <label class="form-check-label" for="whatsapp_primary">
                    <i class="bi bi-phone"></i> Primary number only
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="whatsapp_send_to" id="whatsapp_secondary" value="secondary" {{ old('whatsapp_send_to', $student->whatsapp_send_to ?? 'primary') === 'secondary' ? 'checked' : '' }}>
                <label class="form-check-label" for="whatsapp_secondary">
                    <i class="bi bi-phone"></i> Secondary number only
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="whatsapp_send_to" id="whatsapp_both" value="both" {{ old('whatsapp_send_to', $student->whatsapp_send_to ?? 'primary') === 'both' ? 'checked' : '' }}>
                <label class="form-check-label" for="whatsapp_both">
                    <i class="bi bi-phone"></i> Both numbers
                </label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="alerts_enabled" name="alerts_enabled" {{ old('alerts_enabled', $student->alerts_enabled) ? 'checked' : '' }}>
                <label class="form-check-label" for="alerts_enabled">
                    <i class="bi bi-bell"></i> Enable WhatsApp Alerts
                </label>
            </div>
        </div>
        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
                <button type="button" class="btn btn-outline-secondary" id="cancelEditBtn">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
            </div>
        </div>
    </form>
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
                                    <p class="text-muted small mt-2">Attendance will be recorded when student punches IN</p>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.getElementById('editStudentBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    const viewDiv = document.getElementById('studentInfoView');
    const formDiv = document.getElementById('studentInfoForm');
    
    editBtn.addEventListener('click', function() {
        viewDiv.style.display = 'none';
        formDiv.style.display = 'block';
        editBtn.style.display = 'none';
    });
    
    cancelBtn.addEventListener('click', function() {
        viewDiv.style.display = 'block';
        formDiv.style.display = 'none';
        editBtn.style.display = 'block';
        // Reset form to original values
        formDiv.reset();
    });
});
</script>
@endpush

@endsection
