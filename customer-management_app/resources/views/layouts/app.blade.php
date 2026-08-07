<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">

    <title>@yield('title', 'Patron Hub') | Patron Hub</title>

    <link rel="icon" href="/favicon.ico">

    <link
        rel="stylesheet"
        href="/vendor/bootstrap/5.3.8/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="/css/app.css"
    >

    <link
        rel="stylesheet"
        href="/css/responsive.css"
    >

    @stack('styles')

    <script
        src="/vendor/bootstrap/5.3.8/js/bootstrap.bundle.min.js"
        defer
    ></script>

    <script
        src="/js/app.js"
        defer
    ></script>

    @stack('scripts')
</head>
<body>
<div class="ph-shell">

    {{-- ヘッダー (§11.1) --}}
    <header class="ph-header">
        <button
            type="button"
            class="ph-menu-toggle"
            data-bs-toggle="offcanvas"
            data-bs-target="#phMenu"
            aria-controls="phMenu"
            aria-label="メニューを開く"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>

        <a class="ph-header__brand" href="{{ route('dashboard') }}">
            <span class="ph-mark" aria-hidden="true"></span>
            Patron Hub
        </a>

        <div class="ph-header__spacer"></div>

        <div class="ph-header__user">
            <span class="ph-header__username">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="ph-btn ph-btn--ghost ph-btn--sm">
                    ログアウト
                </button>
            </form>
        </div>
    </header>

    <div class="ph-body">

        {{-- サイドメニュー(パソコン) --}}
        <nav class="ph-sidebar" aria-label="メインメニュー">
            <div class="ph-sidebar__inner">
                @include('components.nav-links')
            </div>
        </nav>

        {{-- メインコンテンツ --}}
        <main class="ph-main">
            <div class="ph-container">

                @if (session('status'))
                    <div class="ph-alert ph-alert--success" role="status" data-autodismiss>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any() && ! View::hasSection('suppress-error-summary'))
                    <div class="ph-alert ph-alert--danger" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')

            </div>
        </main>
    </div>
</div>

{{-- サイドメニュー(スマートフォン: Offcanvas §11.2) --}}
<div
    class="offcanvas offcanvas-start ph-offcanvas"
    tabindex="-1"
    id="phMenu"
    aria-labelledby="phMenuLabel"
>
    <div class="offcanvas-header">
        <span class="ph-header__brand" id="phMenuLabel">
            <span class="ph-mark" aria-hidden="true"></span>
            Patron Hub
        </span>
        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas"
            aria-label="メニューを閉じる"
        ></button>
    </div>
    <div class="offcanvas-body">
        @include('components.nav-links')
    </div>
</div>

</body>
</html>
