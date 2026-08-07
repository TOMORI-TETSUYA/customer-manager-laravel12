@extends('layouts.app')

@section('title', '顧客登録')

@push('styles')
    <link
        rel="stylesheet"
        href="/css/customer.css"
    >
@endpush

@push('scripts')
    <script
        src="/js/form-validation.js"
        defer
    ></script>
@endpush

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">顧客登録</h1>
        <div class="ph-page-head__actions">
            <a class="ph-btn ph-btn--ghost" href="{{ route('customers.index') }}">一覧へ戻る</a>
        </div>
    </div>

    {{-- 重複候補の警告 (§19: 警告 → 続行確認) --}}
    @isset($duplicates)
        <section class="ph-alert ph-alert--danger cust-dup" role="alert">
            <div class="ph-stack-3">
                <strong>同じ電話番号またはメールアドレスの顧客が見つかりました。</strong>
                <ul class="cust-dup__list">
                    @foreach ($duplicates as $dup)
                        <li class="cust-dup__item">
                            <span class="ph-num">{{ $dup->customer_code }}</span>
                            <span>{{ $dup->display_name }}</span>
                            <span class="ph-badge {{ $dup->status->badgeClass() }}">{{ $dup->status->label() }}</span>
                            <a href="{{ route('customers.show', $dup->id) }}">詳細を確認する</a>
                        </li>
                    @endforeach
                </ul>
                <p class="ph-text-sm">
                    別の顧客として登録する場合は、内容を確認のうえ「重複を確認して登録する」を押してください。
                </p>
            </div>
        </section>
    @endisset

    <section class="ph-card">
        <form
            class="ph-card__body ph-form"
            method="POST"
            action="{{ route('customers.store') }}"
            data-customer-form
            novalidate
        >
            @csrf

            @isset($duplicates)
                <input type="hidden" name="force" value="1">
            @endisset

            @include('customers._form')

            <div class="ph-form-actions">
                <button type="submit" class="ph-btn ph-btn--primary">
                    @isset($duplicates)
                        重複を確認して登録する
                    @else
                        登録する
                    @endisset
                </button>
                <a class="ph-btn ph-btn--ghost" href="{{ route('customers.index') }}">キャンセル</a>
            </div>
        </form>
    </section>
@endsection
