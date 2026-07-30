@extends('layouts.app')
@section('content')
<section class="page-head"><div><p class="eyebrow">Operations center</p><h1>Admin Dashboard</h1></div><span class="badge" title="{{ $aiStatusDetail }}"><i data-lucide="activity"></i>AI {{ $aiStatus }}</span></section>
<div class="admin-quick-actions">
    <a class="button muted" href="{{ route('admin.accounts.index') }}"><i data-lucide="users"></i>Accounts</a>
    <a class="button muted" href="{{ route('admin.scans.index') }}"><i data-lucide="scan-search"></i>Scan Data</a>
    <a class="button muted" href="{{ route('admin.species.index') }}"><i data-lucide="database"></i>Species</a>
    <a class="button muted" href="{{ route('admin.feedback.index') }}"><i data-lucide="clipboard-check"></i>Feedback</a>
    <a class="button muted" href="{{ route('admin.models.index') }}"><i data-lucide="settings"></i>Models</a>
</div>
<section class="stats">
@foreach(['Users'=>$users,'Active'=>$activeUsers,'Suspended'=>$suspendedUsers,'Admins'=>$adminUsers,'Recognitions'=>$recognitions,'Today'=>$today,'This month'=>$month,'Low confidence'=>$low,'Failed AI'=>$failed,'Open feedback'=>$feedback,'Training'=>$trainingCandidates] as $label=>$value)
<div><i data-lucide="bar-chart-3"></i><strong>{{ is_float($value) ? number_format($value, 2) : $value }}</strong><span>{{ $label }}</span></div>
@endforeach
</section>
<section class="notice">Average confidence: {{ $avgConfidence ? number_format($avgConfidence * 100, 1).'%' : 'N/A' }}. Average inference time: {{ $avgTime ? number_format($avgTime).' ms' : 'N/A' }}. Reviewed accuracy: {{ $reviewedAccuracy !== null ? number_format($reviewedAccuracy * 100, 1).'%' : 'N/A' }}.</section>

<section class="dashboard-split">
    <article class="panel">
        <h2>Most Detected Species</h2>
        <div class="compact-list">
            @forelse($topSpecies as $item)
                <div class="compact-row">
                    <span>{{ $item->species?->common_name ?? 'Unknown species' }}</span>
                    <strong>{{ $item->scans }} &middot; {{ $item->avg_confidence ? number_format($item->avg_confidence * 100, 1).'%' : 'N/A' }}</strong>
                </div>
            @empty
                <div class="empty">No species metrics yet.</div>
            @endforelse
        </div>
    </article>
    <article class="panel">
        <h2>Recent Scan Data</h2>
        <div class="compact-list">
            @forelse($recentScans as $item)
                <a class="compact-row" href="{{ route('admin.scans.edit', $item) }}">
                    <span>{{ $item->scan_reference }}<br>{{ $item->user?->name ?? 'Deleted user' }}</span>
                    <strong>{{ $item->species?->common_name ?? $item->predicted_class ?? str_replace('_', ' ', $item->recognition_status) }}</strong>
                </a>
            @empty
                <div class="empty">No scan data yet.</div>
            @endforelse
        </div>
    </article>
    <article class="panel">
        <h2>Recent Feedback</h2>
        <div class="compact-list">
            @forelse($recentFeedback as $item)
                <a class="compact-row" href="{{ route('admin.feedback.index', ['status' => $item->status]) }}">
                    <span>{{ $item->user?->name ?? 'Unknown user' }}</span>
                    <strong>{{ str_replace('_', ' ', $item->category) }}</strong>
                </a>
            @empty
                <div class="empty">No feedback submitted.</div>
            @endforelse
        </div>
    </article>
</section>
@endsection
