<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureLoginNotExpired;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // app コンテナは compose.yaml 上、ホストのリバースプロキシ経由でしか
        // 到達できない（ループバック限定公開）ため、'*' で全プロキシを信頼しても安全。
        // これが無いと isSecure() 等が false のままになり、http:// なURLが生成される。
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'password.changed' => EnsurePasswordChanged::class,
            'user.active'      => EnsureUserIsActive::class,
            'login.fresh'      => EnsureLoginNotExpired::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
