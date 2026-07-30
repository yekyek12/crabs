@extends('layouts.app')
@section('content')
<section class="hero">
    <div class="hero-copy">
        <h1>Crab Recognition AI</h1>
        <p>Capture or upload a crab image, send it to a secured AI inference service, and review species information with confidence and image-quality warnings.</p>
        <div class="actions">
            @auth <a class="button" href="{{ route('recognition.create') }}"><i data-lucide="camera"></i>Start Scan</a> @else <a class="button" href="{{ route('register') }}" data-auth-modal="register"><i data-lucide="user-plus"></i>Create Account</a> @endauth
            <button class="button muted install-app-button" id="installAppButton" type="button"><i data-lucide="smartphone"></i><span>Install App</span></button>
        </div>
        <div class="hero-trust">
            <span><i data-lucide="shield-check"></i>Decision-support only</span>
            <span><i data-lucide="smartphone"></i>Installable PWA</span>
            <span><i data-lucide="activity"></i>AI service ready</span>
        </div>
    </div>
    <figure class="hero-visual">
        <img src="{{ asset('images/crab-hero-saaspro.webp') }}" alt="Crab prepared for AI recognition in a clean modern lab setting">
        <div class="hero-scan-frame" aria-hidden="true">
            <span></span><span></span><span></span><span></span>
        </div>
        <div class="hero-image-badge">
            <i data-lucide="camera"></i>
            <span>Mobile scan ready</span>
        </div>
    </figure>
</section>
@endsection
