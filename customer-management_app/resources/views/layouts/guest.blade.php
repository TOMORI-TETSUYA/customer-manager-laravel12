<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">

    <title>@yield('title', 'ログイン') | Patron Hub</title>

    <link rel="icon" href="/favicon.ico">

    <link
        rel="stylesheet"
        href="/vendor/bootstrap/5.3.8/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="/css/app.css"
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
    @yield('content')
</body>
</html>
