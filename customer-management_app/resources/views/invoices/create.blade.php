@extends('layouts.app')

@section('title', '請求登録')

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">請求登録</h1>
        <p class="ph-page-head__sub">{{ $customer->display_name }} ({{ $customer->customer_code }})</p>
        <div class="ph-page-head__actions">
            <a class="ph-btn ph-btn--ghost" href="{{ route('customers.show', $customer) }}">詳細へ戻る</a>
        </div>
    </div>

    <section class="ph-card">
        <form
            class="ph-card__body ph-form"
            method="POST"
            action="{{ route('invoices.store', $customer) }}"
            novalidate
        >
            @csrf

            <div class="ph-form-grid">
                <div class="ph-field is-span-2">
                    <label class="ph-field__label" for="contract_id">関連契約</label>
                    <select
                        class="form-select @error('contract_id') is-invalid @enderror"
                        id="contract_id"
                        name="contract_id"
                    >
                        <option value="">契約に紐付けない</option>
                        @foreach ($contracts as $contract)
                            <option value="{{ $contract->id }}" @selected((string) old('contract_id') === (string) $contract->id)>
                                {{ $contract->contract_number }} / {{ $contract->service_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('contract_id')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="issue_date">
                        発行日<span class="is-required">必須</span>
                    </label>
                    <input
                        type="date"
                        class="form-control @error('issue_date') is-invalid @enderror"
                        id="issue_date"
                        name="issue_date"
                        value="{{ old('issue_date', now()->format('Y-m-d')) }}"
                        required
                    >
                    @error('issue_date')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="due_date">
                        支払期限<span class="is-required">必須</span>
                    </label>
                    <input
                        type="date"
                        class="form-control @error('due_date') is-invalid @enderror"
                        id="due_date"
                        name="due_date"
                        value="{{ old('due_date', now()->addMonth()->endOfMonth()->format('Y-m-d')) }}"
                        required
                    >
                    @error('due_date')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="amount">
                        請求金額(円)<span class="is-required">必須</span>
                    </label>
                    <input
                        type="number"
                        class="form-control @error('amount') is-invalid @enderror"
                        id="amount"
                        name="amount"
                        value="{{ old('amount') }}"
                        min="1"
                        step="1"
                        inputmode="numeric"
                        required
                    >
                    @error('amount')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field is-span-2">
                    <label class="ph-field__label" for="notes">請求備考</label>
                    <textarea
                        class="form-control @error('notes') is-invalid @enderror"
                        id="notes"
                        name="notes"
                        rows="3"
                        maxlength="2000"
                    >{{ old('notes') }}</textarea>
                    <p class="ph-field__help">請求備考は暗号化して保存されます。</p>
                    @error('notes')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <p class="ph-field__help">請求番号は登録時に自動発行されます。</p>

            <div class="ph-form-actions">
                <button type="submit" class="ph-btn ph-btn--primary">登録する</button>
                <a class="ph-btn ph-btn--ghost" href="{{ route('customers.show', $customer) }}">キャンセル</a>
            </div>
        </form>
    </section>
@endsection
