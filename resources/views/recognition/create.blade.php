@extends('layouts.app')
@section('content')
<section class="page-head app-page-head scan-head"><div><p class="eyebrow">Guided capture</p><h1>New Crab Scan</h1></div><span class="badge"><i data-lucide="smartphone"></i>Mobile scan ready</span></section>
<section class="scan-layout mobile-stack recognition-workflow">
    <div class="camera-stage">
        <video id="camera" autoplay playsinline></video>
        <div class="camera-placeholder" id="cameraPlaceholder"><i data-lucide="camera"></i><strong>Center crab here</strong><span>Use bright light and keep the whole body visible.</span></div>
        <div class="frame-guide"></div>
    </div>
</section>
<canvas id="snapshot" hidden></canvas>
<form id="scanForm" class="panel action-panel mobile-action-panel scan-action-card" method="post" action="{{ route('recognition.store') }}" enctype="multipart/form-data" autocomplete="off">@csrf
    <label class="upload-dropzone" for="imageInput"><i data-lucide="upload"></i><span><strong>Upload crab image</strong><small id="imageInputLabel">JPEG, PNG, or WEBP</small></span></label>
    <input id="imageInput" class="scan-file-input" name="image" type="file" accept="image/jpeg,image/png,image/webp" required>
    <input id="latitudeInput" name="latitude" type="hidden" value="{{ old('latitude') }}">
    <input id="longitudeInput" name="longitude" type="hidden" value="{{ old('longitude') }}">
    <input id="locationAccuracyInput" name="location_accuracy_meters" type="hidden" value="{{ old('location_accuracy_meters') }}">
    <input name="capture_checks[]" type="hidden" value="one_crab">
    <input name="capture_checks[]" type="hidden" value="bright_lighting">
    <input name="capture_checks[]" type="hidden" value="whole_crab">
    <input name="capture_checks[]" type="hidden" value="minimal_clutter">
    <input name="capture_checks[]" type="hidden" value="no_covered_parts">
    <div class="field-grid scan-meta-grid">
        <div class="field-group">
            <label for="locationLabelInput">Location label</label>
            <div class="field-icon"><i data-lucide="map-pin"></i><input id="locationLabelInput" name="location_label" value="{{ old('location_label') }}" placeholder="Mangrove site, pond, market"></div>
        </div>
        <div class="field-group">
            <label for="captureNotesInput">Notes</label>
            <input id="captureNotesInput" name="capture_notes" value="{{ old('capture_notes') }}" placeholder="Color, size, habitat clue">
        </div>
    </div>
    <div class="scan-assist-row">
        <button type="button" id="locateScan" class="button muted small"><i data-lucide="map-pin"></i>Use Location</button>
        <span id="locationStatus" class="scan-assist-status">GPS required: use device location or GPS-tagged photo</span>
    </div>
    @foreach($errors->all() as $error)<p class="error">{{ $error }}</p>@endforeach
    <div class="actions"><button type="button" id="startCamera" class="button muted"><i data-lucide="camera"></i>Camera</button><button type="button" id="capture" class="button muted"><i data-lucide="sparkles"></i>Capture</button><button id="analyzeButton" class="button analyze-button" type="submit"><span class="button-spinner" aria-hidden="true"></span><i data-lucide="upload"></i><span class="button-label">Analyze Image</span></button></div>
    <p class="analyze-status" id="analyzeStatus" hidden>Analyzing image with AI providers...</p>
    <div class="offline-queue-panel" id="offlineQueuePanel" hidden>
        <span><i data-lucide="wifi-off"></i><strong id="offlineQueueCount">0</strong> queued scan(s)</span>
        <button type="button" class="button muted small" id="syncOfflineQueue"><i data-lucide="refresh-cw"></i>Sync</button>
    </div>
</form>
@endsection
