@extends('layouts.app', ['title' => 'Live Attendance'])

@section('content')
<div class="brand-card mb-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
        <div>
            <div class="section-title mb-1">
                <i class="bi bi-clock-history"></i> Live Attendance Dashboard
            </div>
            <div class="muted">Real-time punches from EasyTimePro • Auto-refreshing data</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-success" href="{{ url('/attendance/export') }}?{{ http_build_query(request()->query()) }}">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>
</div>

<div class="brand-card mb-3">
    <div class="section-title mb-3"><i class="bi bi-funnel"></i> Filters</div>
    <form class="row gy-2 gx-2 align-items-end" method="get" action="{{ url('/attendance') }}">
        <div class="col-12 col-md-3">
            <label class="form-label"><i class="bi bi-person-badge"></i> Roll / Employee ID</label>
            <input type="text" name="roll" value="{{ $filters['roll'] ?? '' }}" class="form-control" placeholder="e.g. 25175000xxx">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label"><i class="bi bi-person"></i> Name (partial)</label>
            <input type="text" name="name" value="{{ $filters['name'] ?? '' }}" class="form-control" placeholder="Student name">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label"><i class="bi bi-calendar-event"></i> From Date</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? date('Y-m-d') }}" class="form-control">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label"><i class="bi bi-calendar-event"></i> To Date</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? date('Y-m-d') }}" class="form-control">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
        </div>
        <div class="col-auto">
            <a class="btn btn-outline-secondary" href="{{ url('/attendance') }}"><i class="bi bi-arrow-clockwise"></i> Reset</a>
        </div>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label">Total Punches</div>
            <div class="stat-value">{{ number_format($todayStats['total'] ?? 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label">Today</div>
            <div class="stat-value">{{ $todayStats['total'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label"><i class="bi bi-box-arrow-in-right text-success"></i> IN</div>
            <div class="stat-value text-success">{{ $todayStats['in'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label"><i class="bi bi-box-arrow-right text-danger"></i> OUT</div>
            <div class="stat-value text-danger">{{ $todayStats['out'] ?? 0 }}</div>
        </div>
    </div>
</div>

<div class="brand-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title mb-0"><i class="bi bi-list-ul"></i> Punch Records</div>
        <div class="text-muted small">{{ $rows->total() }} total records</div>
    </div>
    
    @if($groupedRows && $groupedRows->count() > 0)
        <div class="accordion" id="punchAccordion">
            @foreach ($groupedRows as $rollNumber => $studentPunches)
                @php
                    $firstPunch = $studentPunches->first();
                    $punchCount = $studentPunches->count();
                    $accordionId = 'student-' . $rollNumber;
                    $hasMultiple = $punchCount > 1;
                @endphp
                
                @if($hasMultiple)
                    <!-- Multiple entries - show in accordion with IN/OUT pairs -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading{{ $accordionId }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $accordionId }}" aria-expanded="false" aria-controls="collapse{{ $accordionId }}">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div>
                                        <a href="{{ route('students.show', $rollNumber) }}" class="text-decoration-none text-dark fw-bold" onclick="event.stopPropagation();">
                                            {{ $firstPunch->student_name ?? '—' }}
                                        </a>
                                        <span class="text-muted ms-2">(Roll: {{ $rollNumber }})</span>
                                        @if($firstPunch->class_course)
                                            <span class="text-muted ms-2">• {{ $firstPunch->class_course }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="badge bg-primary">{{ $punchCount }} {{ $punchCount === 1 ? 'punch' : 'punches' }}</span>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse{{ $accordionId }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $accordionId }}" data-bs-parent="#punchAccordion">
                            <div class="accordion-body">
                                @php
                                    $dailyPairs = $studentPairs[$rollNumber] ?? [];
                                    // Sort by date descending (latest first)
                                    usort($dailyPairs, function($a, $b) {
                                        return strcmp($b['date'], $a['date']);
                                    });
                                @endphp
                                @if(count($dailyPairs) > 0)
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
                                                                        </div>
                                                                        @if(isset($pair['whatsapp_in']))
                                                                            @php $waIn = $pair['whatsapp_in']; @endphp
                                                                            @if($waIn->status === 'success')
                                                                                <small class="d-block mt-1">
                                                                                    <span class="badge bg-success" style="font-size: 0.75rem;">
                                                                                        <i class="bi bi-whatsapp"></i> Sent
                                                                                    </span>
                                                                                </small>
                                                                            @elseif($waIn->status === 'failed')
                                                                                <small class="d-block mt-1">
                                                                                    <span class="badge bg-danger" style="font-size: 0.75rem;" title="{{ $waIn->error ?? 'Failed' }}">
                                                                                        <i class="bi bi-whatsapp"></i> Failed
                                                                                    </span>
                                                                                </small>
                                                                            @else
                                                                                <small class="d-block mt-1">
                                                                                    <span class="badge bg-secondary" style="font-size: 0.75rem;">
                                                                                        <i class="bi bi-whatsapp"></i> Pending
                                                                                    </span>
                                                                                </small>
                                                                            @endif
                                                                        @else
                                                                            <small class="d-block mt-1">
                                                                                <span class="badge bg-secondary" style="font-size: 0.75rem;">
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
                                                                            <span class="badge bg-danger" style="font-size: 0.9rem;"><i class="bi bi-box-arrow-right"></i> {{ $pair['out'] }}</span>
                                                                        </div>
                                                                        @if(isset($pair['whatsapp_out']))
                                                                            @php $waOut = $pair['whatsapp_out']; @endphp
                                                                            @if($waOut->status === 'success')
                                                                                <small class="d-block mt-1">
                                                                                    <span class="badge bg-success" style="font-size: 0.75rem;">
                                                                                        <i class="bi bi-whatsapp"></i> Sent
                                                                                    </span>
                                                                                </small>
                                                                            @elseif($waOut->status === 'failed')
                                                                                <small class="d-block mt-1">
                                                                                    <span class="badge bg-danger" style="font-size: 0.75rem;" title="{{ $waOut->error ?? 'Failed' }}">
                                                                                        <i class="bi bi-whatsapp"></i> Failed
                                                                                    </span>
                                                                                </small>
                                                                            @else
                                                                                <small class="d-block mt-1">
                                                                                    <span class="badge bg-secondary" style="font-size: 0.75rem;">
                                                                                        <i class="bi bi-whatsapp"></i> Pending
                                                                                    </span>
                                                                                </small>
                                                                            @endif
                                                                        @else
                                                                            <small class="d-block mt-1">
                                                                                <span class="badge bg-secondary" style="font-size: 0.75rem;">
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
                                    <div class="mt-2">
                                        <a href="{{ route('students.show', $rollNumber) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-person"></i> View Full Profile
                                        </a>
                                    </div>
                                @else
                                    <div class="text-center py-3">
                                        <span class="text-muted">No attendance data available</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Single entry - show directly without accordion -->
                    @php $r = $firstPunch; @endphp
                    <div class="punch-row-single mb-2 p-3 border rounded">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-2">
                                <a href="{{ route('students.show', $r->employee_id) }}" class="text-decoration-none fw-bold">
                                    {{ $r->employee_id }}
                                </a>
                            </div>
                            <div class="col-12 col-md-3">
                                <a href="{{ route('students.show', $r->employee_id) }}" class="text-decoration-none">
                                    <div class="fw-medium">{{ $r->student_name ?? '—' }}</div>
                                </a>
                                @if($r->class_course)
                                    <small class="text-muted">{{ $r->class_course }}</small>
                                @endif
                            </div>
                            <div class="col-12 col-md-2">
                                <div>{{ \Carbon\Carbon::parse($r->punch_date)->format('M d, Y') }}</div>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($r->punch_date)->format('D') }}</small>
                            </div>
                            <div class="col-12 col-md-2">
                                <div class="fw-medium">{{ $r->punch_time }}</div>
                                @php
                                    $state = $r->computed_state ?? 'IN';
                                    $isIn = $state === 'IN';
                                @endphp
                                <span class="badge {{ $isIn ? 'bg-success' : 'bg-danger' }} text-white mt-1">
                                    <i class="bi bi-{{ $isIn ? 'box-arrow-in-right' : 'box-arrow-right' }}"></i> {{ $state }}
                                </span>
                            </div>
                            <div class="col-12 col-md-2">
                                @if($r->whatsapp_status_display === 'success')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Sent
                                    </span>
                                @elseif($r->whatsapp_status_display === 'failed')
                                    <span class="badge bg-danger" title="{{ $r->whatsapp_error ?? 'Failed' }}">
                                        <i class="bi bi-x-circle"></i> Failed
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-hourglass-split"></i> Pending
                                    </span>
                                @endif
                            </div>
                            <div class="col-12 col-md-1 text-end">
                                <a href="{{ route('students.show', $r->employee_id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="text-center py-4">
            <i class="bi bi-inbox" style="font-size: 2rem; color: var(--brand-muted);"></i>
            <div class="mt-2 text-muted">No punches found.</div>
        </div>
    @endif

    <div class="mt-3">
        {{ $rows->links('pagination::bootstrap-5') }}
    </div>
</div>

<script>
(function() {
    // Get the latest punch timestamp from current page data
    let lastCheckTimestamp = null;
    let isRefreshing = false;
    let autoRefreshInterval = null;
    
    // Initialize: Get the latest timestamp from the current page data
    function initializeTimestamp() {
        @if($rows->isNotEmpty())
            // Get the latest punch from the first row (since they're sorted desc)
            @php
                $firstRow = $rows->first();
                $latestDateTime = $firstRow->punch_date . ' ' . $firstRow->punch_time;
                try {
                    $latestTimestamp = \Carbon\Carbon::parse($latestDateTime)->timestamp;
                } catch (\Exception $e) {
                    $latestTimestamp = time();
                }
            @endphp
            lastCheckTimestamp = {{ $latestTimestamp }};
        @else
            // No data yet, use current time minus 1 minute to catch any recent entries
            lastCheckTimestamp = Math.floor(Date.now() / 1000) - 60;
        @endif
        
        console.log('Initial timestamp:', lastCheckTimestamp, 'Date:', new Date(lastCheckTimestamp * 1000));
    }
    
    // Auto-refresh every 5 seconds
    function startAutoRefresh() {
        if (autoRefreshInterval) return; // Already started
        
        autoRefreshInterval = setInterval(function() {
            if (isRefreshing) return; // Skip if already checking
            
            checkForUpdates();
        }, 3000); // Check every 3 seconds for faster detection
    }
    
    function checkForUpdates() {
        if (isRefreshing) return;
        
        isRefreshing = true;
        const dateFrom = document.querySelector('input[name="date_from"]')?.value || '{{ $filters['date_from'] ?? date('Y-m-d') }}';
        const dateTo = document.querySelector('input[name="date_to"]')?.value || '{{ $filters['date_to'] ?? date('Y-m-d') }}';
        
        fetch(`{{ url('/attendance/check-updates') }}?last_check=${lastCheckTimestamp}&date_from=${dateFrom}&date_to=${dateTo}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Update check:', {
                has_updates: data.has_updates,
                new_count: data.new_punches_count,
                latest_timestamp: data.latest_timestamp,
                current_total: data.current_total,
                last_check: lastCheckTimestamp,
                timestamp_diff: data.latest_timestamp - lastCheckTimestamp
            }); // Debug log
            
            // Always update timestamp to latest for next check
            if (data.latest_timestamp) {
                // Check if timestamp has changed (newer punch exists)
                if (data.latest_timestamp > lastCheckTimestamp) {
                    // New punch detected!
                    showUpdateNotification(data.new_punches_count || 1);
                    
                    // Reload page immediately to show new data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    return; // Don't update timestamp, we're reloading
                }
                
                // No new punches, update timestamp for next check
                lastCheckTimestamp = data.latest_timestamp;
            }
        })
        .catch(error => {
            console.error('Auto-refresh error:', error);
        })
        .finally(() => {
            isRefreshing = false;
        });
    }
    
    function showUpdateNotification(count) {
        // Remove existing notification if any
        const existing = document.getElementById('auto-refresh-notification');
        if (existing) {
            existing.remove();
        }
        
        // Create new notification
        const notification = document.createElement('div');
        notification.id = 'auto-refresh-notification';
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; display: flex; align-items: center; gap: 10px; font-weight: 500;';
        notification.innerHTML = `<i class="bi bi-check-circle"></i> <span>${count} new punch(es) found. Refreshing...</span>`;
        document.body.appendChild(notification);
    }
    
    // Start auto-refresh when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeTimestamp();
        startAutoRefresh();
        
        // Show indicator that auto-refresh is active
        const indicator = document.createElement('div');
        indicator.id = 'refresh-indicator';
        indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: rgba(0,0,0,0.7); color: white; padding: 8px 12px; border-radius: 6px; font-size: 12px; z-index: 9998; display: flex; align-items: center; gap: 6px;';
        indicator.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation: spin 2s linear infinite;"></i> Auto-refresh active';
        document.body.appendChild(indicator);
        
        // Add spin animation
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    });
    
    // Stop auto-refresh when user navigates away
    window.addEventListener('beforeunload', function() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
    });
})();
</script>
@endsection
