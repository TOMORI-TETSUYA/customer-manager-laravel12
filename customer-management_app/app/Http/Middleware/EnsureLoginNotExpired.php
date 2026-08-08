<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ログインからの経過日数による強制ログアウト (§15)
 *
 * 「ログイン状態を保持する」を使うと、クッキーがある限りログイン状態が
 * 続いてしまう。また通常のセッションも、操作を続けている間は
 * SESSION_LIFETIME(無操作での期限)が延び続けるため、いつまでも切れない。
 *
 * そこで最後にログインした日時からの経過日数を毎リクエストで確認し、
 * 上限を超えていたら、その場でログアウトさせて再認証を求める。
 *
 * 日数は config/auth.php の login_max_days (.env の LOGIN_MAX_DAYS)。
 *
 * 基準にしている last_login_at は、ログイン画面からIDとパスワードを入力して
 * 認証したときだけ更新される。クッキーによる自動ログインでは更新されないため、
 * 「最後に自分でログインした日から◯日」という意図どおりに働く。
 */
class EnsureLoginNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $maxDays = (int) config('auth.login_max_days');

        // 0 以下なら期限なし(機能を無効化)
        if ($user === null || $maxDays <= 0 || $user->last_login_at === null) {
            return $next($request);
        }

        // copy() を挟むのは、addDays() が元の値を書き換えてしまうため
        $expiresAt = $user->last_login_at->copy()->addDays($maxDays);

        if ($expiresAt->isFuture()) {
            return $next($request);
        }

        // クッキーで自動的に復帰されないよう、トークンも捨てる
        $user->forceFill(['remember_token' => null])->save();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors([
                'login_id' => "前回のログインから{$maxDays}日が経過したため、自動的にログアウトしました。もう一度ログインしてください。",
            ]);
    }
}
