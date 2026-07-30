@php($editing = $account->exists)
<form class="panel admin-form" method="post" action="{{ $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}">
    @csrf
    @if($editing) @method('put') @endif

    <div class="field-grid">
        <div class="field-group">
            <label for="name">Name</label>
            <input id="name" name="name" value="{{ old('name', $account->name) }}" required>
        </div>
        <div class="field-group">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $account->email) }}" required>
        </div>
    </div>

    <div class="field-grid">
        <div class="field-group">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', $account->role) === $role)>{{ ucfirst($role) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field-group">
            <label for="account_status">Status</label>
            <select id="account_status" name="account_status" required>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(old('account_status', $account->account_status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="field-grid">
        <div class="field-group">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" @required(! $editing)>
        </div>
        <div class="field-group">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $editing)>
        </div>
    </div>

    @if($editing)
        <p class="form-hint">Leave password fields blank to keep the current password.</p>
    @endif

    @foreach($errors->all() as $error)<p class="error">{{ $error }}</p>@endforeach

    <div class="actions">
        <button class="button" type="submit"><i data-lucide="save"></i>{{ $editing ? 'Save Account' : 'Create Account' }}</button>
        <a class="button muted" href="{{ route('admin.accounts.index') }}"><i data-lucide="arrow-right"></i>Back</a>
    </div>
</form>
