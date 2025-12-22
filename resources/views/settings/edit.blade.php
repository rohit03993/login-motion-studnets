@extends('layouts.app', ['title' => 'Settings'])

@section('content')
<div class="brand-card mb-3">
    <div class="section-title">Aisensy Templates</div>
    <div class="muted">API key stays in .env for security. Set URL and templates here. Automatic templates for machine punches, Manual templates for staff-marked attendance.</div>
    @if(session('status'))
        <div class="alert alert-success mt-2">{{ session('status') }}</div>
    @endif
    <form class="row gy-3 gx-3 mt-2" method="post" action="{{ url('/settings') }}" enctype="multipart/form-data">
        @csrf
        <div class="col-12">
            <label class="form-label">Aisensy URL</label>
            <input type="text" name="aisensy_url" value="{{ old('aisensy_url', $aisensy_url) }}" class="form-control" required>
            @error('aisensy_url') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
            <h6 class="mt-3 mb-2"><i class="bi bi-clock"></i> Automatic Templates (Machine Punches)</h6>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Template (Automatic IN)</label>
            <input type="text" name="aisensy_template_in" value="{{ old('aisensy_template_in', $aisensy_template_in) }}" class="form-control" required>
            @error('aisensy_template_in') <div class="text-danger small">{{ $message }}</div> @enderror
            <div class="form-text small">Used for automatic machine punches (IN)</div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Template (Automatic OUT)</label>
            <input type="text" name="aisensy_template_out" value="{{ old('aisensy_template_out', $aisensy_template_out) }}" class="form-control" required>
            @error('aisensy_template_out') <div class="text-danger small">{{ $message }}</div> @enderror
            <div class="form-text small">Used for automatic machine punches (OUT)</div>
        </div>
        <div class="col-12">
            <h6 class="mt-3 mb-2"><i class="bi bi-pencil"></i> Manual Templates (Staff-Marked Attendance)</h6>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Template (Manual IN)</label>
            <input type="text" name="aisensy_template_manual_in" value="{{ old('aisensy_template_manual_in', $aisensy_template_manual_in ?? '') }}" class="form-control" required>
            @error('aisensy_template_manual_in') <div class="text-danger small">{{ $message }}</div> @enderror
            <div class="form-text small">Used when staff manually marks attendance (IN)</div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Template (Manual OUT)</label>
            <input type="text" name="aisensy_template_manual_out" value="{{ old('aisensy_template_manual_out', $aisensy_template_manual_out ?? '') }}" class="form-control" required>
            @error('aisensy_template_manual_out') <div class="text-danger small">{{ $message }}</div> @enderror
            <div class="form-text small">Used when staff manually marks attendance (OUT)</div>
        </div>
        <div class="col-12">
            <h6 class="mt-3 mb-2"><i class="bi bi-clock-history"></i> Auto-Out Settings</h6>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="auto_out_enabled" id="autoOutEnabled" value="1" {{ old('auto_out_enabled', $auto_out_enabled ?? '1') == '1' ? 'checked' : '' }}>
                <label class="form-check-label" for="autoOutEnabled">
                    Enable automatic OUT time for incomplete pairs
                </label>
            </div>
            <div class="form-text small">If enabled, students with IN but no OUT will automatically get an OUT time added. If disabled, incomplete pairs will remain as IN only.</div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Auto-Out Time</label>
            <input type="time" name="auto_out_time" value="{{ old('auto_out_time', $auto_out_time ?? '19:00') }}" class="form-control">
            @error('auto_out_time') <div class="text-danger small">{{ $message }}</div> @enderror
            <div class="form-text small">Time to automatically assign as OUT for incomplete pairs (past dates or current date after this time)</div>
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

@if(auth()->user()->isSuperAdmin())
<div class="brand-card mb-3">
    <div class="section-title mb-2">Company Logo</div>
    <div class="muted mb-3">Upload company logo to replace "Attendance CRM" text in the navigation bar.</div>
    <form class="row gy-3 gx-3" method="post" action="{{ url('/settings') }}" enctype="multipart/form-data">
        @csrf
        <div class="col-12 col-md-6">
            <label class="form-label">Upload Logo</label>
            <input type="file" name="company_logo" class="form-control" accept="image/*">
            <div class="form-text small">Max size: 2MB. Supported formats: JPEG, PNG, JPG, GIF, SVG</div>
            @error('company_logo') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-12 col-md-6">
            @if($company_logo)
                <label class="form-label">Current Logo</label>
                <div class="mb-2">
                    <img src="{{ asset('storage/' . $company_logo) }}" alt="Company Logo" style="max-height: 80px; max-width: 200px; object-fit: contain; border: 1px solid var(--brand-border); padding: 8px; border-radius: 8px;">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remove_logo" id="removeLogo" value="1">
                    <label class="form-check-label" for="removeLogo">
                        Remove logo (revert to text)
                    </label>
                </div>
            @else
                <div class="text-muted small">No logo uploaded. Default "Attendance CRM" text will be shown.</div>
            @endif
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Save Logo</button>
        </div>
    </form>
</div>
@endif

@if(auth()->user()->isSuperAdmin())
<div class="brand-card">
    <div class="section-title mb-2">Data Admin (Super Admin)</div>
    <div class="muted mb-3">Reset/seed and clear logs without running CLI.</div>
    <div class="d-flex flex-column flex-md-row gap-2">
        <form method="POST" action="{{ route('data-admin.reset-students') }}" onsubmit="return confirm('This will truncate students and manual attendance. Continue?');">
            @csrf
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash"></i> Reset Students & Manual Attendance
            </button>
        </form>
        <form method="POST" action="{{ route('data-admin.seed-defaults') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-seedling"></i> Seed Default Program/Batch
            </button>
        </form>
        <form method="POST" action="{{ route('data-admin.clear-punch-logs') }}" onsubmit="return confirm('This will truncate punch_logs. Continue?');">
            @csrf
            <button type="submit" class="btn btn-outline-warning">
                <i class="bi bi-x-circle"></i> Clear Punch Logs
            </button>
        </form>
        <form method="POST" action="{{ route('data-admin.clear-whatsapp-logs') }}" onsubmit="return confirm('This will truncate whatsapp_logs. Continue?');">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">
                <i class="bi bi-whatsapp"></i> Clear WhatsApp Logs
            </button>
        </form>
    </div>
</div>
@endif
@endsection

