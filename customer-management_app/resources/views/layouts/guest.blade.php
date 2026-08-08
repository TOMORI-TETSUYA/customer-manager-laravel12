<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- 検索エンジンへの登録拒否。
         ログイン画面は未認証でも開けるため、ここが唯一クローラーから
         到達しうる画面になる。他画面と同じ指示を必ず出しておく。 --}}
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex, notranslate">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
    <meta name="bingbot" content="noindex, nofollow, noarchive, nosnippet">

    {{-- ブラウザのタブは画面名を出さず「Patron Hub」だけを表示する --}}
    <title>Patron Hub</title>

    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">

    <link
        rel="stylesheet"
        href="/vendor/bootstrap/5.3.8/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="{{ $phAsset('/css/app.css') }}"
    >

    @stack('styles')

    <script
        src="/vendor/bootstrap/5.3.8/js/bootstrap.bundle.min.js"
        defer
    ></script>

    <script
        src="{{ $phAsset('/js/app.js') }}"
        defer
    ></script>

    @stack('scripts')
</head>
<body>
    @yield('content')
</body>
</html>
