@extends('layouts.app')

@section('title', '顧客詳細')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ $phAsset('/css/customer.css') }}"
    >
@endpush

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">{{ $customer->display_name }}</h1>
        <span class="ph-badge {{ $customer->status->badgeClass() }}">{{ $customer->status->label() }}</span>
        <p class="ph-page-head__sub ph-num">{{ $customer->customer_code }}</p>
        <div class="ph-page-head__actions">
            @can('update', $customer)
                <a class="ph-btn ph-btn--primary" href="{{ route('customers.edit', $customer) }}">編集する</a>
            @endcan
            <a class="ph-btn ph-btn--ghost" href="{{ route('customers.index') }}">一覧へ戻る</a>
        </div>
    </div>

    <div class="cust-detail">

        {{-- 左: 基本情報 --}}
        <section class="ph-card">
            <div class="ph-card__body">
                <h2 class="ph-card__title">基本情報</h2>

                <dl class="cust-props">
                    <dt>顧客区分</dt>
                    <dd>{{ $customer->customer_type->label() }}</dd>

                    <dt>顧客名</dt>
                    <dd>
                        {{ $customer->customer_name ?? '—' }}
                        @if ($customer->customer_name_kana)
                            <span class="ph-muted ph-text-sm">({{ $customer->customer_name_kana }})</span>
                        @endif
                    </dd>

                    <dt>会社名</dt>
                    <dd>
                        {{ $customer->company_name ?? '—' }}
                        @if ($customer->company_name_kana)
                            <span class="ph-muted ph-text-sm">({{ $customer->company_name_kana }})</span>
                        @endif
                    </dd>

                    @if ($customer->corporate_contact_name)
                        <dt>法人担当者</dt>
                        <dd>{{ $customer->corporate_contact_name }}</dd>
                    @endif

                    <dt>電話番号</dt>
                    <dd class="ph-num">{{ $customer->phone_encrypted }}</dd>

                    <dt>メール</dt>
                    <dd>{{ $customer->email_encrypted ?? '—' }}</dd>

                    <dt>住所</dt>
                    <dd>
                        @if ($customer->postal_code)
                            〒{{ $customer->postal_code }}
                        @endif
                        {{ $customer->prefecture }}{{ $customer->city }}{{ $customer->address_encrypted }}
                        {{ $customer->building_encrypted }}
                        @unless ($customer->postal_code || $customer->prefecture || $customer->city || $customer->address_encrypted)
                            —
                        @endunless
                    </dd>

                    <dt>希望連絡方法</dt>
                    <dd>
                        {{ ['phone' => '電話', 'email' => 'メール', 'line' => 'LINE', 'mail' => '郵送'][$customer->preferred_contact_method] ?? '未設定' }}
                    </dd>

                    <dt>担当者</dt>
                    <dd>{{ $customer->assignedUser?->name ?? '未設定' }}</dd>

                    <dt>流入経路</dt>
                    <dd>{{ $customer->source ?? '—' }}</dd>

                    <dt>タグ</dt>
                    <dd>
                        @forelse ($customer->tags as $tag)
                            <span class="ph-badge ph-badge--muted">{{ $tag->name }}</span>
                        @empty
                            —
                        @endforelse
                    </dd>

                    <dt>最終対応日</dt>
                    <dd class="ph-num">{{ $customer->last_contacted_at?->isoFormat('YYYY/MM/DD HH:mm') ?? '—' }}</dd>

                    <dt>次回対応日</dt>
                    <dd class="ph-num">{{ $customer->next_action_at?->isoFormat('YYYY/MM/DD') ?? '—' }}</dd>

                    <dt>登録</dt>
                    <dd class="ph-text-sm ph-muted">
                        {{ $customer->created_at?->isoFormat('YYYY/MM/DD HH:mm') }}
                    </dd>

                    <dt>更新</dt>
                    <dd class="ph-text-sm ph-muted">
                        {{ $customer->updated_at?->isoFormat('YYYY/MM/DD HH:mm') }}
                    </dd>
                </dl>

                @if ($customer->notes_encrypted)
                    <div class="ph-stack-2">
                        <h3 class="ph-field__label">備考</h3>
                        <p style="white-space: pre-wrap;">{{ $customer->notes_encrypted }}</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- 右: 対応履歴・契約・請求 --}}
        <div class="ph-stack-4">

            {{-- 対応履歴はメンバーには表示しない (§16) --}}
            @can('access-dialog')
            <section class="ph-card">
                <div class="ph-card__body">
                    <div class="ph-row">
                        <h2 class="ph-card__title">対応履歴</h2>
                        @can('write-data')
                            <a class="ph-btn ph-btn--ghost ph-btn--sm" style="margin-left:auto;" href="{{ route('contacts.create', $customer) }}">
                                対応履歴を登録する
                            </a>
                        @endcan
                    </div>

                    @if ($customer->contacts->isEmpty())
                        <p class="ph-muted ph-text-sm">対応履歴はまだありません。</p>
                    @else
                        <ul class="dash-list">
                            @foreach ($customer->contacts as $contact)
                                <li class="dash-list__item">
                                    <div class="dash-list__main">
                                        <span class="dash-list__title">{{ $contact->subject }}</span>
                                        <span class="dash-list__meta">
                                            {{ App\Models\CustomerContact::METHODS[$contact->contact_method] ?? $contact->contact_method }}
                                            / {{ $contact->creator?->name }}
                                            @if ($contact->response_encrypted)
                                                — {{ Str::limit($contact->response_encrypted, 60) }}
                                            @endif
                                        </span>
                                    </div>
                                    <span class="ph-muted ph-text-sm ph-num ph-nowrap">
                                        {{ $contact->contacted_at?->isoFormat('YY/MM/DD HH:mm') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
            @endcan

            <section class="ph-card">
                <div class="ph-card__body">
                    <div class="ph-row">
                        <h2 class="ph-card__title">契約</h2>
                        @can('write-data')
                            <a class="ph-btn ph-btn--ghost ph-btn--sm" style="margin-left:auto;" href="{{ route('contracts.create', $customer) }}">
                                契約を登録する
                            </a>
                        @endcan
                    </div>

                    @if ($customer->contracts->isEmpty())
                        <p class="ph-muted ph-text-sm">契約はまだありません。</p>
                    @else
                        <div class="ph-table-wrap">
                            <table class="ph-table">
                                <thead>
                                    <tr>
                                        <th>契約番号</th>
                                        <th>サービス名</th>
                                        <th>契約日</th>
                                        <th class="ph-text-right">金額</th>
                                        <th>状態</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customer->contracts as $contract)
                                        <tr>
                                            <td class="ph-num ph-nowrap">{{ $contract->contract_number }}</td>
                                            <td>{{ $contract->service_name }}</td>
                                            <td class="ph-num ph-nowrap">{{ $contract->contract_date?->isoFormat('YYYY/MM/DD') }}</td>
                                            <td class="ph-num ph-text-right ph-nowrap">¥{{ number_format((float) $contract->amount) }}</td>
                                            <td>
                                                <span class="ph-badge ph-badge--muted">
                                                    {{ App\Models\Contract::STATUSES[$contract->status] ?? $contract->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            {{-- 請求・入金はメンバーには表示しない (§16) --}}
            @can('access-payment')
            <section class="ph-card">
                <div class="ph-card__body">
                    <div class="ph-row">
                        <h2 class="ph-card__title">請求・入金</h2>
                        @can('write-data')
                            <a class="ph-btn ph-btn--ghost ph-btn--sm" style="margin-left:auto;" href="{{ route('invoices.create', $customer) }}">
                                請求を登録する
                            </a>
                        @endcan
                    </div>

                    @if ($customer->invoices->isEmpty())
                        <p class="ph-muted ph-text-sm">請求はまだありません。</p>
                    @else
                        <div class="ph-table-wrap">
                            <table class="ph-table">
                                <thead>
                                    <tr>
                                        <th>請求番号</th>
                                        <th>発行日</th>
                                        <th>期限</th>
                                        <th class="ph-text-right">請求額</th>
                                        <th class="ph-text-right">入金済み</th>
                                        <th class="ph-text-right">残額</th>
                                        <th>状態</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customer->invoices as $invoice)
                                        <tr>
                                            <td class="ph-num ph-nowrap">{{ $invoice->invoice_number }}</td>
                                            <td class="ph-num ph-nowrap">{{ $invoice->issue_date?->isoFormat('YY/MM/DD') }}</td>
                                            <td class="ph-num ph-nowrap">{{ $invoice->due_date?->isoFormat('YY/MM/DD') }}</td>
                                            <td class="ph-num ph-text-right ph-nowrap">¥{{ number_format((float) $invoice->amount) }}</td>
                                            <td class="ph-num ph-text-right ph-nowrap">¥{{ number_format($invoice->paidTotal()) }}</td>
                                            <td class="ph-num ph-text-right ph-nowrap">¥{{ number_format($invoice->remaining()) }}</td>
                                            <td>
                                                <span class="ph-badge {{ $invoice->status->badgeClass() }}">
                                                    {{ $invoice->status->label() }}
                                                </span>
                                            </td>
                                            <td class="ph-text-right ph-nowrap">
                                                @can('write-data')
                                                    @if ($invoice->status !== App\Enums\InvoiceStatus::Paid)
                                                        <a class="ph-btn ph-btn--ghost ph-btn--sm" href="{{ route('payments.create', $invoice) }}">
                                                            入金登録
                                                        </a>
                                                    @endif
                                                @endcan
                                            </td>
                                        </tr>

                                        {{-- 請求の備考と入金明細。
                                             どちらも登録できる項目なので、ここで確認できるようにする。 --}}
                                        @if ($invoice->notes_encrypted || $invoice->payments->isNotEmpty())
                                            <tr class="cust-invoice-detail">
                                                <td colspan="8">
                                                    @if ($invoice->notes_encrypted)
                                                        <p class="cust-invoice-detail__note">
                                                            <span class="ph-muted">備考</span>
                                                            {{ $invoice->notes_encrypted }}
                                                        </p>
                                                    @endif

                                                    @if ($invoice->payments->isNotEmpty())
                                                        <ul class="cust-payments">
                                                            @foreach ($invoice->payments as $payment)
                                                                <li class="cust-payments__item">
                                                                    <span class="ph-num ph-nowrap">
                                                                        {{ $payment->paid_at?->isoFormat('YYYY/MM/DD') }}
                                                                    </span>
                                                                    <span class="ph-num ph-nowrap cust-payments__amount">
                                                                        ¥{{ number_format((float) $payment->amount) }}
                                                                    </span>
                                                                    <span class="ph-badge ph-badge--muted">
                                                                        {{ App\Models\Payment::METHODS[$payment->payment_method] ?? $payment->payment_method }}
                                                                    </span>
                                                                    @if ($payment->notes_encrypted)
                                                                        <span class="ph-muted ph-text-sm">
                                                                            {{ $payment->notes_encrypted }}
                                                                        </span>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
            @endcan

        </div>
    </div>
@endsection
