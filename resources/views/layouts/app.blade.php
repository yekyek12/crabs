<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base-url" content="{{ url('/') }}">
    <meta name="theme-color" content="#0f766e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Crab Recognition AI">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="CrabAI">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="msapplication-TileColor" content="#0f766e">
    <meta name="msapplication-TileImage" content="{{ asset('pwa-icon-192.png') }}">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? 'Crab Recognition AI' }}</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('pwa-icon-192.png') }}">
    <script>
        (() => {
            let theme = 'day';
            try {
                theme = localStorage.getItem('crabai-theme') === 'night' ? 'night' : 'day';
            } catch {
                theme = 'day';
            }
            document.documentElement.dataset.theme = theme;
            document.documentElement.style.colorScheme = theme === 'night' ? 'dark' : 'light';
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@if(request()->routeIs('home')) landing-page @endif @if(request()->routeIs('dashboard')) dashboard-page @endif @if(request()->routeIs('recognition.history')) history-page @endif @if(request()->routeIs('profile.*') || request()->routeIs('recognition.map') || request()->routeIs('reports.*') || request()->routeIs('training.*') || request()->routeIs('models.comparison') || request()->routeIs('species.show')) feature-page @endif @if(request()->routeIs('admin.*')) admin-page @endif @if(request()->routeIs('login') || request()->routeIs('register')) auth-page @endif @if(request()->routeIs('recognition.create')) scan-page @endif @if(request()->routeIs('recognition.show')) result-page @endif @if(request()->routeIs('crab-chat.index')) chat-page @endif">
<div class="app-frame">
<aside class="sidebar">
    <a class="brand" href="{{ auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : route('home') }}"><span class="brand-mark"><img src="{{ asset('images/crabai-logo.png') }}" alt="CrabAI Pro logo"></span><span>CrabAI Pro</span></a>
    <div class="sidebar-caption">{{ auth()->check() && auth()->user()->isAdmin() ? 'Operations workspace' : 'AI recognition workspace' }}</div>
    <nav class="side-nav">
        @auth
            @if(auth()->user()->isAdmin())
                <a class="@if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}"><i data-lucide="bar-chart-3"></i>Admin</a>
                <a class="@if(request()->routeIs('admin.accounts.*')) active @endif" href="{{ route('admin.accounts.index') }}"><i data-lucide="users"></i>Accounts</a>
                <a class="@if(request()->routeIs('admin.scans.*')) active @endif" href="{{ route('admin.scans.index') }}"><i data-lucide="scan-search"></i>Scan Data</a>
                <a class="@if(request()->routeIs('admin.species.*')) active @endif" href="{{ route('admin.species.index') }}"><i data-lucide="database"></i>Species</a>
                <a class="@if(request()->routeIs('admin.feedback.*')) active @endif" href="{{ route('admin.feedback.index') }}"><i data-lucide="clipboard-check"></i>Feedback</a>
                <a class="@if(request()->routeIs('admin.models.*')) active @endif" href="{{ route('admin.models.index') }}"><i data-lucide="settings"></i>Models</a>
            @else
                <a class="@if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}"><i data-lucide="home"></i>Dashboard</a>
                <a class="@if(request()->routeIs('recognition.create')) active @endif" href="{{ route('recognition.create') }}"><i data-lucide="camera"></i>Scan</a>
                <a class="@if(request()->routeIs('recognition.history') || request()->routeIs('recognition.show')) active @endif" href="{{ route('recognition.history') }}"><i data-lucide="history"></i>History</a>
                <a class="@if(request()->routeIs('crab-chat.index')) active @endif" href="{{ route('crab-chat.index') }}"><i data-lucide="bot"></i>Crab Chatbot</a>
                <a class="@if(request()->routeIs('profile.*')) active @endif" href="{{ route('profile.edit') }}"><i data-lucide="user-round"></i>Profile</a>
                <a class="@if(request()->routeIs('recognition.map')) active @endif" href="{{ route('recognition.map') }}"><i data-lucide="map-pin"></i>Map</a>
                <a class="@if(request()->routeIs('reports.*')) active @endif" href="{{ route('reports.index') }}"><i data-lucide="file-text"></i>Reports</a>
                <a class="@if(request()->routeIs('training.*')) active @endif" href="{{ route('training.index') }}"><i data-lucide="database"></i>Training</a>
                <a class="@if(request()->routeIs('models.comparison')) active @endif" href="{{ route('models.comparison') }}"><i data-lucide="bar-chart-3"></i>Compare</a>
            @endif
        @else
            <a class="@if(request()->routeIs('home')) active @endif" href="{{ route('home') }}"><i data-lucide="home"></i>Overview</a>
            <a class="@if(request()->routeIs('crab-chat.index')) active @endif" href="{{ route('crab-chat.index') }}"><i data-lucide="bot"></i>Crab Chatbot</a>
            <a class="@if(request()->routeIs('login')) active @endif" href="{{ route('login') }}" data-auth-modal="login"><i data-lucide="log-in"></i>Login</a>
            <a class="@if(request()->routeIs('register')) active @endif" href="{{ route('register') }}" data-auth-modal="register"><i data-lucide="user-plus"></i>Register</a>
        @endauth
    </nav>
    <div class="sidebar-note">
        <i data-lucide="shield-check"></i>
        <span>{{ auth()->check() && auth()->user()->isAdmin() ? 'Admin controls for accounts, scan data, species, feedback, and models.' : 'Private image handling with secured AI API handoff.' }}</span>
    </div>
</aside>
<div class="workspace">
    <header class="topbar">
        <a class="brand mobile-brand" href="{{ auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : route('home') }}"><span class="brand-mark"><img src="{{ asset('images/crabai-logo.png') }}" alt="CrabAI Pro logo"></span><span>CrabAI Pro</span></a>
        <div class="topbar-actions">
            <button class="ghost-button theme-toggle" type="button" data-theme-toggle aria-label="Switch to night theme" aria-pressed="false">
                <i class="theme-icon theme-icon-day" data-lucide="sun" aria-hidden="true"></i>
                <i class="theme-icon theme-icon-night" data-lucide="moon" aria-hidden="true"></i>
                <span data-theme-label>Night theme</span>
            </button>
            @auth
                <span class="user-chip">{{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('logout') }}" data-loading-form data-loading-message="Logging out" data-loading-detail="Ending your session safely.">@csrf<button class="ghost-button" aria-label="Logout"><i data-lucide="log-out"></i><span>Logout</span></button></form>
            @else
                @unless(request()->routeIs('login'))
                    <a class="ghost-button" href="{{ route('login') }}" aria-label="Login" data-auth-modal="login"><i data-lucide="log-in"></i><span>Login</span></a>
                @endunless
                @unless(request()->routeIs('register'))
                    <a class="button small" href="{{ route('register') }}" aria-label="Register" data-auth-modal="register"><i data-lucide="user-plus"></i><span>Register</span></a>
                @endunless
            @endauth
        </div>
    </header>
    <main class="shell">
        @if(session('status'))<div class="toast">{{ session('status') }}</div>@endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>
</div>
</div>
@auth
@php
    $isAdmin = auth()->user()->isAdmin();

    if ($isAdmin) {
        $moreItems = [
            ['label' => 'Admin Species', 'description' => 'Species library and model class mapping', 'href' => route('admin.species.index'), 'icon' => 'database', 'active' => request()->routeIs('admin.species.*')],
            ['label' => 'Model Admin', 'description' => 'Model versions, thresholds, and sync tools', 'href' => route('admin.models.index'), 'icon' => 'settings', 'active' => request()->routeIs('admin.models.*')],
        ];
    } else {
        $moreSpecies = \App\Models\CrabSpecies::where('is_active', true)->orderBy('common_name')->first();
        $moreItems = [
            ['label' => 'Profile', 'description' => 'Account, password, and scan summary', 'href' => route('profile.edit'), 'icon' => 'user-round', 'active' => request()->routeIs('profile.*')],
            ['label' => 'Recognition Map', 'description' => 'Located scans by species and confidence', 'href' => route('recognition.map'), 'icon' => 'map-pin', 'active' => request()->routeIs('recognition.map')],
            ['label' => 'Reports', 'description' => 'Filtered PDF and CSV summaries', 'href' => route('reports.index'), 'icon' => 'file-text', 'active' => request()->routeIs('reports.*')],
            ['label' => 'Species Detail', 'description' => 'Full crab profile and related scans', 'href' => $moreSpecies ? route('species.show', $moreSpecies) : route('crab-chat.index'), 'icon' => 'leaf', 'active' => request()->routeIs('species.show')],
            ['label' => 'Training Dataset', 'description' => 'Low-confidence and corrected scan candidates', 'href' => route('training.index'), 'icon' => 'database', 'active' => request()->routeIs('training.*')],
            ['label' => 'Model Comparison', 'description' => 'Model version confidence, speed, and review metrics', 'href' => route('models.comparison'), 'icon' => 'bar-chart-3', 'active' => request()->routeIs('models.comparison')],
        ];
    }
@endphp
<nav class="bottom-nav">
    @if($isAdmin)
        <a class="@if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}"><i data-lucide="shield-check"></i><span>Admin</span></a>
        <a class="@if(request()->routeIs('admin.accounts.*')) active @endif" href="{{ route('admin.accounts.index') }}"><i data-lucide="users"></i><span>Accounts</span></a>
        <a class="@if(request()->routeIs('admin.scans.*')) active @endif" href="{{ route('admin.scans.index') }}"><i data-lucide="scan-search"></i><span>Scans</span></a>
        <a class="@if(request()->routeIs('admin.feedback.*')) active @endif" href="{{ route('admin.feedback.index') }}"><i data-lucide="clipboard-check"></i><span>Feedback</span></a>
    @else
        <a class="@if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}"><i data-lucide="home"></i><span>Home</span></a>
        <a class="@if(request()->routeIs('recognition.create')) active @endif" href="{{ route('recognition.create') }}"><i data-lucide="camera"></i><span>Scan</span></a>
        <a class="@if(request()->routeIs('recognition.history') || request()->routeIs('recognition.show')) active @endif" href="{{ route('recognition.history') }}"><i data-lucide="history"></i><span>History</span></a>
        <a class="@if(request()->routeIs('crab-chat.index')) active @endif" href="{{ route('crab-chat.index') }}"><i data-lucide="bot"></i><span>Chat</span></a>
    @endif
    <button class="@if(collect($moreItems)->contains('active', true)) active @endif" type="button" data-more-open aria-controls="moreSheet" aria-expanded="false"><i data-lucide="menu"></i><span>More</span></button>
</nav>
<div class="more-sheet" id="moreSheet" hidden>
    <div class="more-sheet-backdrop" data-more-close></div>
    <section class="more-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="moreSheetTitle">
        <div class="more-sheet-grip" aria-hidden="true"></div>
        <header class="more-sheet-head">
            <div>
                <p class="eyebrow">More pages</p>
                <h2 id="moreSheetTitle">Workspace Menu</h2>
            </div>
            <button class="icon-button" type="button" data-more-close aria-label="Close more menu"><i data-lucide="x"></i></button>
        </header>
        <div class="more-sheet-grid">
            @foreach($moreItems as $item)
                <a class="@if($item['active']) active @endif" href="{{ $item['href'] }}">
                    <i data-lucide="{{ $item['icon'] }}"></i>
                    <span><strong>{{ $item['label'] }}</strong><small>{{ $item['description'] }}</small></span>
                </a>
            @endforeach
        </div>
    </section>
</div>
@elseif(! request()->routeIs('home'))
<nav class="bottom-nav guest-bottom-nav">
    <a class="@if(request()->routeIs('home')) active @endif" href="{{ route('home') }}"><i data-lucide="home"></i><span>Home</span></a>
    <a class="@if(request()->routeIs('crab-chat.index')) active @endif" href="{{ route('crab-chat.index') }}"><i data-lucide="bot"></i><span>Chat</span></a>
    <a class="@if(request()->routeIs('login')) active @endif" href="{{ route('login') }}" data-auth-modal="login"><i data-lucide="log-in"></i><span>Login</span></a>
    <a class="@if(request()->routeIs('register')) active @endif" href="{{ route('register') }}" data-auth-modal="register"><i data-lucide="user-plus"></i><span>Register</span></a>
</nav>
@endauth
@guest
<div class="auth-modal" id="authModal" hidden>
    <div class="auth-modal-backdrop" data-auth-close></div>
    <section class="auth-modal-panel" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
        <button class="auth-modal-close" type="button" aria-label="Close auth form" data-auth-close><i data-lucide="x"></i></button>

        <form class="auth-card auth-modal-form" id="loginModalForm" method="post" action="{{ route('login') }}" data-loading-form data-loading-message="Logging in" data-loading-detail="Securing your workspace session.">@csrf
            <div class="auth-card-head">
                <h2 id="authModalTitle">Login</h2>
                <p>Use your registered account to access scans and detection history.</p>
            </div>
            <div class="field-group">
                <label for="modal-login-email">Email address</label>
                <div class="field-icon"><i data-lucide="mail"></i><input id="modal-login-email" name="email" type="email" placeholder="you@example.com" autocomplete="email" required></div>
            </div>
            <div class="field-group">
                <label for="modal-login-password">Password</label>
                <div class="field-icon password-field">
                    <i data-lucide="lock-keyhole"></i>
                    <input id="modal-login-password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="modal-login-password" aria-label="Show password" aria-pressed="false">
                        <i class="password-eye" data-lucide="eye" aria-hidden="true"></i>
                        <i class="password-eye-off" data-lucide="eye-off" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <label class="auth-check"><input type="checkbox" name="remember"> <span>Remember me on this device</span></label>
            <button class="button auth-submit"><i data-lucide="log-in"></i>Login</button>
            <p class="auth-switch">No account yet? <a href="{{ route('register') }}" data-auth-modal="register">Create one</a></p>
        </form>

        <form class="auth-card auth-modal-form" id="registerModalForm" method="post" action="{{ route('register') }}" data-loading-form data-loading-message="Creating account" data-loading-detail="Submitting your registration and preparing your dashboard." hidden>@csrf
            <div class="auth-card-head">
                <h2>Create Account</h2>
                <p>Set up your account to save scans and submit recognition feedback.</p>
            </div>
            <div class="field-group">
                <label for="modal-register-name">Full name</label>
                <div class="field-icon"><i data-lucide="user-round"></i><input id="modal-register-name" name="name" placeholder="Juan Dela Cruz" autocomplete="name" required></div>
            </div>
            <div class="field-group">
                <label for="modal-register-email">Email address</label>
                <div class="field-icon"><i data-lucide="mail"></i><input id="modal-register-email" name="email" type="email" placeholder="you@example.com" autocomplete="email" required></div>
            </div>
            <div class="field-grid">
                <div class="field-group">
                    <label for="modal-register-password">Password</label>
                    <div class="field-icon password-field">
                        <i data-lucide="lock-keyhole"></i>
                        <input id="modal-register-password" name="password" type="password" placeholder="Password" autocomplete="new-password" required>
                        <button class="password-toggle" type="button" data-password-toggle aria-controls="modal-register-password" aria-label="Show password" aria-pressed="false">
                            <i class="password-eye" data-lucide="eye" aria-hidden="true"></i>
                            <i class="password-eye-off" data-lucide="eye-off" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="field-group">
                    <label for="modal-register-password-confirmation">Confirm password</label>
                    <div class="field-icon password-field">
                        <i data-lucide="lock-keyhole"></i>
                        <input id="modal-register-password-confirmation" name="password_confirmation" type="password" placeholder="Confirm" autocomplete="new-password" required>
                        <button class="password-toggle" type="button" data-password-toggle aria-controls="modal-register-password-confirmation" aria-label="Show password" aria-pressed="false">
                            <i class="password-eye" data-lucide="eye" aria-hidden="true"></i>
                            <i class="password-eye-off" data-lucide="eye-off" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
            <button class="button auth-submit"><i data-lucide="user-plus"></i>Register</button>
            <p class="auth-switch">Already registered? <a href="{{ route('login') }}" data-auth-modal="login">Login</a></p>
        </form>
    </section>
</div>
@endguest
<div class="fullscreen-loader" id="fullscreenLoader" role="status" aria-live="polite" aria-busy="true" hidden>
    <section class="fullscreen-loader-card" aria-labelledby="fullscreenLoaderTitle">
        <span class="fullscreen-loader-spinner" aria-hidden="true"></span>
        <h2 id="fullscreenLoaderTitle" data-loading-title>Please wait</h2>
        <p data-loading-detail>Preparing your request.</p>
    </section>
</div>
</body>
</html>
