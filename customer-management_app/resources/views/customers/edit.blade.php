@extends('layouts.app')

@section('title', '顧客編集')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ $phAsset('/css/customer.css') }}"
    >
@endpush

@push('scripts')
    <script
        src="{{ $phAsset('/js/form-validation.js') }}"
        defer
    ></script>
@endpush

@section('content')
    <div class="ph-page-head">
        <h1 class="ph-page-head__title">顧客編集</h1>
        <p class="ph-page-head__sub ph-num">{{ $customer->customer_code }}</p>
        <div class="ph-page-head__actions">
            <a class="ph-btn ph-btn--ghost" href="{{ route('customers.show', $customer) }}">詳細へ戻る</a>
        </div>
    </div>

    <section class="ph-card">
        <form
            class="ph-card__body ph-form"
            method="POST"
            action="{{ route('customers.update', $customer) }}"
            data-customer-form
            novalidate
        >
            @csrf
            @method('PUT')

            @include('customers._form')

            <div class="ph-form-actions">
                <button type="submit" class="ph-btn ph-btn--primary">変更を保存する</button>
                <a class="ph-btn ph-btn--ghost" href="{{ route('customers.show', $customer) }}">キャンセル</a>
            </div>
        </form>
    </section>

    {{-- 削除は主要操作から離す (§32.2) --}}
    @can('delete', $customer)
        <div class="cust-danger-zone">
            <form
                method="POST"
                action="{{ route('customers.destroy', $customer) }}"
                data-confirm="顧客「{{ $customer->display_name }}」を削除します。削除後も管理者が復元できます。よろしいですか？"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="ph-btn ph-btn--danger">この顧客を削除する</button>
            </form>
        </div>
    @endcan
@endsection
