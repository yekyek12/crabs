@extends('layouts.app')
@section('content')
<section class="page-head">
    <div><p class="eyebrow">{{ $scan->scan_reference }}</p><h1>Edit Scan Data</h1></div>
    <div class="admin-head-actions">
        <a class="button muted" href="{{ route('recognition.show', $scan) }}"><i data-lucide="external-link"></i>Open Result</a>
        <a class="button muted" href="{{ route('admin.scans.index') }}"><i data-lucide="arrow-right"></i>Back</a>
    </div>
</section>

<section class="dashboard-split admin-scan-edit">
    <form class="panel admin-form" method="post" action="{{ route('admin.scans.update', $scan) }}">
        @csrf
        @method('put')

        <div class="field-grid">
            <div class="field-group">
                <label for="recognition_status">Recognition status</label>
                <select id="recognition_status" name="recognition_status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(old('recognition_status', $scan->recognition_status) === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-group">
                <label for="confidence_level">Confidence level</label>
                <select id="confidence_level" name="confidence_level" required>
                    @foreach($confidenceLevels as $level)
                        <option value="{{ $level }}" @selected(old('confidence_level', $scan->confidence_level) === $level)>{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="field-group">
            <label for="expert_species_id">Expert species correction</label>
            <select id="expert_species_id" name="expert_species_id">
                <option value="">No correction</option>
                @foreach($species as $item)
                    <option value="{{ $item->id }}" @selected(old('expert_species_id', $scan->expert_species_id) == $item->id)>{{ $item->common_name }}</option>
                @endforeach
            </select>
        </div>

        <label class="admin-inline-check"><input type="checkbox" name="needs_retraining" value="1" @checked(old('needs_retraining', $scan->needs_retraining))> Mark as training candidate</label>

        <div class="field-group">
            <label for="location_label">Location label</label>
            <input id="location_label" name="location_label" value="{{ old('location_label', $scan->location_label) }}">
        </div>

        <div class="field-group">
            <label for="capture_notes">Capture notes</label>
            <textarea id="capture_notes" name="capture_notes">{{ old('capture_notes', $scan->capture_notes) }}</textarea>
        </div>

        <div class="field-group">
            <label for="admin_notes">Internal admin notes</label>
            <textarea id="admin_notes" name="admin_notes">{{ old('admin_notes', $scan->admin_notes) }}</textarea>
        </div>

        @foreach($errors->all() as $error)<p class="error">{{ $error }}</p>@endforeach

        <div class="actions">
            <button class="button" type="submit"><i data-lucide="save"></i>Save Scan</button>
            <a class="button muted" href="{{ route('admin.scans.index') }}"><i data-lucide="arrow-right"></i>Back</a>
        </div>
    </form>

    <article class="panel">
        <h2>Scan Context</h2>
        <dl class="mini-dl">
            <div><dt>User</dt><dd>{{ $scan->user?->name ?? 'Deleted user' }}<br>{{ $scan->user?->email }}</dd></div>
            <div><dt>AI result</dt><dd>{{ $scan->species?->common_name ?? $scan->predicted_class ?? 'Unknown' }}</dd></div>
            <div><dt>Confidence</dt><dd>{{ $scan->confidence === null ? 'N/A' : number_format($scan->confidence * 100, 1).'%' }}</dd></div>
            <div><dt>Model</dt><dd>{{ trim(($scan->model_name ?? '').' '.($scan->model_version ?? '')) ?: 'N/A' }}</dd></div>
            <div><dt>Created</dt><dd>{{ $scan->created_at->format('M d, Y H:i') }}</dd></div>
        </dl>

        <h2>Feedback</h2>
        <div class="compact-list">
            @forelse($scan->feedback as $feedback)
                <div class="compact-row">
                    <span>{{ str_replace('_', ' ', $feedback->category) }}<br>{{ $feedback->user?->name ?? 'Unknown user' }}</span>
                    <strong>{{ str_replace('_', ' ', $feedback->status) }}</strong>
                </div>
            @empty
                <div class="empty">No feedback for this scan.</div>
            @endforelse
        </div>
    </article>
</section>
@endsection
