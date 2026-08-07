@extends('layouts.app')

@section('title', '入金登録')

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">入金登録</h1>
        <p class="ph-page-head__sub">
            {{ $invoice->invoice_number }} / {{ $invoice->customer?->display_name }}
        </p>
        <div class="ph-page-head__actions">
            <a class="ph-btn ph-btn--ghost" href="{{ route('customers.show', $invoice->customer_id) }}">顧客詳細へ戻る</a>
        </div>
    </div>

    <section class="ph-card">
        <div class="ph-card__body">
            <h2 class="ph-card__title">請求内容</h2>
            <dl class="cust-props">
                <dt>請求額</dt>
                <dd class="ph-num">¥{{ number_format((float) $invoice->amount) }}</dd>
                <dt>入金済み</dt>
                <dd class="ph-num">¥{{ number_format($invoice->paidTotal()) }}</dd>
                <dt>残額</dt>
                <dd class="ph-num"><strong>¥{{ number_format($invoice->remaining()) }}</strong></dd>
                <dt>支払期限</dt>
                <dd class="ph-num">{{ $invoice->due_date?->isoFormat('YYYY/MM/DD') }}</dd>
            </dl>

            @if ($invoice->payments->isNotEmpty())
                <div class="ph-table-wrap">
                    <table class="ph-table">
                        <thead>
                            <tr>
                                <th>入金日</th>
                                <th class="ph-text-right">入金額</th>
                                <th>方法</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->payments as $payment)
                                <tr>
                                    <td class="ph-num">{{ $payment->paid_at?->isoFormat('YYYY/MM/DD') }}</td>
                                    <td class="ph-num ph-text-right">¥{{ number_format((float) $payment->amount) }}</td>
                                    <td>{{ App\Models\Payment::METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="ph-card">
        <form
            class="ph-card__body ph-form"
            method="POST"
            action="{{ route('payments.store', $invoice) }}"
            novalidate
        >
            @csrf

            <h2 class="ph-card__title">新しい入金</h2>

            <div class="ph-form-grid">
                <div class="ph-field">
                    <label class="ph-field__label" for="paid_at">
                        入金日<span class="is-required">必須</span>
                    </label>
                    <input
                        type="date"
                        class="form-control @error('paid_at') is-invalid @enderror"
                        id="paid_at"
                        name="paid_at"
                        value="{{ old('paid_at', now()->format('Y-m-d')) }}"
                        required
                    >
                    @error('paid_at')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="amount">
                        入金額(円)<span class="is-required">必須</span>
                    </label>
                    <input
                        type="number"
                        class="form-control @error('amount') is-invalid @enderror"
                        id="amount"
                        name="amount"
                        value="{{ old('amount', $invoice->remaining()) }}"
                        min="1"
                        step="1"
                        inputmode="numeric"
                        required
                    >
                    <p class="ph-field__help">分割入金に対応しています。残額の一部だけ登録できます。</p>
                    @error('amount')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="payment_method">
                        入金方法<span class="is-required">必須</span>
                    </label>
                    <select
                        class="form-select @error('payment_method') is-invalid @enderror"
                        id="payment_method"
                        name="payment_method"
                        required
                    >
                        @foreach (App\Models\Payment::METHODS as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', 'bank') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field is-span-2">
                    <label class="ph-field__label" for="notes">入金備考</label>
                    <textarea
                        class="form-control @error('notes') is-invalid @enderror"
                        id="notes"
                        name="notes"
                        rows="3"
                        maxlength="2000"
                    >{{ old('notes') }}</textarea>
                    <p class="ph-field__help">入金備考は暗号化して保存されます。</p>
                    @error('notes')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="ph-form-actions">
                <button type="submit" class="ph-btn ph-btn--primary">入金を登録する</button>
                <a class="ph-btn ph-btn--ghost" href="{{ route('customers.show', $invoice->customer_id) }}">キャンセル</a>
            </div>
        </form>
    </section>
@endsection
