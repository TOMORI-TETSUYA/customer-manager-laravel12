@extends('layouts.app')

@section('title', 'ユーザー追加')

@push('styles')
    <link rel="stylesheet" href="{{ $phAsset('/css/user.css') }}">
@endpush

@push('scripts')
    <script src="{{ $phAsset('/js/user-form.js') }}" defer></script>
@endpush

@php
    // 作成できるロールはログイン中のロールで変わる (§16)。
    //   管理者 : 管理者・職員・メンバー / 職員 : メンバーのみ
    $roleValues  = collect($roles)->pluck('value')->all();
    $defaultRole = old('role', in_array('staff', $roleValues, true) ? 'staff' : ($roleValues[0] ?? ''));
@endphp

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
            data-user-form
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
                    <label class="ph-field__label" for="password">
                        初期パスワード<span class="is-required">必須</span>
                    </label>
                    <div class="usr-input-group">
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            data-password-input
                            required
                        >
                        <button
                            type="button"
                            class="usr-copy-btn"
                            data-password-toggle
                            aria-pressed="false"
                            aria-label="パスワードを表示"
                        >表示</button>
                    </div>

                    {{-- 自動生成と、3項目まとめてのコピー --}}
                    <div class="usr-pw-tools">
                        <button
                            type="button"
                            class="ph-btn ph-btn--ghost ph-btn--sm"
                            data-password-generate
                        >パスワードを自動生成</button>

                        <button
                            type="button"
                            class="ph-btn ph-btn--ghost ph-btn--sm"
                            data-copy-bundle="form"
                        >ログインID・パスワード・表示名をコピー</button>

                        <span class="usr-pw-status" data-password-status role="status"></span>
                    </div>

                    <p class="ph-field__help">
                        12文字以上・英字と数字を含めてください。初回ログイン時に本人が変更します。<br>
                        作成後、この画面を離れると平文は二度と表示できません。必ず控えてから作成してください。
                    </p>
                    @error('password')
                        <p class="ph-field__error">{{ $message }}</p>
                        <p class="ph-field__help">
                            入力内容は保持されません。「パスワードを自動生成」で作り直してください。
                        </p>
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
                            <option value="{{ $role->value }}" @selected($defaultRole === $role->value)>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                    @if (count($roles) === 1)
                        <p class="ph-field__help">
                            作成できるのは「{{ $roles[0]->label() }}」のみです。
                        </p>
                    @endif
                    @error('role')
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
