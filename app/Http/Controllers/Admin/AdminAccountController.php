<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminAccountController extends Controller
{
    private const ROLES = ['admin', 'user'];
    private const STATUSES = ['active', 'suspended'];

    public function index(Request $request)
    {
        $query = User::query()->withCount('recognitionRecords')->latest();

        if ($request->filled('q')) {
            $query->where(function ($inner) use ($request) {
                $term = '%'.$request->q.'%';
                $inner->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('account_status', $request->status);
        }

        return view('admin.accounts.index', [
            'users' => $query->paginate(12)->withQueryString(),
            'roles' => self::ROLES,
            'statuses' => self::STATUSES,
        ]);
    }

    public function create()
    {
        return view('admin.accounts.create', [
            'account' => new User(['role' => 'user', 'account_status' => 'active']),
            'roles' => self::ROLES,
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $account = User::create($this->validated($request));
        $this->audit($request, 'created', $account, null, $account->toArray());

        return redirect()->route('admin.accounts.index')->with('status', 'Account created.');
    }

    public function edit(User $account)
    {
        return view('admin.accounts.edit', [
            'account' => $account,
            'roles' => self::ROLES,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, User $account)
    {
        $data = $this->validated($request, $account);

        if ($account->is($request->user()) && ($data['role'] !== 'admin' || $data['account_status'] !== 'active')) {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove your own admin access or suspend your own account.',
            ]);
        }

        $old = $account->toArray();
        $account->update($data);
        $this->audit($request, 'updated', $account, $old, $account->fresh()->toArray());

        return redirect()->route('admin.accounts.index')->with('status', 'Account updated.');
    }

    private function validated(Request $request, ?User $account = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account)],
            'role' => ['required', Rule::in(self::ROLES)],
            'account_status' => ['required', Rule::in(self::STATUSES)],
            'password' => [$account ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    private function audit(Request $request, string $action, User $account, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'account.'.$action,
            'entity_type' => User::class,
            'entity_id' => $account->id,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
