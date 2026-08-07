@extends('layouts.app')

@section('title', 'ユーザー追加')

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">ユーザー追加</h1>
        <div class="ph-page-head__actions">
            <a class="ph-btn ph-btn--ghost" href="{{ route('admin.users.index') }}">一覧へ戻る</a>
        </div>
    </div>

    <section class="ph-card">
        <form
            class="ph-card__body ph-form"
            method="POST"
            action="{{ route('admin.users.store') }}"
            novalidate
        >
            @csrf

            <div class="ph-form-grid">
                <div class="ph-field">
                    <label class="ph-field__label" for="login_id">
                        ログインID<span class="is-required">必須</span>
                    </label>
                    <input
                        type="text"
                        class="form-control @error('login_id') is-invalid @enderror"
                        id="login_id"
                        name="login_id"
                        value="{{ old('login_id') }}"
                        maxlength="50"
                        autocomplete="off"
                        required
                    >
                    <p class="ph-field__help">半角英数字と記号(_ - .)が使えます。</p>
                    @error('login_id')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="name">
                        表示名<span class="is-required">必須</span>
                    </label>
                    <input
                        type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        maxlength="100"
                        required
                    >
                    @error('name')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="role">
                        権限<span class="is-required">必須</span>
                    </label>
                    <select
                        class="form-select @error('role') is-invalid @enderror"
                        id="role"
                        name="role"
                        required
                    >
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', 'staff') === $role->value)>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="password">
                        初期パスワード<span class="is-required">必須</span>
                    </label>
                    <input
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        required
                    >
                    <p class="ph-field__help">
                        12文字以上・英字と数字を含めてください。初回ログイン時に本人が変更します。
                    </p>
                    @error('password')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="ph-form-actions">
                <button type="submit" class="ph-btn ph-btn--primary">ユーザーを作成する</button>
                <a class="ph-btn ph-btn--ghost" href="{{ route('admin.users.index') }}">キャンセル</a>
            </div>
        </form>
    </section>
@endsection
