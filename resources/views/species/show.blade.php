@extends('layouts.app')
@section('content')
@php
    $referenceImage = $species->reference_image_path ?: 'images/crab-species-fallback.svg';
    $referenceImageSrc = \Illuminate\Support\Str::startsWith($referenceImage, ['http://', 'https://'])
        ? $referenceImage
        : asset(ltrim($referenceImage, '/'));
@endphp
<section class="species-detail-hero">
    <img src="{{ $referenceImageSrc }}" alt="{{ $species->common_name }}">
    <div class="species-detail-copy">
        <p class="eyebrow">Species profile</p>
        <h1>{{ $species->common_name }}</h1>
        <p class="species-detail-subtitle"><em>{{ $species->scientific_name }}</em> @if($species->family) <span>{{ $species->family }}</span> @endif</p>
        <div class="species-detail-badges">
            <span class="badge"><i data-lucide="shield-check"></i>{{ $species->is_supported ? 'Supported' : 'Reference' }}</span>
            <span class="badge muted-badge"><i data-lucide="file-search"></i>{{ $species->recognition_records_count }} scans</span>
        </div>
    </div>
</section>

<section class="detail-grid species-detail-grid">
    <article class="panel species-detail-panel">
        <h2>Identification</h2>
        <div class="result-field"><span>Classification</span><strong>{{ $species->classification ?: 'Not recorded' }}</strong></div>
        <div class="result-field"><span>Visual traits</span><strong>{{ $species->visual_characteristics ?: 'Not recorded' }}</strong></div>
        <div class="result-field"><span>Model class</span><strong>{{ $species->model_class_name ?: 'Unmapped' }} @if($species->model_class_id !== null) #{{ $species->model_class_id }} @endif</strong></div>
    </article>
    <article class="panel species-detail-panel">
        <h2>Habitat And Caution</h2>
        <div class="result-field"><span>Habitat</span><strong>{{ $species->habitat ?: 'Not recorded' }}</strong></div>
        <div class="result-field"><span>Edible status</span><strong>{{ $species->edible_status ?: 'No food-safety claim recorded' }}</strong></div>
        <div class="result-field"><span>Caution</span><strong>{{ $species->caution_notes ?: 'Verify with local references before use.' }}</strong></div>
    </article>
</section>

@if($species->description)
<article class="panel species-detail-panel">
    <h2>Description</h2>
    <p class="feature-copy">{{ $species->description }}</p>
</article>
@endif

@auth
<section class="dashboard-split feature-split">
    <article class="panel species-detail-panel">
        <h2>Your Recent Scans</h2>
        <div class="compact-list">
            @forelse($recentRecords as $record)
                <a class="compact-row" href="{{ route('recognition.show', $record) }}">
                    <span>{{ $record->scan_reference }}</span>
                    <strong>{{ $record->confidence ? number_format($record->confidence * 100, 1).'%' : 'N/A' }}</strong>
                </a>
            @empty
                <div class="empty">No scans for this species yet.</div>
            @endforelse
        </div>
    </article>
    <article class="panel species-detail-panel">
        <h2>Related Species</h2>
        <div class="compact-list">
            @forelse($relatedSpecies as $item)
                <a class="compact-row" href="{{ route('species.show', $item) }}">
                    <span>{{ $item->common_name }}</span>
                    <strong>{{ $item->family ?: 'Reference' }}</strong>
                </a>
            @empty
                <div class="empty">No related species found.</div>
            @endforelse
        </div>
    </article>
</section>
@endauth

@if($species->reference_url)
<a class="button muted detail-reference" href="{{ $species->reference_url }}" target="_blank" rel="noopener"><i data-lucide="external-link"></i>{{ $species->reference_name ?: 'Open Reference' }}</a>
@endif
@endsection
