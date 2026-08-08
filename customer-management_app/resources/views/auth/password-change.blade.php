@extends('layouts.guest')

@section('title', 'パスワード変更')

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
        <div class="login-aurora" aria-hidden="true">
            <div class="login-aurora__blob login-aurora__blob--indigo"></div>
            <div class="login-aurora__blob login-aurora__blob--teal"></div>
        </div>

        <section class="login-card" aria-labelledby="pw-title">
            <div class="login-card__body">

                <header class="login-brand">
                    <div class="login-brand__mark" aria-hidden="true"></div>
                    <h1 class="login-brand__title login-stagger" id="pw-title">
                        パスワード変更
                    </h1>
                    <p class="login-brand__sub login-stagger">
                        12文字以上・英字と数字を含めてください
                    </p>
                </header>

                @if (session('status'))
                    <div class="ph-alert ph-alert--info login-stagger" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <form
                    class="login-form"
                    method="POST"
                    action="{{ route('password.update') }}"
                    novalidate
                >
                    @csrf
                    @method('PUT')

                    <div class="ph-field login-stagger">
                        <label class="ph-field__label" for="current_password">
                            現在のパスワード<span class="is-required">必須</span>
                        </label>
                        <input
                            type="password"
                            class="form-control @error('current_password') is-invalid @enderror"
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                        >
                        @error('current_password')
                            <p class="ph-field__error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ph-field login-stagger">
                        <label class="ph-field__label" for="password">
                            新しいパスワード<span class="is-required">必須</span>
                        </label>
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            required
                        >
                        @error('password')
                            <p class="ph-field__error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ph-field login-stagger">
                        <label class="ph-field__label" for="password_confirmation">
                            新しいパスワード(確認)<span class="is-required">必須</span>
                        </label>
                        <input
                            type="password"
                            class="form-control"
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="login-submit login-stagger">
                        <span class="login-submit__inner">
                            <span class="login-submit__spinner" aria-hidden="true"></span>
                            <span class="login-submit__label">変更する</span>
                        </span>
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="login-footnote login-stagger">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm p-0">
                        ログアウトする
                    </button>
                </form>

            </div>
        </section>
    </main>
@endsection
