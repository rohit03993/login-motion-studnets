@extends('layouts.app', ['title' => 'Settings'])

@section('content')
<div class="brand-card mb-3">
    <div class="section-title">Aisensy Templates</div>
    <div class="muted">API key stays in .env for security. Set URL and templates (IN/OUT) here.</div>
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
        <div class="col-12 col-md-6">
            <label class="form-label">Template (IN)</label>
            <input type="text" name="aisensy_template_in" value="{{ old('aisensy_template_in', $aisensy_template_in) }}" class="form-control" required>
            @error('aisensy_template_in') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Template (OUT)</label>
            <input type="text" name="aisensy_template_out" value="{{ old('aisensy_template_out', $aisensy_template_out) }}" class="form-control" required>
            @error('aisensy_template_out') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection

