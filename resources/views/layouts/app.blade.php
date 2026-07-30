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
@php
    $currentRouteName = request()->route()?->getName() ?? 'page';
    $tutorial = [
        'key' => $currentRouteName,
        'title' => 'Page Tutorial',
        'intro' => 'Use this guide to move through the current mobile screen.',
        'steps' => [
            'Review the page heading to confirm where you are.',
            'Use the main controls in the content area to complete the page task.',
            'Use the bottom navigation to move between primary pages.',
        ],
    ];

    if (request()->routeIs('home')) {
        $tutorial = [
            'key' => 'home',
            'title' => 'Welcome Tutorial',
            'intro' => 'Start here when you are setting up CrabAI Pro on a phone.',
            'steps' => [
                'Create an account or log in to save scans and history.',
                'Use Install App to add CrabAI Pro to your mobile home screen when the browser supports it.',
                'After sign-in, use Scan, History, Chat, and More from the bottom navigation.',
            ],
        ];
    } elseif (request()->routeIs('login')) {
        $tutorial = [
            'key' => 'login',
            'title' => 'Login Tutorial',
            'intro' => 'Sign in to open your saved scan workspace.',
            'steps' => [
                'Enter the email address registered for your account.',
                'Use the eye icon if you need to check the password before submitting.',
                'Submit the form to continue to your dashboard.',
            ],
        ];
    } elseif (request()->routeIs('register')) {
        $tutorial = [
            'key' => 'register',
            'title' => 'Account Tutorial',
            'intro' => 'Create an account before saving crab recognition results.',
            'steps' => [
                'Enter your name, email address, and password.',
                'Confirm the password before submitting the form.',
                'After registration, start a scan or open the chatbot from the mobile navigation.',
            ],
        ];
    } elseif (request()->routeIs('dashboard')) {
        $tutorial = [
            'key' => 'dashboard',
            'title' => 'Dashboard Tutorial',
            'intro' => 'Use the dashboard as a quick status view of your recognition work.',
            'steps' => [
                'Check the summary cards for total scans, successful results, low confidence cases, and species data.',
                'Use New Scan to start image recognition.',
                'Open recent results from the activity list or use History for the full archive.',
            ],
        ];
    } elseif (request()->routeIs('recognition.create')) {
        $tutorial = [
            'key' => 'recognition-create',
            'title' => 'Scan Tutorial',
            'intro' => 'Capture or upload a crab image with enough context for reliable recognition.',
            'steps' => [
                'Upload an image or open the camera, then keep the whole crab visible in bright light.',
                'Attach location with Use Location or rely on photo GPS metadata when available.',
                'Complete the capture checklist before pressing Analyze Image.',
            ],
        ];
    } elseif (request()->routeIs('recognition.history')) {
        $tutorial = [
            'key' => 'recognition-history',
            'title' => 'History Tutorial',
            'intro' => 'Use history to review, search, export, or delete recognition records.',
            'steps' => [
                'Search by scan, species, status, or date filters.',
                'Open any row to inspect the recognition result in detail.',
                'Use CSV or PDF when you need a filtered export of the current list.',
            ],
        ];
    } elseif (request()->routeIs('recognition.show')) {
        $tutorial = [
            'key' => 'recognition-show',
            'title' => 'Result Tutorial',
            'intro' => 'Review the recognition output before using it for a decision.',
            'steps' => [
                'Check the predicted species, confidence, processing time, and model version.',
                'Review AI consensus, location reliability, and warnings before trusting the result.',
                'Submit feedback if the result is incorrect or needs review.',
            ],
        ];
    } elseif (request()->routeIs('crab-chat.index')) {
        $tutorial = [
            'key' => 'crab-chat',
            'title' => 'Chat Tutorial',
            'intro' => 'Ask crab identification and care questions in the chatbot.',
            'steps' => [
                'Use a suggested prompt or type a crab-related question.',
                'Keep questions focused on crab traits, habitat, handling, or recognition guidance.',
                'Use the bottom navigation to return to scanning or history.',
            ],
        ];
    } elseif (request()->routeIs('profile.*')) {
        $tutorial = [
            'key' => 'profile',
            'title' => 'Profile Tutorial',
            'intro' => 'Manage your account details and review your scan activity.',
            'steps' => [
                'Check the profile stats for scans, recognized records, feedback, and latest activity.',
                'Update your name or email when needed.',
                'Enter current and new passwords only when changing your password.',
            ],
        ];
    } elseif (request()->routeIs('recognition.map')) {
        $tutorial = [
            'key' => 'recognition-map',
            'title' => 'Map Tutorial',
            'intro' => 'Explore located scans and possible crab range overlays.',
            'steps' => [
                'Use filters to narrow the map by species, confidence, or scan reference.',
                'Select map pins to see scan details and open the full result.',
                'Use range overlays as supporting context, not as proof of species identity.',
            ],
        ];
    } elseif (request()->routeIs('reports.*')) {
        $tutorial = [
            'key' => 'reports',
            'title' => 'Reports Tutorial',
            'intro' => 'Build filtered scan summaries for export.',
            'steps' => [
                'Choose filters for date range, status, species, or confidence.',
                'Review the summary cards before exporting.',
                'Use CSV for spreadsheet work or PDF for a formatted report.',
            ],
        ];
    } elseif (request()->routeIs('training.*')) {
        $tutorial = [
            'key' => 'training',
            'title' => 'Training Dataset Tutorial',
            'intro' => 'Review records that may improve future model training.',
            'steps' => [
                'Use the filters to find low-confidence or corrected recognition records.',
                'Open records that need inspection before including them in training review.',
                'Export the filtered dataset when preparing offline review or model work.',
            ],
        ];
    } elseif (request()->routeIs('models.comparison')) {
        $tutorial = [
            'key' => 'models-comparison',
            'title' => 'Model Comparison Tutorial',
            'intro' => 'Compare recognition model behavior across recent scan data.',
            'steps' => [
                'Review the top stats for best confidence, fastest model, registry versions, and scope.',
                'Compare model cards for confidence, speed, and review signals.',
                'Use the page to decide which model results need admin review.',
            ],
        ];
    } elseif (request()->routeIs('species.show')) {
        $tutorial = [
            'key' => 'species-show',
            'title' => 'Species Detail Tutorial',
            'intro' => 'Read species reference details alongside related recognition activity.',
            'steps' => [
                'Review common name, scientific name, supported status, and scan count.',
                'Use the detail sections to compare traits, habitat, and range information.',
                'Open the external reference when you need source material.',
            ],
        ];
    } elseif (request()->routeIs('offline')) {
        $tutorial = [
            'key' => 'offline',
            'title' => 'Offline Tutorial',
            'intro' => 'Use this screen when the app cannot reach the network or AI service.',
            'steps' => [
                'Check your connection before starting a recognition request.',
                'Cached guidance may remain available while recognition is unavailable.',
                'Return to Scan when the connection or AI service is reachable again.',
            ],
        ];
    } elseif (request()->routeIs('admin.dashboard')) {
        $tutorial = [
            'key' => 'admin-dashboard',
            'title' => 'Admin Dashboard Tutorial',
            'intro' => 'Use the admin dashboard to monitor recognition operations.',
            'steps' => [
                'Review AI service status and operational summary cards.',
                'Use quick actions for accounts, scan data, species, feedback, and models.',
                'Open the bottom More menu for secondary admin pages on mobile.',
            ],
        ];
    } elseif (request()->routeIs('admin.accounts.*')) {
        $tutorial = [
            'key' => 'admin-accounts',
            'title' => 'Accounts Tutorial',
            'intro' => 'Manage user and admin account records.',
            'steps' => [
                'Search or filter accounts before editing.',
                'Use Add Account to create a new user or admin account.',
                'Open Edit to update names, emails, roles, or passwords.',
            ],
        ];
    } elseif (request()->routeIs('admin.scans.*')) {
        $tutorial = [
            'key' => 'admin-scans',
            'title' => 'Scan Data Tutorial',
            'intro' => 'Review and maintain recognition scan records.',
            'steps' => [
                'Search or filter scans by reference, user, status, model, or date.',
                'Open a result for inspection or edit scan metadata from the row actions.',
                'Export CSV when you need an admin copy of the filtered scan set.',
            ],
        ];
    } elseif (request()->routeIs('admin.species.*')) {
        $tutorial = [
            'key' => 'admin-species',
            'title' => 'Species Tutorial',
            'intro' => 'Maintain the species library used by recognition and reference pages.',
            'steps' => [
                'Search species before adding a duplicate record.',
                'Use Add Species or Edit to manage names, support status, ranges, and references.',
                'Keep model class mappings aligned with the active AI service.',
            ],
        ];
    } elseif (request()->routeIs('admin.feedback.*')) {
        $tutorial = [
            'key' => 'admin-feedback',
            'title' => 'Feedback Tutorial',
            'intro' => 'Review user feedback on recognition results.',
            'steps' => [
                'Filter feedback by status to find pending reviews.',
                'Compare user correction notes with the original scan result.',
                'Save review decisions so the record can support future dataset work.',
            ],
        ];
    } elseif (request()->routeIs('admin.models.*')) {
        $tutorial = [
            'key' => 'admin-models',
            'title' => 'Models Tutorial',
            'intro' => 'Manage model versions and AI service synchronization.',
            'steps' => [
                'Check AI service status, threshold, registered versions, and active version.',
                'Use Sync AI Service before registering or activating model data.',
                'Activate only the version that should drive future recognition requests.',
            ],
        ];
    }
@endphp
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
            <button class="ghost-button tutorial-toggle" type="button" data-tutorial-open aria-label="Open page tutorial" aria-controls="pageTutorial" aria-expanded="false">
                <i data-lucide="info" aria-hidden="true"></i>
                <span>Tutorial</span>
            </button>
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
<div class="tutorial-sheet" id="pageTutorial" data-tutorial-key="{{ $tutorial['key'] }}" hidden>
    <div class="tutorial-sheet-backdrop" data-tutorial-close></div>
    <section class="tutorial-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="pageTutorialTitle" aria-describedby="pageTutorialIntro">
        <div class="tutorial-sheet-grip" aria-hidden="true"></div>
        <header class="tutorial-sheet-head">
            <div>
                <p class="eyebrow">Mobile tutorial</p>
                <h2 id="pageTutorialTitle">{{ $tutorial['title'] }}</h2>
            </div>
            <button class="icon-button" type="button" data-tutorial-close aria-label="Close tutorial"><i data-lucide="x"></i></button>
        </header>
        <p class="tutorial-intro" id="pageTutorialIntro">{{ $tutorial['intro'] }}</p>
        <ol class="tutorial-steps">
            @foreach($tutorial['steps'] as $step)
                <li><span>{{ $loop->iteration }}</span><p>{{ $step }}</p></li>
            @endforeach
        </ol>
        <div class="tutorial-actions">
            <button class="button" type="button" data-tutorial-close>Got it</button>
        </div>
    </section>
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
