@extends('layouts.app')

@section('title', 'ユーザー管理')

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">ユーザー管理</h1>
        <div class="ph-page-head__actions">
            <a class="ph-btn ph-btn--primary" href="{{ route('admin.users.create') }}">
                ユーザーを追加する
            </a>
        </div>
    </div>

    <div class="ph-table-wrap">
        <table class="ph-table">
            <thead>
                <tr>
                    <th>ログインID</th>
                    <th>表示名</th>
                    <th>権限</th>
                    <th>状態</th>
                    <th class="ph-nowrap">最終ログイン</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td class="ph-num">{{ $user->login_id }}</td>
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
                        <td class="ph-text-right">
                            @if ($user->id !== auth()->id())
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
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
@endsection
