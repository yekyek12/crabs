@extends('layouts.app')
@section('content')
@php
    $globalSpecies = (array) data_get($record->ai_response, 'global_species', []);
    $resultName = $record->species?->common_name ?? $globalSpecies['common_name'] ?? $record->predicted_class ?? 'Recognition Result';
    $scientificName = $record->species?->scientific_name ?? $globalSpecies['scientific_name'] ?? $record->predicted_class ?? 'Unknown';
    $box = $record->bounding_box;
    $boxStyle = null;
    if (is_array($box)) {
        $x1 = data_get($box, 'x1');
        $y1 = data_get($box, 'y1');
        $x2 = data_get($box, 'x2');
        $y2 = data_get($box, 'y2');
        $x = data_get($box, 'x', data_get($box, 'left'));
        $y = data_get($box, 'y', data_get($box, 'top'));
        $w = data_get($box, 'width', data_get($box, 'w'));
        $h = data_get($box, 'height', data_get($box, 'h'));
        if ($x === null && array_key_exists(0, $box)) $x = $box[0];
        if ($y === null && array_key_exists(1, $box)) $y = $box[1];
        if ($w === null && array_key_exists(2, $box)) $w = $box[2];
        if ($h === null && array_key_exists(3, $box)) $h = $box[3];
        if ($x1 !== null && $y1 !== null && $x2 !== null && $y2 !== null) {
            $x = $x1; $y = $y1; $w = $x2 - $x1; $h = $y2 - $y1;
        }
        if ($x !== null && $y !== null && $w !== null && $h !== null) {
            $values = [(float) $x, (float) $y, (float) $w, (float) $h];
            $max = max(array_map('abs', $values));
            if ($max <= 1) $values = array_map(fn ($value) => $value * 100, $values);
            if (max(array_map('abs', $values)) <= 100) {
                [$x, $y, $w, $h] = $values;
                $boxStyle = 'left:'.max(0, min(100, $x)).'%;top:'.max(0, min(100, $y)).'%;width:'.max(0, min(100 - $x, $w)).'%;height:'.max(0, min(100 - $y, $h)).'%;';
            }
        }
    }
    $hasCoordinates = $record->latitude !== null && $record->longitude !== null;
    $gpsCoordinates = $hasCoordinates ? number_format($record->latitude, 7, '.', '').','.number_format($record->longitude, 7, '.', '') : null;
    $gpsUrl = $gpsCoordinates ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($gpsCoordinates) : null;
    $maxDeviceAccuracy = max(1, (float) config('services.location.max_device_accuracy_meters', 100));
    $locationReliability = 'No exact GPS saved';
    if ($hasCoordinates && $record->location_accuracy_meters === null) {
        $locationReliability = 'Exact coordinates saved; accuracy radius unavailable';
    } elseif ($hasCoordinates && $record->location_accuracy_meters <= $maxDeviceAccuracy) {
        $locationReliability = 'Reliable GPS within +/- '.number_format($record->location_accuracy_meters, 0).' m';
    } elseif ($hasCoordinates) {
        $locationReliability = 'Low-accuracy legacy GPS (+/- '.number_format($record->location_accuracy_meters, 0).' m)';
    }
@endphp
<section class="page-head app-page-head result-head"><div><p class="eyebrow">{{ $record->scan_reference }}</p><h1>{{ $resultName }}</h1></div><span class="badge"><i data-lucide="gauge"></i>{{ $record->confidence_level }}</span></section>
<div class="result-media">
    <img class="result-image" src="{{ route('recognition.image', $record) }}" alt="Submitted crab image">
    @if($boxStyle)
        <span class="result-bounding-box" style="{{ $boxStyle }}"><span>Detection</span></span>
    @elseif($record->bounding_box)
        <span class="result-box-note">Bounding box recorded</span>
    @endif
</div>
<section class="stats result-stats">
    <div><i data-lucide="gauge"></i><strong>{{ $record->confidence ? number_format($record->confidence * 100, 1).'%' : 'N/A' }}</strong><span>Confidence</span></div>
    <div><i data-lucide="clock-3"></i><strong>{{ $record->processing_time_ms ?? 'N/A' }}</strong><span>ms</span></div>
    <div><i data-lucide="activity"></i><strong>{{ $record->model_version ?? 'N/A' }}</strong><span>Model</span></div>
</section>
<article class="panel guidance-panel">
    <h2>Result Guidance</h2>
    <div class="guidance-grid">
        @foreach($guidance as $item)
            <div class="guidance-item">
                <i data-lucide="info"></i>
                <span><strong>{{ $item['title'] }}</strong>{{ $item['detail'] }}</span>
            </div>
        @endforeach
    </div>
</article>
@php($consensus = data_get($record->ai_response, 'consensus'))
@if($consensus)
<article class="panel result-consensus">
    <h2>AI Reliability</h2>
    <div class="result-field"><span>Provider agreement</span><strong>{{ data_get($consensus, 'agreement_count', 0) }}/{{ data_get($consensus, 'usable_provider_count', 0) }} usable providers</strong></div>
    <div class="result-field"><span>Required rule</span><strong>At least {{ data_get($consensus, 'minimum_required', 2) }} matching provider result(s)</strong></div>
    <div class="result-field"><span>Provider status</span><strong>{{ count(data_get($consensus, 'provider_errors', [])) }} provider issue(s) during this scan</strong></div>
</article>
@endif
<article class="panel result-details">
    <div class="result-field"><span>Scientific name</span><strong>{{ $scientificName }}</strong></div>
    <div class="result-field"><span>Library match</span><strong>{{ $record->species ? 'Matched local supported species' : 'Global AI detection outside local library' }}</strong></div>
    <div class="result-field"><span>Classification</span><strong>{{ $record->species?->classification ?? $globalSpecies['classification'] ?? $globalSpecies['family'] ?? 'Pending verified species mapping.' }}</strong></div>
    <div class="result-field"><span>Habitat</span><strong>{{ $record->species?->habitat ?? $globalSpecies['habitat'] ?? 'Pending verified project data.' }}</strong></div>
    <div class="result-field"><span>Expert correction</span><strong>{{ $record->expertSpecies?->common_name ?? ($record->needs_retraining ? 'Marked for retraining review' : 'No expert correction recorded.') }}</strong></div>
    <div class="result-field"><span>Location</span><strong>{{ $record->location_label ?: ($hasCoordinates ? $record->latitude.', '.$record->longitude : 'No location recorded.') }}</strong></div>
    @if($hasCoordinates)
        <div class="result-field"><span>Location reliability</span><strong>{{ $locationReliability }}</strong></div>
        <div class="result-field"><span>Coordinates</span><strong>{{ number_format($record->latitude, 6) }}, {{ number_format($record->longitude, 6) }}{{ $record->location_accuracy_meters ? ' +/- '.number_format($record->location_accuracy_meters, 0).' m' : '' }}</strong></div>
        <div class="result-location-actions">
            <a class="button result-location-button" href="{{ route('recognition.map', ['scan' => $record->scan_reference]) }}">
                <i data-lucide="map-pin"></i>View on Recognition Map
            </a>
            <a class="button muted result-location-button" href="{{ $gpsUrl }}" target="_blank" rel="noopener">
                <i data-lucide="map-pin"></i>Open GPS Location
            </a>
        </div>
    @endif
    @if($record->capture_notes)<div class="result-field"><span>Capture notes</span><strong>{{ $record->capture_notes }}</strong></div>@endif
    @if(! empty($globalSpecies['visual_characteristics']))
        <div class="result-field"><span>Visual traits</span><strong>{{ $globalSpecies['visual_characteristics'] }}</strong></div>
    @endif
    @if(! empty($globalSpecies['geographic_range']))
        <div class="result-field"><span>Geographic range</span><strong>{{ $globalSpecies['geographic_range'] }}</strong></div>
    @endif
    <div class="result-field"><span>Edible status</span><strong>{{ $record->species?->edible_status ?? $globalSpecies['edible_status'] ?? 'No verified edible-status data. Do not use this result as food-safety proof.' }}</strong></div>
    @if(! empty($globalSpecies['caution_notes']))
        <div class="result-field"><span>Caution</span><strong>{{ $globalSpecies['caution_notes'] }}</strong></div>
    @endif
    @if(! empty($globalSpecies['reference_hint']))
        <div class="result-field"><span>Reference hint</span><strong>{{ $globalSpecies['reference_hint'] }}</strong></div>
    @endif
    @foreach(($record->quality_warnings ?? []) as $warning)<p class="warning result-alert">{{ $warning }}</p>@endforeach
    @if($record->failure_reason)<p class="error result-alert">{{ $record->failure_reason }}</p>@endif
</article>
@if($record->feedback->isNotEmpty())
<article class="panel feedback-history-card">
    <h2>Feedback Status</h2>
    @foreach($record->feedback as $feedback)
        <div class="result-field">
            <span>{{ str_replace('_', ' ', $feedback->category) }} &middot; {{ str_replace('_', ' ', $feedback->status) }}</span>
            <strong>{{ $feedback->admin_response ?: $feedback->description }}</strong>
        </div>
    @endforeach
</article>
@endif
<form class="panel feedback-card" method="post" action="{{ route('feedback.store', $record) }}">@csrf
    <h2>Report Result</h2>
    <select name="category"><option value="incorrect_prediction">Incorrect prediction</option><option value="unclear_result">Unclear result</option><option value="unsupported_crab">Unsupported crab</option><option value="technical_issue">Technical issue</option><option value="image_processing_failure">Image-processing failure</option><option value="other">Other</option></select>
    <textarea name="description" placeholder="Describe what happened" required></textarea>
    <button class="button muted"><i data-lucide="alert-triangle"></i>Submit Feedback</button>
</form>
<section class="notice professional-note"><i data-lucide="info"></i><span>Decision-support only. Do not treat confidence as proof that a species is edible or safe for consumption.</span></section>
@endsection
