@extends('layouts.app', ['title' => 'Settings'])

@section('content')
<div class="brand-card mb-3">
    <div class="section-title">Aisensy Templates</div>
    <div class="muted">API key stays in .env for security. Set URL and templates here. Automatic templates for machine punches, Manual templates for staff-marked attendance.</div>
    @if(session('status'))
        <div class="alert alert-success mt-2">{{ session('status') }}</div>
    @endif
    <form class="row gy-3 gx-3 mt-2" method="post" action="{{ url('/settings') }}">
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
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

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

