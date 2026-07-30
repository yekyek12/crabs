@extends('layouts.app')
@section('content')
@php
    $focusedHasGps = $focusedRecord && $focusedRecord->latitude !== null && $focusedRecord->longitude !== null;
    $mapCountLabel = $focusedRecord ? ($focusedHasGps ? 'Focused scan' : 'Focused range') : $mapStats['filtered_points'].' plotted';
    $latestLocatedAt = $mapStats['latest_located_at'] ? $mapStats['latest_located_at']->format('M d, Y') : 'N/A';
@endphp
<section class="page-head feature-head map-head">
    <div>
        <p class="eyebrow">Scan geography</p>
        <h1>Recognition Map</h1>
    </div>
    <span class="badge map-count-badge"><i data-lucide="map-pin"></i>{{ $mapCountLabel }}</span>
</section>

<form class="filters feature-filters map-filters">
    @if(request('scan'))
        <input name="scan" type="hidden" value="{{ request('scan') }}">
    @endif
    <select name="species">
        <option value="">All species</option>
        @foreach($species as $item)
            <option value="{{ $item->id }}" @selected(request('species') == $item->id)>{{ $item->common_name }}</option>
        @endforeach
    </select>
    <select name="confidence">
        <option value="">Any confidence</option>
        <option value="high" @selected(request('confidence') === 'high')>High</option>
        <option value="moderate" @selected(request('confidence') === 'moderate')>Moderate</option>
        <option value="low" @selected(request('confidence') === 'low')>Low</option>
    </select>
    <input name="date_from" type="date" value="{{ request('date_from') }}">
    <input name="date_to" type="date" value="{{ request('date_to') }}">
    <button class="button small"><i data-lucide="filter"></i>Filter</button>
    @if(request('scan'))
        <a class="button muted small" href="{{ route('recognition.map', request()->except('scan')) }}"><i data-lucide="x"></i>Clear Focus</a>
    @endif
</form>

<section class="stats feature-stats map-db-stats">
    <div><i data-lucide="database"></i><strong>{{ $mapStats['total_scans'] }}</strong><span>Database scans</span></div>
    <div><i data-lucide="map-pin"></i><strong>{{ $mapStats['located_scans'] }}</strong><span>Located scans</span></div>
    <div><i data-lucide="filter"></i><strong>{{ $mapStats['filtered_points'] }}</strong><span>Filtered points</span></div>
    <div><i data-lucide="shield-check"></i><strong>{{ $mapStats['six_provider_reliable_points'] }}</strong><span>AI verified points</span></div>
    <div><i data-lucide="globe-2"></i><strong>{{ $mapStats['global_range_layers'] }}</strong><span>Global ranges</span></div>
    <div><i data-lucide="gauge"></i><strong>{{ $mapStats['minimum_provider_agreement'] }}/{{ $mapStats['required_provider_count'] }}</strong><span>AI agreement rule</span></div>
    <div class="@if($mapStats['missing_location_scans'] > 0) warning @endif"><i data-lucide="alert-triangle"></i><strong>{{ $mapStats['missing_location_scans'] }}</strong><span>Missing GPS</span></div>
    <div><i data-lucide="clock-3"></i><strong>{{ $latestLocatedAt }}</strong><span>Latest location</span></div>
</section>

<section class="map-board recognition-map-board" data-map-points='@json($points)' data-range-layers='@json($rangeLayers)'>
    <div class="map-board-head">
        <span><i data-lucide="globe-2"></i>GPS pins / global ranges</span>
        <strong>{{ $mapStats['filtered_points'] }} / {{ $mapStats['global_range_layers'] }}</strong>
    </div>
    <div class="map-layer-legend">
        <span><b class="scan-pin-key"></b>Exact scan GPS</span>
        <span><b class="range-key"></b>Possible global range</span>
    </div>
    <div class="map-grid" id="recognitionMapGrid" aria-label="Recognition locations map">
        <div class="map-empty-state">No GPS points or known species ranges match this filter.</div>
    </div>
</section>

<section class="notice map-range-note">
    <i data-lucide="globe-2"></i>
    <span>Scan pins show exact saved GPS. Shaded regions show possible global range for the identified species, not proof that the scanned crab came from every place in that range.</span>
</section>

<div class="mobile-record-list map-record-list">
    @forelse($records as $record)
        <a class="feature-row map-record-row @if($focusedRecord?->is($record)) is-focused @endif" href="{{ route('recognition.show', $record) }}">
            <span><i data-lucide="map-pin"></i>{{ $record->location_label ?: number_format($record->latitude, 4).', '.number_format($record->longitude, 4) }}</span>
            <strong>{{ $record->species?->common_name ?? $record->predicted_class ?? 'Unknown crab' }}</strong>
            <small>{{ $record->confidence ? number_format($record->confidence * 100, 1).'%' : 'N/A' }} &middot; {{ $record->created_at->format('M d, Y') }}{{ $record->location_accuracy_meters ? ' &middot; +/- '.number_format($record->location_accuracy_meters, 0).' m' : '' }}</small>
        </a>
    @empty
        <div class="empty map-empty-list">No exact GPS scan points match this filter. Species ranges can still appear on the map when range data is available.</div>
    @endforelse
</div>
@endsection
