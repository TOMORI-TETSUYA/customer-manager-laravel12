@extends('layouts.app')

@section('title', '対応履歴登録')

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">対応履歴登録</h1>
        <p class="ph-page-head__sub">{{ $customer->display_name }} ({{ $customer->customer_code }})</p>
        <div class="ph-page-head__actions">
            <a class="ph-btn ph-btn--ghost" href="{{ route('customers.show', $customer) }}">詳細へ戻る</a>
        </div>
    </div>

    <section class="ph-card">
        <form
            class="ph-card__body ph-form"
            method="POST"
            action="{{ route('contacts.store', $customer) }}"
            novalidate
        >
            @csrf

            <div class="ph-form-grid">
                <div class="ph-field">
                    <label class="ph-field__label" for="contacted_at">
                        対応日時<span class="is-required">必須</span>
                    </label>
                    <input
                        type="datetime-local"
                        class="form-control @error('contacted_at') is-invalid @enderror"
                        id="contacted_at"
                        name="contacted_at"
                        value="{{ old('contacted_at', now()->format('Y-m-d\TH:i')) }}"
                        required
                    >
                    @error('contacted_at')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="contact_method">
                        対応方法<span class="is-required">必須</span>
                    </label>
                    <select
                        class="form-select @error('contact_method') is-invalid @enderror"
                        id="contact_method"
                        name="contact_method"
                        required
                    >
                        @foreach (App\Models\CustomerContact::METHODS as $value => $label)
                            <option value="{{ $value }}" @selected(old('contact_method') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('contact_method')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field is-span-2">
                    <label class="ph-field__label" for="subject">
                        件名<span class="is-required">必須</span>
                    </label>
                    <input
                        type="text"
                        class="form-control @error('subject') is-invalid @enderror"
                        id="subject"
                        name="subject"
                        value="{{ old('subject') }}"
                        maxlength="200"
                        required
                    >
                    @error('subject')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field is-span-2">
                    <label class="ph-field__label" for="response">対応内容</label>
                    <textarea
                        class="form-control @error('response') is-invalid @enderror"
                        id="response"
                        name="response"
                        rows="5"
                        maxlength="5000"
                    >{{ old('response') }}</textarea>
                    <p class="ph-field__help">対応内容は暗号化して保存されます。</p>
                    @error('response')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="status">
                        対応ステータス<span class="is-required">必須</span>
                    </label>
                    <select
                        class="form-select @error('status') is-invalid @enderror"
                        id="status"
                        name="status"
                        required
                    >
                        @foreach (['done' => '完了', 'pending' => '対応中', 'follow_up' => '要フォロー'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'done') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ph-field">
                    <label class="ph-field__label" for="next_action_at">次回対応日</label>
                    <input
                        type="datetime-local"
                        class="form-control @error('next_action_at') is-invalid @enderror"
                        id="next_action_at"
                        name="next_action_at"
                        value="{{ old('next_action_at') }}"
                    >
                    @error('next_action_at')
                        <p class="ph-field__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="ph-form-actions">
                <button type="submit" class="ph-btn ph-btn--primary">登録する</button>
                <a class="ph-btn ph-btn--ghost" href="{{ route('customers.show', $customer) }}">キャンセル</a>
            </div>
        </form>
    </section>
@endsection
