<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 初回パスワード変更の強制 (§5.1 / §15.1)
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->must_change_password) {
            return redirect()
                ->route('password.change')
                ->with('status', '最初にパスワードを変更してください。');
        }

        return $next($request);
    }
}
