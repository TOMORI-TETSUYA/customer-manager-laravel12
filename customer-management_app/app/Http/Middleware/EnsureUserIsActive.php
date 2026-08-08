<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 無効化されたユーザーを毎リクエストで締め出す (§5.1 / §15)
 *
 * ログイン時の Auth::attempt でも is_active を見ているが、それだけでは
 * 「ログイン中のユーザーを無効化しても、そのセッションが生き続ける」
 * という穴が残る。ここで毎回確認して、無効ならその場でログアウトさせる。
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::logout();

            // remember-me クッキーで復帰されないようトークンも破棄する
            // (Auth::logout() 内でトークンは再生成されるが、明示的に消す)
            $user->forceFill(['remember_token' => null])->save();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['login_id' => 'このアカウントは利用できません。管理者にお問い合わせください。']);
        }

        return $next($request);
    }
}
