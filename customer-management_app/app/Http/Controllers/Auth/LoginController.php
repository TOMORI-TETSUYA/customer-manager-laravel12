<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ログイン処理 (§14 フローチャート / §15 セキュリティー)
 */
class LoginController extends Controller
{
    /** 認証失敗時の統一エラーメッセージ (§15.2) */
    private const FAILED_MESSAGE = 'ログインIDまたはパスワードが正しくありません。';

    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    /** AUTH-01 ログイン画面 */
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $throttleKey = $this->throttleKey($request);

        // 試行回数制限 (§15.2: 5回 / 1分 / ID+IP単位)
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->auditLog->record('login_blocked');

            throw ValidationException::withMessages([
                'login_id' => "ログイン試行回数が上限に達しました。{$seconds}秒後に再度お試しください。",
            ]);
        }

        $credentials = [
            'login_id'  => $request->string('login_id')->toString(),
            'password'  => $request->string('password')->toString(),
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            // ログイン失敗履歴を保存 (§14)。パスワードは記録しない。
            $this->auditLog->record('login_failed');

            throw ValidationException::withMessages([
                'login_id' => self::FAILED_MESSAGE,
            ]);
        }

        RateLimiter::clear($throttleKey);

        // セッション固定攻撃対策: セッションID再生成 (§15.4)
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->save();

        $this->auditLog->record('login_success', 'user', $user->id);

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->auditLog->record('logout', 'user', Auth::id());

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function throttleKey(LoginRequest $request): string
    {
        return Str::transliterate(
            Str::lower($request->string('login_id')->toString()) . '|' . $request->ip()
        );
    }
}
