<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 初回パスワード変更 (§5.1 / §15.1)
 */
class PasswordChangeController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function edit(): View
    {
        return view('auth.password-change');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password'             => $request->string('password')->toString(),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        $this->auditLog->record('password_changed', 'user', $user->id);

        return redirect()
            ->route('dashboard')
            ->with('status', 'パスワードを変更しました。');
    }
}
