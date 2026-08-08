@extends('layouts.app')

@php
    // このビューは顧客一覧のほか、/dialog(対応履歴)・/contract(契約管理)・
    // /payment(請求・入金) からも使われる (§16)。
    // 中身は同じ顧客一覧で、初期の並び順と絞り込みだけが異なる。
    $pageTitle = match (true) {
        request()->routeIs('dialog.index')   => '対応履歴',
        request()->routeIs('contract.index') => '契約管理',
        request()->routeIs('payment.index')  => '請求・入金',
        default                              => '顧客一覧',
    };
@endphp

@section('title', $pageTitle)

@push('styles')
    <link
        rel="stylesheet"
        href="{{ $phAsset('/css/customer.css') }}"
    >
@endpush

@push('scripts')
    <script
        src="{{ $phAsset('/js/customer-filter.js') }}"
        defer
    ></script>
@endpush

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">{{ $pageTitle }}</h1>
        <p class="ph-page-head__sub ph-num">全 {{ number_format($customers->total()) }} 件</p>
        @can('create', App\Models\Customer::class)
            <div class="ph-page-head__actions">
                <a class="ph-btn ph-btn--primary" href="{{ route('customers.create') }}">
                    顧客を登録する
                </a>
            </div>
        @endcan
    </div>

    {{-- 検索・フィルター (§18) --}}
    <section class="ph-card">
        {{-- 送信先は現在のURL。/dialog や /contract から検索しても
             そのページに留まるようにする。 --}}
        <form class="ph-card__body cust-search" method="GET" action="{{ url()->current() }}">

            <div class="cust-search__row">
                <div class="cust-search__keyword">
                    <label class="visually-hidden" for="keyword">キーワード</label>
                    <input
                        type="search"
                        class="form-control"
                        id="keyword"
                        name="keyword"
                        value="{{ request('keyword') }}"
                        placeholder="顧客ID・氏名・会社名・電話番号・メールアドレス"
                    >
                </div>

                <button type="submit" class="ph-btn ph-btn--primary">検索する</button>

                <button
                    type="button"
                    class="ph-btn ph-btn--ghost"
                    data-bs-toggle="collapse"
                    data-bs-target="#customerFilter"
                    aria-expanded="false"
                    aria-controls="customerFilter"
                >
                    詳細フィルター
                </button>

                <a class="ph-btn ph-btn--ghost" href="{{ url()->current() }}">
                    条件をクリア
                </a>
            </div>

            {{-- 詳細フィルター (§18.3) --}}
            <div class="collapse" id="customerFilter">
                <div class="cust-filter">
                    <div class="ph-field">
                        <label class="ph-field__label" for="f-type">顧客区分</label>
                        <select class="form-select" id="f-type" name="customer_type">
                            <option value="">すべて</option>
                            @foreach (App\Enums\CustomerType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(request('customer_type') === $type->value)>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ph-field">
                        <label class="ph-field__label" for="f-status">顧客ステータス</label>
                        <select class="form-select" id="f-status" name="status">
                            <option value="">すべて</option>
                            @foreach (App\Enums\CustomerStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ph-field">
                        <label class="ph-field__label" for="f-assigned">担当者</label>
                        <select class="form-select" id="f-assigned" name="assigned_user_id">
                            <option value="">すべて</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string) request('assigned_user_id') === (string) $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ph-field">
                        <label class="ph-field__label" for="f-tag">タグ</label>
                        <select class="form-select" id="f-tag" name="tag_id">
                            <option value="">すべて</option>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}" @selected((string) request('tag_id') === (string) $tag->id)>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ph-field">
                        <label class="ph-field__label" for="f-created-from">登録日(から)</label>
                        <input type="date" class="form-control" id="f-created-from" name="created_from" value="{{ request('created_from') }}">
                    </div>

                    <div class="ph-field">
                        <label class="ph-field__label" for="f-created-to">登録日(まで)</label>
                        <input type="date" class="form-control" id="f-created-to" name="created_to" value="{{ request('created_to') }}">
                    </div>

                    <div class="ph-field">
                        <label class="ph-field__label" for="f-contacted-from">最終対応日(から)</label>
                        <input type="date" class="form-control" id="f-contacted-from" name="contacted_from" value="{{ request('contacted_from') }}">
                    </div>

                    <div class="ph-field">
                        <label class="ph-field__label" for="f-contacted-to">最終対応日(まで)</label>
                        <input type="date" class="form-control" id="f-contacted-to" name="contacted_to" value="{{ request('contacted_to') }}">
                    </div>

                    <div class="ph-field">
                        <label class="ph-field__label" for="f-next-from">次回対応日(から)</label>
                        <input type="date" class="form-control" id="f-next-from" name="next_action_from" value="{{ request('next_action_from') }}">
                    </div>

                    <div class="ph-field">
                        <label class="ph-field__label" for="f-next-to">次回対応日(まで)</label>
                        <input type="date" class="form-control" id="f-next-to" name="next_action_to" value="{{ request('next_action_to') }}">
                    </div>

                    <div class="ph-field">
                        <label class="ph-field__label" for="f-contract">契約状況</label>
                        <select class="form-select" id="f-contract" name="contract_state">
                            <option value="">すべて</option>
                            <option value="active" @selected(request('contract_state') === 'active')>契約中あり</option>
                            <option value="none" @selected(request('contract_state') === 'none')>契約なし</option>
                        </select>
                    </div>

                    {{-- 入金状況は請求・入金を見られるロールにだけ出す (§16) --}}
                    @can('access-payment')
                        <div class="ph-field">
                            <label class="ph-field__label" for="f-payment">入金状況</label>
                            <select class="form-select" id="f-payment" name="payment_state">
                                <option value="">すべて</option>
                                <option value="unpaid" @selected(request('payment_state') === 'unpaid')>未入金・一部入金あり</option>
                                <option value="clear" @selected(request('payment_state') === 'clear')>未入金なし</option>
                            </select>
                        </div>
                    @endcan

                    @if (auth()->user()->isAdmin())
                        <div class="ph-field">
                            <label class="ph-field__label" for="f-deleted">削除済み顧客</label>
                            <select class="form-select" id="f-deleted" name="show_deleted">
                                <option value="">表示しない</option>
                                <option value="1" @selected($showDeleted)>削除済みのみ表示</option>
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <div class="ph-row">
                <div class="ph-field">
                    <label class="ph-field__label" for="sort">並び替え (§18.4)</label>
                    <select class="form-select" id="sort" name="sort" data-autosubmit>
                        @foreach ([
                            'created_desc'   => '登録日の新しい順',
                            'created_asc'    => '登録日の古い順',
                            'code'           => '顧客ID順',
                            'name'           => '顧客名順',
                            'company'        => '会社名順',
                            'contacted_desc' => '最終対応日の新しい順',
                            'next_action'    => '次回対応日の近い順',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(request('sort', 'created_desc') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="per_page">表示件数 (§6.2)</label>
                    <select class="form-select" id="per_page" name="per_page" data-autosubmit>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected((int) request('per_page', 25) === $option)>
                                {{ $option }}件
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </section>

    @if ($customers->isEmpty())
        <section class="ph-card">
            <div class="ph-card__body">
                <p class="ph-muted">条件に一致する顧客はいません。検索条件を変えるか、新しい顧客を登録してください。</p>
            </div>
        </section>
    @else

        {{-- テーブル表示(768px以上) --}}
        <div class="ph-table-wrap cust-table-wrap">
            <table class="ph-table">
                <thead>
                    <tr>
                        <th>顧客ID</th>
                        <th>顧客名 / 会社名</th>
                        <th>区分</th>
                        <th>ステータス</th>
                        <th>電話番号</th>
                        <th>担当者</th>
                        <th class="ph-nowrap">最終対応日</th>
                        <th class="ph-nowrap">次回対応日</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td class="ph-num ph-nowrap">{{ $customer->customer_code }}</td>
                            <td>
                                <a href="{{ route('customers.show', $customer->id) }}">
                                    {{ $customer->display_name }}
                                </a>
                                @foreach ($customer->tags as $tag)
                                    <span class="ph-badge ph-badge--muted">{{ $tag->name }}</span>
                                @endforeach
                            </td>
                            <td>{{ $customer->customer_type->label() }}</td>
                            <td>
                                <span class="ph-badge {{ $customer->status->badgeClass() }}">
                                    {{ $customer->status->label() }}
                                </span>
                            </td>
                            {{-- 電話番号はマスク表示 (§32.2) --}}
                            <td class="ph-num ph-nowrap">{{ $customer->masked_phone }}</td>
                            <td>{{ $customer->assignedUser?->name }}</td>
                            <td class="ph-num ph-nowrap">
                                {{ $customer->last_contacted_at?->isoFormat('YYYY/MM/DD') ?? '—' }}
                            </td>
                            <td class="ph-num ph-nowrap">
                                {{ $customer->next_action_at?->isoFormat('YYYY/MM/DD') ?? '—' }}
                            </td>
                            <td class="ph-text-right ph-nowrap">
                                @if ($showDeleted)
                                    <form method="POST" action="{{ route('customers.restore', $customer->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="ph-btn ph-btn--ghost ph-btn--sm">復元</button>
                                    </form>
                                @else
                                    <a class="ph-btn ph-btn--ghost ph-btn--sm" href="{{ route('customers.show', $customer->id) }}">
                                        詳細
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- カード表示(768px未満 §32.2) --}}
        <div class="cust-cards">
            @foreach ($customers as $customer)
                <article class="cust-card">
                    <div class="cust-card__head">
                        <span class="cust-card__name">
                            <a href="{{ route('customers.show', $customer->id) }}">
                                {{ $customer->display_name }}
                            </a>
                        </span>
                        <span class="ph-badge {{ $customer->status->badgeClass() }}">
                            {{ $customer->status->label() }}
                        </span>
                    </div>
                    <div class="cust-card__meta">
                        <span class="ph-num">{{ $customer->customer_code }}</span>
                        <span>{{ $customer->customer_type->label() }}</span>
                        <span class="ph-num">{{ $customer->masked_phone }}</span>
                        <span>担当: {{ $customer->assignedUser?->name ?? '未設定' }}</span>
                        @if ($customer->next_action_at)
                            <span class="ph-num">次回: {{ $customer->next_action_at->isoFormat('M/D') }}</span>
                        @endif
                    </div>
                    @if ($showDeleted)
                        <form method="POST" action="{{ route('customers.restore', $customer->id) }}">
                            @csrf
                            <button type="submit" class="ph-btn ph-btn--ghost ph-btn--sm">復元する</button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>

        {{-- ページネーション: 条件を保持したまま移動 (§33.3) --}}
        <div class="ph-pagination-row">
            <p class="ph-muted ph-text-sm ph-num">
                {{ number_format($customers->firstItem()) }}〜{{ number_format($customers->lastItem()) }} 件
                / 全 {{ number_format($customers->total()) }} 件
            </p>
            {{ $customers->links() }}
        </div>
    @endif
@endsection
