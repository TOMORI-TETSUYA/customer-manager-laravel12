@extends('layouts.guest')

@section('title', 'ログイン')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ $phAsset('/css/login.css') }}"
    >
@endpush

@push('scripts')
    <script
        src="{{ $phAsset('/js/login-animation.js') }}"
        defer
    ></script>
@endpush

@section('content')
    <main class="login-scene">

        {{-- レイヤー型グラデーション背景 (§12.1 / §13.3) --}}
        <div class="login-aurora" aria-hidden="true">
            <div class="login-aurora__blob login-aurora__blob--indigo"></div>
            <div class="login-aurora__blob login-aurora__blob--teal"></div>
            <div class="login-aurora__blob login-aurora__blob--violet"></div>
        </div>

        <section class="login-card" aria-labelledby="login-title">
            <div class="login-card__body">

                <header class="login-brand">
                    <div class="login-brand__mark" aria-hidden="true"></div>
                    <h1 class="login-brand__title login-stagger" id="login-title">
                        Patron <span class="is-gradient">Hub</span>
                    </h1>
                    <p class="login-brand__sub login-stagger">顧客管理システム</p>
                </header>

                @if (session('status'))
                    <div class="ph-alert ph-alert--info login-stagger" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <form
                    class="login-form"
                    method="POST"
                    action="{{ route('login.store') }}"
                    novalidate
                >
                    @csrf

                    <div class="ph-field login-stagger">
                        <label class="ph-field__label" for="login_id">
                            ログインID<span class="is-required">必須</span>
                        </label>
                        <input
                            type="text"
                            class="form-control @error('login_id') is-invalid @enderror"
                            id="login_id"
                            name="login_id"
                            value="{{ old('login_id') }}"
                            autocomplete="username"
                            inputmode="email"
                            required
                            autofocus
                        >
                        @error('login_id')
                            <p class="ph-field__error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ph-field login-stagger">
                        <label class="ph-field__label" for="password">
                            パスワード<span class="is-required">必須</span>
                        </label>
                        <div class="login-password">
                            <input
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                class="login-password__toggle"
                                aria-label="パスワードを表示する"
                            >
                                {{-- 表示 --}}
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12Z"/>
                                    <circle cx="12" cy="12" r="2.8"/>
                                </svg>
                                {{-- 非表示 --}}
                                <svg viewBox="0 0 24 24" aria-hidden="true" class="d-none">
                                    <path d="M2 12s3.5-6.5 10-6.5c2 0 3.7.6 5.1 1.4M22 12s-3.5 6.5-10 6.5c-2 0-3.7-.6-5.1-1.4"/>
                                    <path d="m4 20 16-16"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="ph-field__error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="login-remember login-stagger">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            @checked(old('remember'))
                        >
                        ログイン状態を保持する
                    </label>

                    <button type="submit" class="login-submit login-stagger">
                        <span class="login-submit__inner">
                            <span class="login-submit__spinner" aria-hidden="true"></span>
                            <span class="login-submit__label">ログインする</span>
                        </span>
                    </button>
                </form>

                <p class="login-footnote login-stagger">
                    パスワードをお忘れの場合は、システム管理者へ連絡してください。
                </p>

            </div>
        </section>
    </main>
@endsection
