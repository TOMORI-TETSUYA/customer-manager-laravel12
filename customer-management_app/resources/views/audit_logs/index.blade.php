@extends('layouts.app')

@section('title', '操作履歴')

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">操作履歴</h1>
        <p class="ph-page-head__sub ph-num">全 {{ number_format($logs->total()) }} 件</p>
    </div>

    <section class="ph-card">
        <form class="ph-card__body" method="GET" action="{{ route('admin.audit-logs.index') }}">
            <div class="cust-filter">
                <div class="ph-field">
                    <label class="ph-field__label" for="l-user">ユーザー</label>
                    <select class="form-select" id="l-user" name="user_id">
                        <option value="">すべて</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="l-action">操作</label>
                    {{-- 選択肢は AuditLog::ACTIONS が唯一の定義元。
                         ここに直接書くと一覧の表示とずれるため増やさないこと。 --}}
                    <select class="form-select" id="l-action" name="action">
                        <option value="">すべて</option>
                        @foreach (App\Models\AuditLog::ACTIONS as $value => $label)
                            <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="l-from">日付(から)</label>
                    <input type="date" class="form-control" id="l-from" name="from" value="{{ request('from') }}">
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="l-to">日付(まで)</label>
                    <input type="date" class="form-control" id="l-to" name="to" value="{{ request('to') }}">
                </div>

                <div class="ph-field">
                    <span class="ph-field__label">&nbsp;</span>
                    <button type="submit" class="ph-btn ph-btn--primary">絞り込む</button>
                </div>
            </div>
        </form>
    </section>

    <div class="ph-table-wrap">
        <table class="ph-table">
            <thead>
                <tr>
                    <th class="ph-nowrap">日時</th>
                    <th>ユーザー</th>
                    <th>操作</th>
                    <th>対象</th>
                    <th>変更カラム</th>
                    <th>IPアドレス</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="ph-num ph-nowrap">{{ $log->created_at?->isoFormat('YYYY/MM/DD HH:mm:ss') }}</td>
                        <td>{{ $log->user?->name ?? '—' }}</td>
                        <td>
                            <span class="ph-badge ph-badge--muted">{{ $log->actionLabel() }}</span>
                        </td>
                        <td class="ph-num">{{ $log->targetLabel() ?? '—' }}</td>
                        <td class="ph-text-sm ph-muted">
                            {{-- 変更されたカラム名のみ。値は記録しない (§23.3)。
                                 追跡できるよう、あえてDBのカラム名のまま表示する。 --}}
                            {{ $log->changed_fields ? implode(', ', $log->changed_fields) : '—' }}
                        </td>
                        <td class="ph-num">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="ph-muted">条件に一致する履歴はありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
@endsection
