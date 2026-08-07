<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * USR-01 ユーザー管理 (管理者のみ)
 */
class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function index(): View
    {
        Gate::authorize('manage-users');

        $users = User::query()
            ->orderByDesc('is_active')
            ->orderBy('login_id')
            ->paginate(50);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        Gate::authorize('manage-users');

        return view('users.create', ['roles' => UserRole::cases()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validated();

        $user = User::query()->create([
            ...$validated,
            'is_active'            => true,
            'must_change_password' => true, // 初回ログイン時に変更させる (§15.1)
        ]);

        $this->auditLog->record('user_create', 'user', $user->id);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "ユーザー「{$user->name}」を作成しました。初回ログイン時にパスワード変更が必要です。");
    }

    /** 有効・無効の切り替え (§5.1) */
    public function toggle(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        if ($user->id === Auth::id()) {
            return back()->withErrors(['user' => '自分自身を無効化することはできません。']);
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        $this->auditLog->record(
            $user->is_active ? 'user_enable' : 'user_disable',
            'user',
            $user->id,
        );

        return back()->with('status', 'ユーザーの状態を変更しました。');
    }
}
