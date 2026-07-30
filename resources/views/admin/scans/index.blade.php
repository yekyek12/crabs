@extends('layouts.app')
@section('content')
<section class="page-head">
    <div><p class="eyebrow">Admin scan data</p><h1>Scan Data</h1></div>
    <div class="admin-head-actions">
        <a class="button muted" href="{{ route('admin.dashboard') }}"><i data-lucide="bar-chart-3"></i>Dashboard</a>
        <a class="button" href="{{ route('admin.scans.export.csv', request()->query()) }}"><i data-lucide="download"></i>CSV</a>
    </div>
</section>

<form class="filters admin-scan-filters">
    <label class="input-icon"><i data-lucide="search"></i><input name="q" value="{{ request('q') }}" placeholder="Search scans, users, models"></label>
    <select name="user_id">
        <option value="">Any user</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }} - {{ $user->email }}</option>
        @endforeach
    </select>
    <select name="species">
        <option value="">Any species</option>
        @foreach($species as $item)
            <option value="{{ $item->id }}" @selected((string) request('species') === (string) $item->id)>{{ $item->common_name }}</option>
        @endforeach
    </select>
    <select name="status">
        <option value="">Any status</option>
        @foreach($statuses as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
        @endforeach
    </select>
    <select name="confidence">
        <option value="">Any confidence</option>
        @foreach($confidenceLevels as $level)
            <option value="{{ $level }}" @selected(request('confidence') === $level)>{{ ucfirst($level) }}</option>
        @endforeach
    </select>
    <select name="training">
        <option value="">Any training flag</option>
        <option value="yes" @selected(request('training') === 'yes')>Training candidate</option>
        <option value="no" @selected(request('training') === 'no')>Not marked</option>
    </select>
    <input name="date_from" type="date" value="{{ request('date_from') }}" aria-label="Date from">
    <input name="date_to" type="date" value="{{ request('date_to') }}" aria-label="Date to">
    <button class="button small"><i data-lucide="filter"></i>Filter</button>
</form>

<div class="admin-table admin-scans-table">
    <div class="admin-table-head"><span>Scan</span><span>User</span><span>AI Result</span><span>Review</span><span></span></div>
    @forelse($records as $record)
        <article class="admin-table-row">
            <div>
                <strong>{{ $record->scan_reference }}</strong>
                <span>{{ $record->created_at->format('M d, Y H:i') }}</span>
                <span>{{ $record->location_label ?: 'No location' }}</span>
            </div>
            <div>
                <strong>{{ $record->user?->name ?? 'Deleted user' }}</strong>
                <span>{{ $record->user?->email ?? 'No email' }}</span>
            </div>
            <div>
                <strong>{{ $record->species?->common_name ?? $record->predicted_class ?? 'Unknown' }}</strong>
                <span>{{ $record->confidence === null ? 'Confidence N/A' : number_format($record->confidence * 100, 1).'%' }}</span>
                <span>{{ trim(($record->model_name ?? '').' '.($record->model_version ?? '')) ?: 'No model data' }}</span>
            </div>
            <div class="admin-status-list">
                <span class="badge">{{ str_replace('_', ' ', $record->recognition_status) }}</span>
                <span class="badge muted-badge">{{ $record->confidence_level }}</span>
                @if($record->needs_retraining)
                    <span class="badge">training</span>
                @endif
                @if($record->feedback_count)
                    <span class="badge muted-badge">{{ $record->feedback_count }} feedback</span>
                @endif
            </div>
            <div class="admin-row-actions">
                <a class="icon-button" href="{{ route('recognition.show', $record) }}" aria-label="Open {{ $record->scan_reference }}"><i data-lucide="external-link"></i></a>
                <a class="icon-button" href="{{ route('admin.scans.edit', $record) }}" aria-label="Edit {{ $record->scan_reference }}"><i data-lucide="pencil"></i></a>
                <form method="post" action="{{ route('admin.scans.destroy', $record) }}" onsubmit="return confirm('Delete this scan record and its stored images?')">
                    @csrf
                    @method('delete')
                    <button class="icon-button danger" type="submit" aria-label="Delete {{ $record->scan_reference }}"><i data-lucide="trash-2"></i></button>
                </form>
            </div>
        </article>
    @empty
        <div class="empty">No scan records found.</div>
    @endforelse
</div>

{{ $records->links('components.pagination') }}
@endsection
