@extends('layouts.app')
@section('content')
<section class="page-head">
    <div><p class="eyebrow">Admin accounts</p><h1>Accounts</h1></div>
    <div class="admin-head-actions">
        <a class="button muted" href="{{ route('admin.dashboard') }}"><i data-lucide="bar-chart-3"></i>Dashboard</a>
        <a class="button" href="{{ route('admin.accounts.create') }}"><i data-lucide="user-plus"></i>Add Account</a>
    </div>
</section>

<form class="filters admin-filters">
    <label class="input-icon"><i data-lucide="search"></i><input name="q" value="{{ request('q') }}" placeholder="Search accounts"></label>
    <select name="role">
        <option value="">Any role</option>
        @foreach($roles as $role)
            <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
        @endforeach
    </select>
    <select name="status">
        <option value="">Any status</option>
        @foreach($statuses as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <button class="button small"><i data-lucide="filter"></i>Filter</button>
</form>

<div class="admin-table admin-accounts-table">
    <div class="admin-table-head"><span>Account</span><span>Access</span><span>Scans</span><span></span></div>
    @forelse($users as $account)
        <article class="admin-table-row">
            <div>
                <strong>{{ $account->name }}</strong>
                <span>{{ $account->email }}</span>
            </div>
            <div class="admin-status-list">
                <span class="badge">{{ $account->role }}</span>
                <span class="badge {{ $account->account_status === 'active' ? '' : 'muted-badge' }}">{{ $account->account_status }}</span>
            </div>
            <div>
                <strong>{{ $account->recognition_records_count }}</strong>
                <span>recognition records</span>
            </div>
            <div class="admin-row-actions">
                <a class="icon-button" href="{{ route('admin.accounts.edit', $account) }}" aria-label="Edit {{ $account->name }}"><i data-lucide="pencil"></i></a>
            </div>
        </article>
    @empty
        <div class="empty">No accounts found.</div>
    @endforelse
</div>

{{ $users->links('components.pagination') }}
@endsection
