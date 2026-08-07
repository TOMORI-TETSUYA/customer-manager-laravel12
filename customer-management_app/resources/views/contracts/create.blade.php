@extends('layouts.app')

@section('title', '契約登録')

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">契約登録</h1>
        <p class="ph-page-head__sub">{{ $customer->display_name }} ({{ $customer->customer_code }})</p>
        <div class="ph-page-head__actions">
            <a class="ph-btn ph-btn--ghost" href="{{ route('customers.show', $customer) }}">詳細へ戻る</a>
        </div>
    </div>

    <section class="ph-card">
        <form
            class="ph-card__body ph-form"
            method="POST"
            action="{{ route('contracts.store', $customer) }}"
            novalidate
        >
            @csrf

            <div class="ph-form-grid">
                <div class="ph-field is-span-2">
                    <label class="ph-field__label" for="service_name">
                        サービス名<span class="is-required">必須</span>
                    </label>
                    <input
                        type="text"
                        class="form-control @error('service_name') is-invalid @enderror"
                        id="service_name"
                        name="service_name"
                        value="{{ old('service_name') }}"
                        maxlength="150"
                        required
                    >
                    @error('service_name')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="contract_date">
                        契約日<span class="is-required">必須</span>
                    </label>
                    <input
                        type="date"
                        class="form-control @error('contract_date') is-invalid @enderror"
                        id="contract_date"
                        name="contract_date"
                        value="{{ old('contract_date', now()->format('Y-m-d')) }}"
                        required
                    >
                    @error('contract_date')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="amount">
                        契約金額(円)<span class="is-required">必須</span>
                    </label>
                    <input
                        type="number"
                        class="form-control @error('amount') is-invalid @enderror"
                        id="amount"
                        name="amount"
                        value="{{ old('amount') }}"
                        min="0"
                        step="1"
                        inputmode="numeric"
                        required
                    >
                    @error('amount')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="status">
                        契約ステータス<span class="is-required">必須</span>
                    </label>
                    <select
                        class="form-select @error('status') is-invalid @enderror"
                        id="status"
                        name="status"
                        required
                    >
                        @foreach (App\Models\Contract::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <p class="ph-field__help">契約番号は登録時に自動発行されます。</p>

            <div class="ph-form-actions">
                <button type="submit" class="ph-btn ph-btn--primary">登録する</button>
                <a class="ph-btn ph-btn--ghost" href="{{ route('customers.show', $customer) }}">キャンセル</a>
            </div>
        </form>
    </section>
@endsection
