@extends('layouts.app', ['title' => 'Manual Attendance'])

@section('content')
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-pencil-square"></i> Manual Attendance Marking
            </div>
            <div class="muted">Mark students as present or absent for a specific batch and date</div>
        </div>
    </div>
</div>

<div class="brand-card mb-3">
    <form method="GET" action="{{ route('manual-attendance.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label"><i class="bi bi-calendar"></i> Date</label>
            <input type="date" name="date" value="{{ $date }}" class="form-control" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label"><i class="bi bi-funnel"></i> Batch</label>
            <select name="batch" class="form-select" required>
                <option value="">Select Batch</option>
                @if($hasNoBatch)
                    <option value="__no_batch__" {{ $batch === '__no_batch__' ? 'selected' : '' }}>
                        (No Batch / Unassigned)
                    </option>
                @endif
                @foreach($batches as $batchOption)
                    <option value="{{ $batchOption }}" {{ $batch === $batchOption ? 'selected' : '' }}>
                        {{ $batchOption }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search"></i> Load
            </button>
        </div>
    </form>
</div>

@if($batch && $date)
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
                                        <td class="fw-medium">{{ $item['student']->roll_number }}</td>
                                        <td>{{ $item['student']->name ?? 'N/A' }}</td>
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
                                            {{ $item['out_time'] ?? '-' }}
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
                                                        data-date="{{ $date }}">
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
                                        <td class="fw-medium">{{ $item['student']->roll_number }}</td>
                                        <td>{{ $item['student']->name ?? 'N/A' }}</td>
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
            <p class="mt-3 mb-0">Please select a batch and date to view attendance</p>
        </div>
    </div>
@endif

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
    
    // Mark Present
    console.log('Setting up mark present buttons...');
    const markPresentButtons = document.querySelectorAll('.mark-present-btn');
    console.log('Found', markPresentButtons.length, 'mark present buttons');
    
    markPresentButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Mark present button clicked!');
            
            const rollNumber = this.dataset.roll;
            const date = this.dataset.date;
            const originalText = this.innerHTML;
            const btnElement = this;
            
            console.log('Roll:', rollNumber, 'Date:', date);
            
            if (!rollNumber || !date) {
                console.error('Missing roll or date');
                showToast('Error', 'Missing required information.', 'error');
                return;
            }
            
            btnElement.disabled = true;
            btnElement.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Marking...';
            
            const url = '{{ route("manual-attendance.mark-present") }}';
            console.log('Fetching URL:', url);
            
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
                    date: date
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    return response.json().then(err => {
                        console.error('Error response:', err);
                        throw new Error(err.message || 'Server error: ' + response.status);
                    }).catch(e => {
                        console.error('Failed to parse error response:', e);
                        throw new Error('Server error: ' + response.status);
                    });
                }
                return response.json().then(data => {
                    console.log('Success response:', data);
                    return data;
                });
            })
            .then(data => {
                console.log('Processing data:', data);
                if (data.success) {
                    const whatsappMsg = data.whatsapp_sent ? ' WhatsApp sent.' : (data.whatsapp_sent === false ? ' WhatsApp not sent (check phone number).' : '');
                    showToast('Success', data.message + whatsappMsg);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('Error', data.message || 'Failed to mark student as present.', 'error');
                    btnElement.disabled = false;
                    btnElement.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error marking present:', error);
                console.error('Error stack:', error.stack);
                showToast('Error', error.message || 'Failed to mark student as present. Please try again.', 'error');
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;
            });
        });
    });
    
    // Mark Out
    document.querySelectorAll('.mark-out-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const rollNumber = this.dataset.roll;
            const date = this.dataset.date;
            const originalText = this.innerHTML;
            
            if (!confirm('Mark this student as OUT?')) {
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Marking...';
            
            fetch('{{ route("manual-attendance.mark-out") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    roll_number: rollNumber,
                    date: date
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('Error', data.message, 'error');
                    this.disabled = false;
                    this.innerHTML = originalText;
                }
            })
            .catch(error => {
                showToast('Error', 'Failed to mark student as OUT. Please try again.', 'error');
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
    });
});
</script>
@endpush

@endsection

