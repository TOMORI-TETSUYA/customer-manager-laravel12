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

        // 試行回数制限 (§15.2)。回数・期間・遅延秒数は config/auth.php で設定する。
        if (RateLimiter::tooManyAttempts($throttleKey, $this->throttle('max_attempts'))) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->auditLog->record('login_blocked');

            // ブロック中はさらに応答を遅らせ、総当たりの効率を落とす
            $this->delayResponse($this->throttle('blocked_delay'));

            throw ValidationException::withMessages([
                'login_id' => $this->blockedMessage($seconds),
            ]);
        }

        $credentials = [
            'login_id'  => $request->string('login_id')->toString(),
            'password'  => $request->string('password')->toString(),
            'is_active' => true,
        ];

        // 「ログイン状態を保持する」で発行するクッキーの有効期限 (§15)。
        // Laravel の既定は約5年と長すぎるため、config/auth.php の
        // login_max_days に合わせる。
        // なお、クッキーの期限はブラウザ側の管理で改ざんされうるため、
        // 実際の締め出しは EnsureLoginNotExpired ミドルウェアが担う。
        $maxDays = (int) config('auth.login_max_days');

        if ($maxDays > 0) {
            Auth::guard()->setRememberDuration($maxDays * 24 * 60);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, $this->throttle('decay_minutes') * 60);

            // ログイン失敗履歴を保存 (§14)。パスワードは記録しない。
            $this->auditLog->record('login_failed');

            // 失敗のたびに応答を遅らせ、短時間に何度も試せないようにする
            $this->delayResponse($this->throttle('failed_delay'));

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

        // ロールごとに入口が異なる (§16)。
        //
        // メンバーが開ける画面は限られているため、直前に開こうとしていた
        // URL(intended)へ戻すとログイン直後に403になることがある。
        // 混乱を避けて、メンバーは常に既定の入口(顧客一覧)へ送る。
        if ($user->isMember()) {
            $request->session()->forget('url.intended');

            return redirect()->route($user->homeRouteName());
        }

        return redirect()->intended(route($user->homeRouteName()));
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

    /** config/auth.php の login_throttle から設定値を取り出す */
    private function throttle(string $key): int
    {
        return (int) config("auth.login_throttle.{$key}");
    }

    /** ブロック中に表示する文言。残り時間は分単位で丸めて伝える。 */
    private function blockedMessage(int $seconds): string
    {
        if ($seconds >= 60) {
            $minutes = (int) ceil($seconds / 60);

            return "ログイン試行回数が上限に達しました。約{$minutes}分後に再度お試しください。";
        }

        return "ログイン試行回数が上限に達しました。{$seconds}秒後に再度お試しください。";
    }

    /**
     * 応答を意図的に遅らせる (§15.2)
     *
     * 総当たり攻撃は「短時間に大量に試せること」が前提なので、
     * 1回あたりの所要時間を引き延ばして効率を落とす。
     *
     * 【注意】待っている間、PHP-FPM のプロセスを1つ占有し続ける。
     * 同時に遅延できる数は docker/php/www.conf の pm.max_children が上限。
     */
    private function delayResponse(int $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        // ブラウザを閉じられても中断させない。
        // 途中で止まると待たせる意味がなくなるため。
        ignore_user_abort(true);

        // 待つ時間のぶんだけ、PHPの実行時間の上限を延ばす
        set_time_limit($seconds + 30);

        sleep($seconds);
    }
}
