@extends('layouts.app')

@section('title', 'ユーザー管理')

@push('styles')
    <link rel="stylesheet" href="{{ $phAsset('/css/user.css') }}">
@endpush

@push('scripts')
    <script src="{{ $phAsset('/js/user-form.js') }}" defer></script>
@endpush

@php
    // 直前の作成・再発行から渡された平文パスワード (フラッシュデータ)。
    // 次のリクエストで破棄されるため、再読み込みすると null になり
    // 一覧のパスワードは ●●●●● 表示へ戻る。
    $issued = session('issued_credential');
    $issuedUserId = is_array($issued) ? (int) ($issued['user_id'] ?? 0) : 0;
    $issuedLoginId = is_array($issued) ? (string) ($issued['login_id'] ?? '') : '';
    $issuedName = is_array($issued) ? (string) ($issued['name'] ?? '') : '';
    $issuedPassword = is_array($issued) ? (string) ($issued['password'] ?? '') : '';
@endphp

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">ユーザー管理</h1>
        @can('manage-users')
            <div class="ph-page-head__actions">
                <a class="ph-btn ph-btn--primary" href="{{ route('admin.users.create') }}">
                    ユーザーを追加する
                </a>
            </div>
        @endcan
    </div>

    @if ($issuedUserId > 0)
        <div class="usr-notice" role="alert">
            パスワードを平文で表示できるのは今この画面だけです。
            再読み込みするか他の画面へ移動すると <code>●●●●●</code> に戻り、二度と表示できません。
            「コピー」を押すとログインID・パスワード・表示名をまとめて写せます。
        </div>
    @endif

    <div class="ph-table-wrap usr-table-wrap">
        <table class="ph-table">
            <thead>
                <tr>
                    <th>ログインID</th>
                    <th>パスワード</th>
                    <th>表示名</th>
                    <th>権限</th>
                    <th>状態</th>
                    <th class="ph-nowrap">最終ログイン</th>
                    @can('manage-users')
                        <th></th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td class="ph-num">{{ $user->login_id }}</td>

                        <td>
                            @if ($issuedUserId === $user->id)
                                {{-- 発行直後の1回だけ平文を表示する --}}
                                <span class="usr-copyable">
                                    <code class="usr-pw usr-pw--plain">{{ $issuedPassword }}</code>
                                    <button
                                        type="button"
                                        class="usr-copy-btn"
                                        data-copy-bundle="row"
                                        data-copy-login="{{ $issuedLoginId }}"
                                        data-copy-password="{{ $issuedPassword }}"
                                        data-copy-name="{{ $issuedName }}"
                                        aria-label="ログインID・パスワード・表示名をコピー"
                                    >コピー</button>
                                </span>
                            @else
                                <span class="usr-copyable">
                                    <span
                                        class="usr-pw usr-pw--masked"
                                        title="パスワードはハッシュ化して保存されているため、平文を表示することはできません。"
                                    >●●●●●</span>
                                    @can('manage-users')
                                        {{-- 元のパスワードは復元できないので、新しく発行し直す。
                                             自分自身にも発行できる(その場で控えてもらう)。 --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.regenerate-password', $user) }}"
                                            data-confirm="{{ $user->id === auth()->id()
                                                ? 'あなた自身のパスワードを再発行します。今のパスワードは使えなくなるため、表示された新しいパスワードを必ず控えてください。よろしいですか？'
                                                : 'ユーザー「' . $user->name . '」のパスワードを再発行します。今のパスワードは使えなくなります。よろしいですか？' }}"
                                            class="d-inline"
                                        >
                                            @csrf
                                            <button type="submit" class="usr-copy-btn">自動生成</button>
                                        </form>
                                    @endcan
                                </span>
                            @endif
                        </td>

                        <td>{{ $user->name }}</td>
                        <td>{{ $user->role->label() }}</td>
                        <td>
                            @if ($user->is_active)
                                <span class="ph-badge ph-badge--success">有効</span>
                            @else
                                <span class="ph-badge ph-badge--muted">無効</span>
                            @endif
                            @if ($user->must_change_password)
                                <span class="ph-badge ph-badge--warning">初期PW</span>
                            @endif
                        </td>
                        <td class="ph-num ph-nowrap">
                            {{ $user->last_login_at?->isoFormat('YYYY/MM/DD HH:mm') ?? '—' }}
                        </td>
                        @can('manage-users')
                            <td class="ph-text-right ph-nowrap">
                                @if ($user->id !== auth()->id())
                                    <span class="usr-row-actions">
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.toggle', $user) }}"
                                            data-confirm="ユーザー「{{ $user->name }}」を{{ $user->is_active ? '無効化' : '有効化' }}します。よろしいですか？"
                                            class="d-inline"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="ph-btn ph-btn--ghost ph-btn--sm">
                                                {{ $user->is_active ? '無効にする' : '有効にする' }}
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.destroy', $user) }}"
                                            data-confirm="ユーザー「{{ $user->name }}」を削除します。ログインできなくなり、画面からは元に戻せません。よろしいですか？"
                                            class="d-inline"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ph-btn ph-btn--danger ph-btn--sm">
                                                削除
                                            </button>
                                        </form>
                                    </span>
                                @endif
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- 768px未満はカード表示 (§32.2) --}}
    <div class="usr-cards">
        @foreach ($users as $user)
            <article class="usr-card">
                <div class="usr-card__head">
                    <span class="usr-card__name">{{ $user->name }}</span>
                    @if ($user->is_active)
                        <span class="ph-badge ph-badge--success">有効</span>
                    @else
                        <span class="ph-badge ph-badge--muted">無効</span>
                    @endif
                    @if ($user->must_change_password)
                        <span class="ph-badge ph-badge--warning">初期PW</span>
                    @endif
                </div>

                <dl class="usr-card__list">
                    <dt>ログインID</dt>
                    <dd>{{ $user->login_id }}</dd>

                    <dt>パスワード</dt>
                    <dd>
                        @if ($issuedUserId === $user->id)
                            <span class="usr-copyable">
                                <code class="usr-pw usr-pw--plain">{{ $issuedPassword }}</code>
                                <button
                                    type="button"
                                    class="usr-copy-btn"
                                    data-copy-bundle="row"
                                    data-copy-login="{{ $issuedLoginId }}"
                                    data-copy-password="{{ $issuedPassword }}"
                                    data-copy-name="{{ $issuedName }}"
                                    aria-label="ログインID・パスワード・表示名をコピー"
                                >コピー</button>
                            </span>
                        @else
                            <span class="usr-copyable">
                                <span class="usr-pw usr-pw--masked">●●●●●</span>
                                @can('manage-users')
                                    <form
                                        method="POST"
                                        action="{{ route('admin.users.regenerate-password', $user) }}"
                                        data-confirm="{{ $user->id === auth()->id()
                                            ? 'あなた自身のパスワードを再発行します。今のパスワードは使えなくなるため、表示された新しいパスワードを必ず控えてください。よろしいですか？'
                                            : 'ユーザー「' . $user->name . '」のパスワードを再発行します。今のパスワードは使えなくなります。よろしいですか？' }}"
                                        class="d-inline"
                                    >
                                        @csrf
                                        <button type="submit" class="usr-copy-btn">自動生成</button>
                                    </form>
                                @endcan
                            </span>
                        @endif
                    </dd>

                    <dt>権限</dt>
                    <dd>{{ $user->role->label() }}</dd>

                    <dt>最終ログイン</dt>
                    <dd>{{ $user->last_login_at?->isoFormat('YYYY/MM/DD HH:mm') ?? '—' }}</dd>
                </dl>

                @can('manage-users')
                    @if ($user->id !== auth()->id())
                        <div class="usr-row-actions">
                            <form
                                method="POST"
                                action="{{ route('admin.users.toggle', $user) }}"
                                data-confirm="ユーザー「{{ $user->name }}」を{{ $user->is_active ? '無効化' : '有効化' }}します。よろしいですか？"
                            >
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="ph-btn ph-btn--ghost ph-btn--sm">
                                    {{ $user->is_active ? '無効にする' : '有効にする' }}
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('admin.users.destroy', $user) }}"
                                data-confirm="ユーザー「{{ $user->name }}」を削除します。ログインできなくなり、画面からは元に戻せません。よろしいですか？"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ph-btn ph-btn--danger ph-btn--sm">
                                    削除
                                </button>
                            </form>
                        </div>
                    @endif
                @endcan
            </article>
        @endforeach
    </div>

    {{ $users->links() }}
@endsection
