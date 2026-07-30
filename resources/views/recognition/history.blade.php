@extends('layouts.app')
@section('content')
<section class="page-head app-page-head history-head">
    <div><p class="eyebrow">Recognition archive</p><h1>History</h1></div>
    <div class="history-head-actions">
        <a class="button" href="{{ route('recognition.create') }}"><i data-lucide="camera"></i>Scan</a>
        <a class="button muted" href="{{ route('recognition.export.csv', request()->query()) }}"><i data-lucide="download"></i>CSV</a>
        <a class="button muted" href="{{ route('recognition.export.pdf', request()->query()) }}"><i data-lucide="file-text"></i>PDF</a>
        <form method="post" action="{{ route('recognition.clear') }}" onsubmit="return confirm('Clear all recognition history? This cannot be undone.')">
            @csrf
            @method('delete')
            <button class="button danger" type="submit"><i data-lucide="trash-2"></i>Clear All</button>
        </form>
    </div>
</section>
<form class="filters history-filters">
    <label class="input-icon"><i data-lucide="search"></i><input name="q" placeholder="Search scans" value="{{ request('q') }}"></label>
    <select name="species"><option value="">All species</option>@foreach($species as $item)<option value="{{ $item->id }}" @selected(request('species')==$item->id)>{{ $item->common_name }}</option>@endforeach</select>
    <select name="confidence"><option value="">Any confidence</option><option value="high" @selected(request('confidence') === 'high')>High</option><option value="moderate" @selected(request('confidence') === 'moderate')>Moderate</option><option value="low" @selected(request('confidence') === 'low')>Low</option></select>
    <button class="button small"><i data-lucide="filter"></i>Filter</button>
</form>
<div class="list app-list history-list">
    @forelse($records as $record)
        @php($historyLocation = $record->location_label ?: (($record->latitude !== null && $record->longitude !== null) ? number_format($record->latitude, 5).', '.number_format($record->longitude, 5) : 'Missing GPS metadata'))
        <article class="row history-row">
            <a class="history-row-link" href="{{ route('recognition.show', $record) }}">
                <span>{{ $record->created_at->format('M d, Y H:i') }}</span>
                <strong>{{ $record->species?->common_name ?? $record->predicted_class ?? $record->recognition_status }}</strong>
                <small>{{ $record->confidence ? number_format($record->confidence * 100, 1).'%' : 'N/A' }} | {{ $historyLocation }} @if($record->feedback_count) | {{ $record->feedback_count }} feedback @endif</small>
            </a>
            <div class="history-row-actions">
                @if($record->latitude !== null && $record->longitude !== null)
                    <a class="icon-button map" href="{{ route('recognition.map', ['scan' => $record->scan_reference]) }}" aria-label="View scan on recognition map"><i data-lucide="map-pin"></i></a>
                @endif
                <form method="post" action="{{ route('recognition.destroy', $record) }}" onsubmit="return confirm('Delete this recognition record?')">
                    @csrf
                    @method('delete')
                    <button class="icon-button danger" type="submit" aria-label="Delete recognition record"><i data-lucide="trash-2"></i></button>
                </form>
            </div>
        </article>
    @empty
        <div class="empty history-empty">No matching records.</div>
    @endforelse
</div>
{{ $records->links('components.pagination') }}
@endsection
